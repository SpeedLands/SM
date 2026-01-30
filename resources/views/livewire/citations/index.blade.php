<?php

use App\Models\Citation;
use App\Models\Student;
use App\Models\Cycle;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    // Create Modal
    public bool $showCreateModal = false;
    public ?string $editingCitationId = null;
    public string $studentSearch = '';
    public ?string $selectedStudentId = null;
    public string $reason = '';
    public string $citationDate = '';
    public string $citationTime = '';

    public function mount(): void
    {
        $this->citationDate = now()->format('Y-m-d');
        $this->citationTime = '08:00';
        // Open create modal automatically when navigated with query params
        if (request()->query('open_create')) {
            $this->selectedStudentId = request()->query('student_id');
            $this->studentSearch = request()->query('student_name') ?? '';
            $this->showCreateModal = true;
        }
    }

    public function openCreateModal(): void
    {
        $this->authorize('teacher-or-admin');
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function resetForm(): void
    {
        $this->reset(['selectedStudentId', 'studentSearch', 'reason']);
        $this->citationDate = now()->format('Y-m-d');
        $this->citationTime = '08:00';
    }

    public function selectStudent(string $id): void
    {
        $this->selectedStudentId = $id;
        $this->studentSearch = Student::find($id)->name;
    }

    public function saveCitation(): void
    {
        $this->authorize('teacher-or-admin');
        $this->validate([
            'selectedStudentId' => 'required|exists:students,id',
            'reason' => 'required|string',
            'citationDate' => 'required|date|after_or_equal:today',
            'citationTime' => 'required',
        ], [
            'selectedStudentId.required' => 'Debe seleccionar un alumno.',
            'reason.required' => 'El motivo es obligatorio.',
        ]);

        if ($this->editingCitationId) {
            $citation = Citation::findOrFail($this->editingCitationId);
            $citation->update([
                'student_id' => $this->selectedStudentId,
                'reason' => $this->reason,
                'citation_date' => \Carbon\Carbon::parse($this->citationDate . ' ' . $this->citationTime),
            ]);
            $message = 'Citatorio actualizado correctamente.';
        } else {
            $activeCycle = Cycle::where('is_active', true)->first();
            if (!$activeCycle) {
                 $this->dispatch('notify', ['message' => 'No hay un ciclo activo.', 'variant' => 'danger']);
                 return;
            }
            
            Citation::create([
                'cycle_id' => $activeCycle->id,
                'student_id' => $this->selectedStudentId,
                'teacher_id' => auth()->id(),
                'reason' => $this->reason,
                'citation_date' => \Carbon\Carbon::parse($this->citationDate . ' ' . $this->citationTime),
                'status' => 'PENDING',
            ]);
            $message = 'Citatorio generado correctamente.';
        }

        $this->showCreateModal = false;
        $this->editingCitationId = null;
        $this->resetForm();
        $this->dispatch('notify', ['message' => $message]);
    }

    public function editCitation(string $id): void
    {
        $this->authorize('teacher-or-admin');
        $citation = Citation::findOrFail($id);
        
        $this->editingCitationId = $citation->id;
        $this->selectedStudentId = $citation->student_id;
        $this->studentSearch = $citation->student->name;
        $this->reason = $citation->reason;
        $this->citationDate = $citation->citation_date->format('Y-m-d');
        $this->citationTime = $citation->citation_date->format('H:i');
        
        $this->showCreateModal = true;
    }

    public function deleteCitation(string $id): void
    {
        $this->authorize('teacher-or-admin');
        Citation::findOrFail($id)->delete();
        $this->dispatch('notify', ['message' => 'Citatorio eliminado correctamente.']);
    }

    public function updateStatus(string $id, string $status): void
    {
        $this->authorize('teacher-or-admin');
        Citation::findOrFail($id)->update(['status' => $status]);
        $this->dispatch('notify', ['message' => 'Estado del citatorio actualizado.']);
    }

    public function signCitation(string $id): void
    {
        $this->authorize('parent-only');
        Citation::findOrFail($id)->update(['parent_signature' => true]);
        $this->dispatch('notify', ['message' => 'Citatorio firmado correctamente.']);
    }

    public function with(): array
    {
        $activeCycle = Cycle::where('is_active', true)->first();
        $user = auth()->user();
        $isStaff = $user->isAdmin() || $user->isTeacher();

        $query = Citation::with(['student', 'teacher'])
            ->when($activeCycle, fn($q) => $q->where('cycle_id', $activeCycle->id))
            ->orderBy('citation_date', 'asc');

        if ($isStaff) {
            $citations = $query->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
                ->when($this->search, function($q) {
                    $q->whereHas('student', fn($sq) => $sq->where('name', 'like', "%{$this->search}%"))
                      ->orWhere('reason', 'like', "%{$this->search}%");
                })
                ->paginate(10);
        } else {
            $studentIds = $user->students->pluck('id');
            $citations = $query->whereIn('student_id', $studentIds)->get();
        }

        return [
            'citations' => $citations,
            'isStaff' => $isStaff,
            'studentResults' => $isStaff && strlen($this->studentSearch) >= 3 && !$this->selectedStudentId
                ? Student::where('name', 'like', "%{$this->studentSearch}%")->limit(5)->get()
                : [],
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg" level="1">Citatorios a Padres</flux:heading>
            <flux:text class="text-zinc-500">Gestión de citas y reuniones presenciales.</flux:text>
        </div>
        @if($isStaff)
            <flux:button variant="primary" icon="calendar" wire:click="openCreateModal">Nuevo Citatorio</flux:button>
        @endif
    </div>

    @if($isStaff)
        <!-- Admin/Teacher View -->
        <div class="flex flex-col md:flex-row gap-4">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Buscar por alumno o motivo..." class="flex-1" />
            <flux:select wire:model.live="statusFilter" class="w-full md:w-64" placeholder="Todos los estados">
                <option value="">Todos los estados</option>
                <option value="PENDING">Pendientes</option>
                <option value="ATTENDED">Asistió</option>
                <option value="NO_SHOW">No asistió</option>
            </flux:select>
        </div>

        <div class="p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700 text-zinc-500">
                        <th class="py-3 px-2 font-semibold uppercase tracking-wider text-xs">Fecha y Hora</th>
                        <th class="py-3 px-2 font-semibold uppercase tracking-wider text-xs">Alumno</th>
                        <th class="py-3 px-2 font-semibold uppercase tracking-wider text-xs">Motivo</th>
                        <th class="py-3 px-2 font-semibold uppercase tracking-wider text-xs text-center">Firma Padre</th>
                        <th class="py-3 px-2 font-semibold uppercase tracking-wider text-xs text-center">Estado</th>
                        <th class="py-3 px-2 font-semibold uppercase tracking-wider text-xs text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($citations as $citation)
                        <tr wire:key="{{ $citation->id }}">
                            <td class="py-4 px-2">
                                <div class="font-medium">{{ $citation->citation_date->format('d/m/Y') }}</div>
                                <div class="text-xs text-zinc-500">{{ $citation->citation_date->format('H:i') }} hrs</div>
                            </td>
                            <td class="py-4 px-2">
                                <div class="font-bold">{{ $citation->student->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $citation->student->grade }}{{ $citation->student->group_name }}</div>
                            </td>
                            <td class="py-4 px-2">
                                <div class="font-medium max-w-xs truncate" title="{{ $citation->reason }}">{{ $citation->reason }}</div>
                                <div class="text-[10px] text-zinc-400">Generado por: {{ $citation->teacher->name }}</div>
                            </td>
                            <td class="py-4 px-2 text-center">
                                @if($citation->parent_signature)
                                    <flux:badge color="green" size="sm" inset="left" icon="check-badge">Firmado</flux:badge>
                                @else
                                    <flux:badge color="neutral" size="sm" inset="left" icon="clock">Pendiente</flux:badge>
                                @endif
                            </td>
                            <td class="py-4 px-2 text-center">
                                @if($citation->status === 'PENDING')
                                    <flux:badge color="amber" size="sm" inset="left">Agendado</flux:badge>
                                @elseif($citation->status === 'ATTENDED')
                                    <flux:badge color="green" size="sm" inset="left">Asistió</flux:badge>
                                @else
                                    <flux:badge color="red" size="sm" inset="left">No asistió</flux:badge>
                                @endif
                            </td>
                            <td class="py-4 px-2 text-right">
                                <div class="flex justify-end gap-1">
                                    @if($citation->status === 'PENDING')
                                        <flux:button variant="ghost" size="sm" icon="check-circle" class="text-green-600" title="Marcar asistencia" wire:click="updateStatus('{{ $citation->id }}', 'ATTENDED')" />
                                        <flux:button variant="ghost" size="sm" icon="x-circle" class="text-red-600" title="Marcar inasistencia" wire:click="updateStatus('{{ $citation->id }}', 'NO_SHOW')" />
                                        <flux:button variant="ghost" size="sm" icon="pencil" wire:click="editCitation('{{ $citation->id }}')" />
                                        <flux:button variant="ghost" size="sm" icon="trash" color="red" wire:click="deleteCitation('{{ $citation->id }}')" />
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-zinc-500 italic">No se encontraron citatorios.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">
                {{ $citations->links() }}
            </div>
        </div>
    @else
        <!-- Parent View -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @forelse($citations as $citation)
                <div wire:key="cit-{{ $citation->id }}" class="p-6 rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm relative">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center gap-3">
                            <flux:icon icon="calendar-days" class="text-blue-500" size="xl" />
                            <div>
                                <flux:heading level="3" size="md">Citatorio Escolar</flux:heading>
                                <flux:text size="sm" class="text-zinc-500">Para padre/tutor de: <strong>{{ $citation->student->name }}</strong></flux:text>
                            </div>
                        </div>
                        @if($citation->parent_signature)
                            <flux:badge color="green" variant="outline">Enterado</flux:badge>
                        @endif
                    </div>

                    <div class="space-y-4">
                        <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-100 dark:border-zinc-800">
                            <flux:text size="xs" class="uppercase font-bold text-zinc-500 mb-1">Motivo de la Cita</flux:text>
                            <flux:text class="text-zinc-700 dark:text-zinc-300 font-medium">{{ $citation->reason }}</flux:text>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-3 bg-blue-50/50 dark:bg-blue-900/10 rounded-xl border border-blue-50 dark:border-blue-900/20">
                                <flux:text size="xs" class="uppercase font-bold text-blue-600 dark:text-blue-400 mb-1">Fecha</flux:text>
                                <flux:text class="font-bold">{{ $citation->citation_date->format('d/m/Y') }}</flux:text>
                            </div>
                            <div class="p-3 bg-blue-50/50 dark:bg-blue-900/10 rounded-xl border border-blue-50 dark:border-blue-900/20">
                                <flux:text size="xs" class="uppercase font-bold text-blue-600 dark:text-blue-400 mb-1">Hora</flux:text>
                                <flux:text class="font-bold">{{ $citation->citation_date->format('H:i') }} hrs</flux:text>
                            </div>
                        </div>
                        
                        <flux:text size="sm" class="text-zinc-500 italic">Solicitado por: Prof(a). {{ $citation->teacher->name }}</flux:text>
                    </div>

                    @if(!$citation->parent_signature)
                        <div class="mt-6 pt-6 border-t border-zinc-100 dark:border-zinc-800">
                            <flux:button variant="primary" class="w-full py-4 shadow-lg shadow-blue-500/20" icon="finger-print" wire:click="signCitation('{{ $citation->id }}')">
                                Confirmar de Enterado
                            </flux:button>
                        </div>
                    @else
                        <div class="mt-6 pt-4 border-t border-zinc-100 dark:border-zinc-800 text-center">
                            <flux:text size="xs" color="green" class="font-bold">✓ USTED HA CONFIRMADO LA RECEPCIÓN DE ESTE CITATORIO</flux:text>
                        </div>
                    @endif
                </div>
            @empty
                <div class="md:col-span-2 py-20 text-center border border-dashed rounded-3xl border-zinc-300 dark:border-zinc-700">
                    <flux:icon icon="calendar" class="mx-auto text-zinc-300 mb-4" size="xl" />
                    <flux:heading size="md" class="text-zinc-500">No tiene citatorios pendientes</flux:heading>
                    <flux:text class="text-zinc-500">Agradecemos su compromiso con la educación de sus hijos.</flux:text>
                </div>
            @endforelse
        </div>
    @endif

    @if($isStaff)
        <!-- Create Modal -->
        <flux:modal wire:model.self="showCreateModal" class="md:w-160">
            <form wire:submit="saveCitation" class="space-y-6">
                <header>
                    <flux:heading size="md">{{ $editingCitationId ? 'Editar Citatorio' : 'Generar Citatorio' }}</flux:heading>
                    <flux:text>{{ $editingCitationId ? 'Modifique los detalles de la reunión presencial.' : 'Solicite una reunión presencial con los padres de familia.' }}</flux:text>
                </header>

                <div class="space-y-4">
                    <div class="relative">
                        <flux:input wire:model.live.debounce.300ms="studentSearch" label="Buscar Alumno" icon="user" placeholder="Nombre..." />
                        @if(count($studentResults) > 0)
                            <div class="absolute z-60 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg overflow-hidden">
                                @foreach($studentResults as $student)
                                    <button type="button" wire:click="selectStudent('{{ $student->id }}')" class="w-full text-left px-4 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-700 flex flex-col">
                                        <span class="font-bold text-sm">{{ $student->name }}</span>
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
                        <flux:input type="date" wire:model="citationDate" label="Fecha de la Cita" />
                        <flux:input type="time" wire:model="citationTime" label="Hora" />
                    </div>

                    <flux:textarea wire:model="reason" label="Motivo de la Cita" rows="4" placeholder="Ej: Revisión de desempeño académico, Comportamiento en clase..." />
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button wire:click="$set('showCreateModal', false)">Cancelar</flux:button>
                    <flux:button variant="primary" type="submit">{{ $editingCitationId ? 'Actualizar Citatorio' : 'Generar Citatorio' }}</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>