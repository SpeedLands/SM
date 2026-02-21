<?php

use App\Http\Requests\StoreReportRequest;
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
    public bool $onlyPending = false;
    
    // Modal state
    public bool $showReportModal = false;
    public bool $showDeleteModal = false;
    public ?Report $editingReport = null;
    public ?string $reportIdToDelete = null;
    
    // Form fields
    public string $studentSearch = '';
    public ?string $selectedStudentId = null;
    public ?string $infractionId = null;
    public string $subject = '';
    public string $description = '';
    public string $reportDate = '';
    public string $reportTime = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingSeverity(): void
    {
        $this->resetPage();
    }

    public function updatingOnlyPending(): void
    {
        $this->resetPage();
    }

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

        if (now()->isWeekend()) {
            $this->dispatch('notify', ['message' => 'No se pueden registrar reportes los fines de semana.', 'variant' => 'warning']);
            return;
        }

        $request = new StoreReportRequest();
        $this->validate($request->rules(), $request->messages(), $request->attributes());

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

        // Notify parents via FCM asíncronamente (Hallazgo #3 y #6)
        $student = Student::with('parents')->find($this->selectedStudentId);
        $infraction = Infraction::find($this->infractionId);
        $parentIds = $student->parents->pluck('id')->toArray();
        
        if (!empty($parentIds)) {
            \App\Jobs\SendBulkFcmNotifications::dispatch(
                $parentIds,
                'Nuevo Reporte Disciplinario',
                "Se ha registrado un reporte para {$student->name}: {$infraction->description}",
                [],
                route('reports.index')
            );
        }

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

    public function confirmDelete(string $id): void
    {
        $this->authorize('admin-only');
        $this->reportIdToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteReport(): void
    {
        $this->authorize('admin-only');
        
        if (!$this->reportIdToDelete) {
            return;
        }

        $report = Report::findOrFail($this->reportIdToDelete);

        if ($report->status === 'SIGNED') {
            $this->dispatch('notify', ['message' => 'No se puede eliminar un reporte que ya ha sido firmado.', 'variant' => 'danger']);
            $this->showDeleteModal = false;
            $this->reportIdToDelete = null;
            return;
        }

        $report->delete();
        $this->showDeleteModal = false;
        $this->reportIdToDelete = null;
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

        $this->dispatch('navigation-refresh');
        $this->dispatch('notify', ['message' => 'Reporte firmado correctamente.']);
    }

    public function updatingStudentSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $activeCycle = Cycle::where('is_active', true)->first();

        $reports = Report::with(['student', 'teacher', 'infraction', 'parent'])
            ->select('reports.*')
            ->join('students', 'reports.student_id', '=', 'students.id')
            ->when(auth()->user()->isViewParent(), function ($q) {
                $q->join('student_parents', 'reports.student_id', '=', 'student_parents.student_id')
                  ->where('student_parents.parent_id', auth()->id());
            })
            ->when($activeCycle, fn($q) => $q->where('reports.cycle_id', $activeCycle->id))
            ->when($this->status, fn($q) => $q->where('reports.status', $this->status))
            ->when($this->severity, function($q) {
                $q->join('infractions', 'reports.infraction_id', '=', 'infractions.id')
                  ->where('infractions.severity', $this->severity);
            })
            ->when($this->onlyPending, fn($q) => $q->where('reports.status', 'PENDING_SIGNATURE'))
            ->when($this->search, function($q) {
                $q->where(function($sq) {
                    $sq->where('students.name', 'like', "%{$this->search}%")
                      ->orWhere('reports.subject', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('reports.date', 'desc')
            ->paginate(10);

        $studentResults = [];
        if (strlen($this->studentSearch) >= 3 && !$this->selectedStudentId) {
            $studentResults = Student::where('name', 'like', "%{$this->studentSearch}%")
                ->limit(5)
                ->get();
        }

        // Cache infractions for 1 hour to avoid repeated DB calls on every render
        $infractions = cache()->remember('infractions_all', 3600, fn() => Infraction::orderBy('description')->get());

        return [
            'reports' => $reports,
            'infractions' => $infractions,
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
        @if(auth()->user()->isViewStaff())
            <div class="flex flex-col sm:flex-row gap-2">
                @can('admin-only')
                    <flux:button variant="ghost" icon="cog-6-tooth" href="{{ route('infractions.index') }}" wire:navigate>Gestionar Tipos</flux:button>
                @endcan
                <flux:button variant="primary" icon="plus-circle" wire:click="openCreateModal">Nuevo Reporte</flux:button>
            </div>
        @endif
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

        @if(auth()->user()->isViewParent())
            <div class="flex items-center gap-2 px-2">
                <flux:checkbox wire:model.live="onlyPending" label="Solo pendientes" />
            </div>
        @endif
    </div>

    @if(auth()->user()->isViewStaff())
        <!-- Reports Table (Staff View) -->
        <div class="p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="py-3 px-2 font-semibold">Fecha</th>
                        <th class="py-3 px-2 font-semibold">Alumno</th>
                        <th class="py-3 px-2 font-semibold">Infracción / Asunto</th>
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
                                <div class="font-medium text-blue-600 dark:text-blue-400 whitespace-normal">{{ $report->infraction->description }}</div>
                                @if($report->subject)
                                    <div class="text-xs font-semibold uppercase mt-1 whitespace-normal">Asunto: {{ $report->subject }}</div>
                                @endif
                                <div class="text-xs text-zinc-500 whitespace-normal line-clamp-2 italic">{{ $report->description }}</div>
                            </td>
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
                                    @if(auth()->user()->isAdmin())
                                        <flux:button variant="ghost" size="sm" icon="trash" class="text-red-500" wire:click="confirmDelete('{{ $report->id }}')" />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-zinc-500 italic">No se encontraron reportes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">
                {{ $reports->links() }}
            </div>
        </div>
    @else
        <!-- Parent View: Feed style (Modern Cards) -->
        <div class="space-y-6 max-w-3xl mx-auto">
            @forelse ($reports as $report)
                <div wire:key="rep-{{ $report->id }}" class="p-6 rounded-2xl border {{ $report->status === 'SIGNED' ? 'border-zinc-200 bg-zinc-50/50' : 'border-amber-200 bg-white shadow-lg' }} dark:border-zinc-700 dark:bg-zinc-900 relative transition-all hover:shadow-xl group">
                    @if($report->status !== 'SIGNED' && $report->infraction->severity === 'GRAVE')
                        <div class="absolute -top-3 -right-3">
                            <flux:badge color="red" size="sm" class="animate-pulse shadow-md">Falta Grave</flux:badge>
                        </div>
                    @endif

                    <div class="flex justify-between items-start mb-6">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-linear-to-br from-zinc-500 to-zinc-700 flex items-center justify-center text-white font-black text-xl shadow-inner uppercase">
                                {{ substr($report->student->name, 0, 1) }}
                            </div>
                            <div class="whitespace-normal">
                                <div class="flex items-center gap-2">
                                    <flux:text size="xs" class="uppercase tracking-widest font-black text-zinc-400 shrink-0">Reporte de:</flux:text>
                                    <span class="px-2 py-0.5 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-[10px] font-bold wrap-break-word">{{ $report->student->name }}</span>
                                </div>
                                <flux:heading level="3" size="lg" class="mt-0.5 whitespace-normal">{{ $report->infraction->description }}</flux:heading>
                            </div>
                        </div>
                        <div class="text-right">
                             <flux:text size="xs" class="text-zinc-500 block">{{ $report->date->format('d M, Y') }}</flux:text>
                             <flux:text size="xs" class="text-zinc-400">{{ $report->date->format('H:i') }} hrs</flux:text>
                        </div>
                    </div>

                    @if($report->subject)
                        <div class="mb-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 uppercase">
                                Asunto: {{ $report->subject }}
                            </span>
                        </div>
                    @endif

                    <div class="prose prose-zinc dark:prose-invert max-w-none text-zinc-700 dark:text-zinc-300 bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-xl border border-zinc-100 dark:border-zinc-800 italic leading-relaxed">
                        "{{ $report->description }}"
                    </div>

                    <div class="mt-8 pt-6 border-t border-zinc-100 dark:border-zinc-800">
                        @if($report->status !== 'SIGNED')
                            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div class="flex items-center gap-2 text-amber-600 dark:text-amber-400">
                                    <flux:icon icon="information-circle" variant="micro" />
                                    <flux:text size="sm" class="font-medium text-inherit">Requiere su firma de enterado</flux:text>
                                </div>
                                <flux:button variant="primary" icon="finger-print" class="w-full sm:w-auto px-10 shadow-lg shadow-amber-500/30" wire:click="signReport('{{ $report->id }}')">
                                    Firmar Reporte
                                </flux:button>
                            </div>
                        @else
                            <div class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/10 rounded-xl border border-green-100 dark:border-green-800/30">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center text-white shadow-md">
                                        <flux:icon icon="check" variant="micro" />
                                    </div>
                                    <div>
                                        <flux:text size="sm" class="font-bold text-green-800 dark:text-green-200 uppercase tracking-tight">Reporte Firmado / Enterado</flux:text>
                                        <flux:text size="xs" class="text-green-700/60 dark:text-green-400/60 font-medium">Registrado el {{ $report->signed_at->format('d/m/Y H:i') }}</flux:text>
                                    </div>
                                </div>
                                <flux:icon icon="shield-check" class="text-green-200 dark:text-green-800" size="xl" />
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-20 text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-300 dark:text-zinc-600 mb-4">
                        <flux:icon icon="check-circle" size="xl" />
                    </div>
                    <flux:heading size="md" class="text-zinc-400">Sin reportes registrados</flux:heading>
                    <flux:text class="text-zinc-500">No se han encontrado incidencias disciplinarias para sus hijos en este ciclo.</flux:text>
                </div>
            @endforelse
            <div class="mt-4">
                {{ $reports->links() }}
            </div>
        </div>
    @endif

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

    @can('admin-only')
        <!-- Delete Confirmation Modal -->
        <flux:modal wire:model.self="showDeleteModal" class="min-w-88">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">¿Eliminar reporte?</flux:heading>
                    <flux:text class="mt-2">
                        Esta acción no se puede deshacer. El registro del reporte será eliminado permanentemente.
                    </flux:text>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="deleteReport">Eliminar Reporte</flux:button>
                </div>
            </div>
        </flux:modal>
    @endcan

    <!-- Notification logic (Toast) would go here or in layout -->
</div>
