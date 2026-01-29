<?php

use App\Models\Report;
use App\Models\Student;
use App\Models\Infraction;
use App\Models\Cycle;
use App\Models\CommunityService;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;

    // Filters
    public string $search = '';
    public string $status = '';
    public string $severity = '';
    
    // Modal state
    public bool $showReportModal = false;
    public ?Report $editingReport = null;
    
    // Form fields
    public string $studentSearch = '';
    public ?string $selectedStudentId = null;
    public ?string $infractionId = null;
    public string $subject = '';
    public string $description = '';
    public string $reportDate = '';
    public string $reportTime = '';

    public function mount(): void
    {
        $this->reportDate = now()->format('Y-m-d');
        $this->reportTime = now()->format('H:i');
        // Open create modal automatically when navigated with query params
        if (request()->query('open_create')) {
            $this->selectedStudentId = request()->query('student_id');
            $this->studentSearch = request()->query('student_name') ?? '';
            $this->showReportModal = true;
        }
    }

    public function openCreateModal(): void
    {
        $this->authorize('teacher-or-admin');
        $this->resetForm();
        $this->showReportModal = true;
    }

    public function resetForm(): void
    {
        $this->reset(['editingReport', 'selectedStudentId', 'studentSearch', 'infractionId', 'subject', 'description']);
        $this->reportDate = now()->format('Y-m-d');
        $this->reportTime = now()->format('H:i');
    }

    public function selectStudent(string $id): void
    {
        $this->selectedStudentId = $id;
        $this->studentSearch = Student::find($id)->name;
    }

    public function save(): void
    {
        $this->authorize('teacher-or-admin');
        $this->validate([
            'selectedStudentId' => 'required|exists:students,id',
            'infractionId' => 'required|exists:infractions,id',
            'reportDate' => 'required|date',
            'reportTime' => 'required',
            'subject' => 'nullable|string|max:100',
            'description' => 'required|string',
        ], [
            'selectedStudentId.required' => 'Debe seleccionar un alumno.',
            'infractionId.required' => 'Debe seleccionar una infracción.',
            'description.required' => 'La descripción es obligatoria.',
        ]);

        $activeCycle = Cycle::where('is_active', true)->first();
        
        if (!$activeCycle) {
            $this->dispatch('notify', ['message' => 'No hay un ciclo activo configurado.', 'variant' => 'danger']);
            return;
        }

        $reportDateTime = Carbon::parse($this->reportDate . ' ' . $this->reportTime);

        $report = Report::create([
            'cycle_id' => $activeCycle->id,
            'student_id' => $this->selectedStudentId,
            'teacher_id' => auth()->id(),
            'infraction_id' => $this->infractionId,
            'subject' => $this->subject,
            'description' => $this->description,
            'date' => $reportDateTime,
            'status' => 'PENDING_SIGNATURE',
        ]);

        // "Rule of 3" Check
        $this->checkCommunityServiceTrigger($this->selectedStudentId, $activeCycle->id);

        $this->showReportModal = false;
        $this->resetForm();
        $this->dispatch('notify', ['message' => 'Reporte registrado exitosamente.']);
    }

    protected function checkCommunityServiceTrigger(string $studentId, int $cycleId): void
    {
        $reportsCount = Report::countForStudentInCycle($studentId, $cycleId);
        
        // Every 3rd report triggers a suggested community service
        if ($reportsCount > 0 && $reportsCount % 3 === 0) {
            // Check if one is already pending for this count to avoid duplicates if re-triggered
            // For now, we skip duplicates. In a real scenario, we might want to be more specific.
            
            $student = Student::find($studentId);
            $this->dispatch('community-service-suggested', [
                'student_name' => $student->name,
                'count' => $reportsCount
            ]);
        }
    }

    public function deleteReport(string $id): void
    {
        $this->authorize('admin-only');
        $report = Report::findOrFail($id);

        if ($report->status === 'SIGNED') {
            $this->dispatch('notify', ['message' => 'No se puede eliminar un reporte que ya ha sido firmado.', 'variant' => 'danger']);
            return;
        }

        $report->delete();
        $this->dispatch('notify', ['message' => 'Reporte eliminado correctamente.']);
    }

    public function signReport(string $id): void
    {
        $this->authorize('parent-only');
        
        $report = Report::findOrFail($id);
        
        // Ensure the report belongs to one of the parent's students
        $parentStudentIds = auth()->user()->students->pluck('id')->toArray();
        if (!in_array($report->student_id, $parentStudentIds)) {
            abort(403, 'No tiene permiso para firmar este reporte.');
        }

        $report->update([
            'status' => 'SIGNED',
            'signed_at' => now(),
            'signed_by_parent_id' => auth()->id(),
        ]);

        $this->dispatch('notify', ['message' => 'Reporte firmado correctamente.']);
    }

    public function with(): array
    {
        $activeCycle = Cycle::where('is_active', true)->first();

        $reports = Report::with(['student', 'teacher', 'infraction', 'parent'])
            ->when(auth()->user()->isParent(), function ($q) {
                $q->whereHas('student.parents', function ($pq) {
                    $pq->where('users.id', auth()->id());
                });
            })
            ->when($activeCycle, fn($q) => $q->where('cycle_id', $activeCycle->id))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->severity, fn($q) => $q->whereHas('infraction', fn($iq) => $iq->where('severity', $this->severity)))
            ->when($this->search, function($q) {
                $q->whereHas('student', fn($sq) => $sq->where('name', 'like', "%{$this->search}%"))
                  ->orWhere('subject', 'like', "%{$this->search}%");
            })
            ->orderBy('date', 'desc')
            ->paginate(10);

        $studentResults = [];
        if (strlen($this->studentSearch) >= 3 && !$this->selectedStudentId) {
            $studentResults = Student::where('name', 'like', "%{$this->studentSearch}%")
                ->limit(5)
                ->get();
        }

        return [
            'reports' => $reports,
            'infractions' => Infraction::all(),
            'studentResults' => $studentResults,
            'activeCycle' => $activeCycle,
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">Reportes Disciplinarios</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">Seguimiento de conducta y faltas al reglamento.</flux:text>
        </div>
        @can('teacher-or-admin')
            <div class="flex gap-2">
                @can('admin-only')
                    <flux:button variant="ghost" icon="cog-6-tooth" href="{{ route('infractions.index') }}" wire:navigate>Gestionar Tipos</flux:button>
                @endcan
                <flux:button variant="primary" icon="plus-circle" wire:click="openCreateModal">Nuevo Reporte</flux:button>
            </div>
        @endcan
    </div>

    <!-- Filters -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Buscar por alumno o asunto..." class="md:col-span-2" />
        <flux:select wire:model.live="status" placeholder="Estado...">
            <option value="">Todos los estados</option>
            <option value="PENDING_SIGNATURE">Pendiente de Firma</option>
            <option value="SIGNED">Firmado</option>
        </flux:select>
        <flux:select wire:model.live="severity" placeholder="Gravedad...">
            <option value="">Todas las gravedades</option>
            <option value="NORMAL">Normal</option>
            <option value="GRAVE">Grave</option>
        </flux:select>
    </div>

    <!-- Reports Table -->
    <div class="p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="py-3 px-2 font-semibold">Fecha</th>
                    <th class="py-3 px-2 font-semibold">Alumno</th>
                    <th class="py-3 px-2 font-semibold">Infracción / Asunto</th>
                    {{-- <th class="py-3 px-2 font-semibold text-center">Gravedad</th> --}}
                    <th class="py-3 px-2 font-semibold text-center">Estado</th>
                    <th class="py-3 px-2 text-right font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($reports as $report)
                    <tr wire:key="{{ $report->id }}">
                        <td class="py-4 px-2">
                            <div class="font-medium">{{ $report->date->format('d/m/Y') }}</div>
                            <div class="text-xs text-zinc-500">{{ $report->date->format('H:i') }}</div>
                        </td>
                        <td class="py-4 px-2">
                            <div class="font-bold">{{ $report->student->name }}</div>
                        </td>
                        <td class="py-4 px-2">
                            <div class="font-medium text-blue-600 dark:text-blue-400">{{ $report->infraction->description }}</div>
                            @if($report->subject)
                                <div class="text-xs font-semibold uppercase mt-1">Asunto: {{ $report->subject }}</div>
                            @endif
                            <div class="text-xs text-zinc-500 whitespace-normal line-clamp-1 italic">{{ $report->description }}</div>
                        </td>
                        {{-- <td class="py-4 px-2 text-center">
                            @if($report->infraction->severity === 'GRAVE')
                                <flux:badge color="red" size="sm" inset="left">Grave</flux:badge>
                            @else
                                <flux:badge color="neutral" size="sm" inset="left">Normal</flux:badge>
                            @endif
                        </td> --}}
                        <td class="py-4 px-2 text-center">
                            @if($report->status === 'SIGNED')
                                <div class="flex flex-col items-center">
                                    <flux:badge color="green" size="sm" inset="left" icon="check-badge">Firmado</flux:badge>
                                    @if($report->signed_at)
                                        <span class="text-[10px] text-zinc-500 mt-1">{{ $report->signed_at->format('d/m/Y H:i') }}</span>
                                    @endif
                                </div>
                            @else
                                <flux:badge color="amber" size="sm" inset="left" icon="clock">Pendiente</flux:badge>
                            @endif
                        </td>
                        <td class="py-4 px-2 text-right">
                            <div class="flex justify-end gap-1">
                                @if(auth()->user()->isParent() && $report->status !== 'SIGNED')
                                    <flux:button variant="primary" size="sm" icon="finger-print" wire:click="signReport('{{ $report->id }}')">Firmar</flux:button>
                                @endif

                                @can('admin-only')
                                    <flux:button variant="ghost" size="sm" icon="trash" class="text-red-500" wire:click="deleteReport('{{ $report->id }}')" />
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-zinc-500 italic">No se encontraron reportes.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">
            {{ $reports->links() }}
        </div>
    </div>

    @can('teacher-or-admin')
        <!-- Create/Edit Modal -->
        <flux:modal wire:model.self="showReportModal" class="md:w-160">
            <form wire:submit="save" class="space-y-6">
                <header>
                    <flux:heading size="lg">Registrar Reporte Disciplinario</flux:heading>
                    <flux:text>Complete los detalles de la incidencia académica o conductual.</flux:text>
                </header>

                <div class="space-y-4">
                    <!-- Student Search -->
                    <div class="relative">
                        <flux:input wire:model.live.debounce.300ms="studentSearch" label="Buscar Alumno (Nombre)" icon="user" placeholder="Escriba al menos 3 caracteres..." />
                        @if(count($studentResults) > 0)
                            <div class="absolute z-10 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg overflow-hidden">
                                @foreach($studentResults as $student)
                                    <button type="button" wire:click="selectStudent('{{ $student->id }}')" class="w-full text-left px-4 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-700 flex flex-col">
                                        <span class="font-bold text-sm">{{ $student->name }}</span>
                                        <span class="text-xs text-zinc-500">{{ $student->grade }}{{ $student->group_name }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        @if($selectedStudentId)
                            <div class="mt-2 flex items-center gap-2 text-green-600 dark:text-green-400 text-sm font-medium">
                                <flux:icon icon="check-circle" variant="micro" />
                                Alumno seleccionado correctamente.
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:input type="date" wire:model="reportDate" label="Fecha" />
                        <flux:input type="time" wire:model="reportTime" label="Hora" />
                    </div>

                    <flux:select label="Infracción (Reglamento)" wire:model="infractionId">
                        <option value="">Seleccione el tipo de falta...</option>
                        @foreach($infractions as $infraction)
                            <option value="{{ $infraction->id }}">{{ $infraction->description }}</option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="subject" label="Asunto / Materia (Opcional)" placeholder="Ej: Clase de Matemáticas, Receso..." />

                    <flux:textarea wire:model="description" label="Descripción de los hechos" placeholder="Detalle lo sucedido de forma objetiva..." rows="4" />
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button wire:click="$set('showReportModal', false)">Cancelar</flux:button>
                    <flux:button variant="primary" type="submit">Guardar Reporte</flux:button>
                </div>
            </form>
        </flux:modal>
    @endcan

    <!-- Notification logic (Toast) would go here or in layout -->
</div>
