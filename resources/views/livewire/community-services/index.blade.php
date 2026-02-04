<?php

use App\Models\CommunityService;
use App\Models\Student;
use App\Models\Cycle;
use App\Models\Report;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    
    // Modal state
    public bool $showServiceModal = false;
    
    // Form fields
    public string $studentSearch = '';
    public ?string $selectedStudentId = null;
    public string $activity = '';
    public string $description = '';
    public string $scheduledDate = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        $this->scheduledDate = now()->format('Y-m-d');
        // Open create modal automatically when navigated with query params
        if (request()->query('open_create')) {
            $this->selectedStudentId = request()->query('student_id');
            $this->studentSearch = request()->query('student_name') ?? '';
            $this->showServiceModal = true;
        }
    }

    public function openCreateModal(?string $studentId = null): void
    {
        $this->authorize('teacher-or-admin');
        $this->resetForm();
        if ($studentId) {
            $this->selectStudent($studentId);
        }
        $this->showServiceModal = true;
    }

    public function resetForm(): void
    {
        $this->reset(['selectedStudentId', 'studentSearch', 'activity', 'description']);
        $this->scheduledDate = now()->format('Y-m-d');
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
            'activity' => 'required|string|max:255',
            'scheduledDate' => [
                'required',
                'date',
                'after_or_equal:today',
                function ($attribute, $value, $fail) {
                    if (Carbon::parse($value)->isSunday()) {
                        $fail('No se permite programar servicio comunitario los domingos.');
                    }
                },
            ],
        ], [
            'selectedStudentId.required' => 'Debe seleccionar un alumno.',
            'activity.required' => 'La actividad es obligatoria.',
            'scheduledDate.after_or_equal' => 'La fecha debe ser hoy o posterior.',
        ]);

        $activeCycle = Cycle::where('is_active', true)->first();
        
        if (!$activeCycle) {
            $this->dispatch('notify', ['message' => 'No hay un ciclo activo.', 'variant' => 'danger']);
            return;
        }

        CommunityService::create([
            'cycle_id' => $activeCycle->id,
            'student_id' => $this->selectedStudentId,
            'assigned_by_id' => auth()->id(),
            'activity' => $this->activity,
            'description' => $this->description,
            'scheduled_date' => $this->scheduledDate,
            'status' => 'PENDING',
        ]);

        $this->showServiceModal = false;
        $this->resetForm();
        $this->dispatch('notify', ['message' => 'Servicio comunitario asignado.']);
    }

    public function signService(string $id): void
    {
        $this->authorize('parent-only');
        $service = CommunityService::findOrFail($id);
        
        // Ensure the service belongs to one of the parent's students
        $parentStudentIds = auth()->user()->students->pluck('id')->toArray();
        if (!in_array($service->student_id, $parentStudentIds)) {
            abort(403, 'No tiene permiso para firmar este registro.');
        }

        $service->update([
            'parent_signature' => true,
            'parent_signed_at' => now(),
        ]);
        
        $this->dispatch('navigation-refresh');
        $this->dispatch('notify', ['message' => 'Servicio firmado correctamente.']);
    }

    public function with(): array
    {
        $activeCycle = Cycle::where('is_active', true)->first();

        $services = CommunityService::with(['student', 'assignedBy'])
            ->when(auth()->user()->isViewParent(), function ($q) {
                $q->whereHas('student.parents', function ($pq) {
                    $pq->where('users.id', auth()->id());
                });
            })
            ->when($activeCycle, fn($q) => $q->where('cycle_id', $activeCycle->id))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, function($q) {
                $q->whereHas('student', fn($sq) => $sq->where('name', 'like', "%{$this->search}%"))
                  ->orWhere('activity', 'like', "%{$this->search}%");
            })
            ->orderBy('scheduled_date', 'asc')
            ->paginate(10);

        // Suggestions logic
        $suggestedStudents = [];
        if ($activeCycle && auth()->user()->isViewStaff()) {
            $suggestedStudents = Student::whereHas('reports', function($q) use ($activeCycle) {
                $q->where('cycle_id', $activeCycle->id);
            })
            ->get()
            ->filter(function($student) use ($activeCycle) {
                $reportsCount = Report::where('student_id', $student->id)->where('cycle_id', $activeCycle->id)->count();
                if ($reportsCount < 3) return false;
                
                // Check if they already have enough services assigned
                $servicesCount = CommunityService::where('student_id', $student->id)->where('cycle_id', $activeCycle->id)->count();
                return $servicesCount < floor($reportsCount / 3);
            });
        }

        $studentResults = [];
        if (strlen($this->studentSearch) >= 3 && !$this->selectedStudentId) {
            $studentResults = Student::where('name', 'like', "%{$this->studentSearch}%")
                ->limit(5)
                ->get();
        }

        return [
            'services' => $services,
            'suggestedStudents' => $suggestedStudents,
            'studentResults' => $studentResults,
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">Servicio Comunitario</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">Asignación y seguimiento de actividades reparatorias.</flux:text>
        </div>
        @if(auth()->user()->isViewStaff())
            <flux:button variant="primary" icon="plus" wire:click="openCreateModal()">Asignar Servicio</flux:button>
        @endif
    </div>

    <!-- Suggested Actions (Alert-like) -->
    @if(count($suggestedStudents) > 0)
        <div class="p-4 rounded-xl border border-blue-200 bg-blue-50 dark:border-blue-900/30 dark:bg-blue-900/20 shadow-sm">
            <div class="flex items-start gap-3">
                <flux:icon icon="information-circle" class="text-blue-600 dark:text-blue-400 mt-0.5" />
                <div class="flex-1">
                    <flux:heading level="3" size="sm" class="text-blue-900 dark:text-blue-100 font-bold">Sugerencias de Asignación</flux:heading>
                    <flux:text size="sm" class="text-blue-800 dark:text-blue-300 mt-1">
                        Los siguientes alumnos han acumulado reportes suficientes (3+) para considerar una asignación de servicio comunitario:
                    </flux:text>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($suggestedStudents as $student)
                            <flux:button size="xs" variant="filled" class="bg-blue-600! text-white!" wire:click="openCreateModal('{{ $student->id }}')">
                                {{ $student->name }}
                            </flux:button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Filters -->
    <div class="flex flex-col md:flex-row gap-4">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Buscar alumno o actividad..." class="flex-1" />
        <flux:select wire:model.live="statusFilter" class="w-full md:w-64">
            <option value="">Todos los estados</option>
            <option value="PENDING">Pendientes</option>
            <option value="COMPLETED">Completados</option>
            <option value="MISSED">No asistió</option>
        </flux:select>
    </div>

    <!-- Services Table -->
    <div class="p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700 text-zinc-500">
                    <th class="py-3 px-2 font-semibold uppercase tracking-wider text-xs">Fecha Programada</th>
                    <th class="py-3 px-2 font-semibold uppercase tracking-wider text-xs">Alumno</th>
                    <th class="py-3 px-2 font-semibold uppercase tracking-wider text-xs">Actividad</th>
                    <th class="py-3 px-2 font-semibold uppercase tracking-wider text-xs text-center">Estado</th>
                    <th class="py-3 px-2 font-semibold uppercase tracking-wider text-xs text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($services as $service)
                    <tr wire:key="{{ $service->id }}">
                        <td class="py-4 px-2">
                            <div class="font-medium">{{ $service->scheduled_date->format('d/m/Y') }}</div>
                            <div class="text-xs text-zinc-500 italic">{{ $service->scheduled_date->diffForHumans() }}</div>
                        </td>
                        <td class="py-4 px-2">
                            <div class="font-bold">{{ $service->student->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $service->student->grade }}{{ $service->student->group_name }}</div>
                        </td>
                        <td class="py-4 px-2">
                            <div class="font-medium">{{ $service->activity }}</div>
                            <div class="text-xs text-zinc-500 line-clamp-1 italic">{{ $service->description }}</div>
                        </td>
                        <td class="py-4 px-2 text-center">
                            @if($service->status === 'PENDING')
                                <flux:badge color="amber" size="sm" inset="left">Pendiente</flux:badge>
                            @elseif($service->status === 'COMPLETED')
                                <flux:badge color="green" size="sm" inset="left">Completado</flux:badge>
                            @else
                                <flux:badge color="red" size="sm" inset="left">Incumplido</flux:badge>
                            @endif
                        </td>
                        <td class="py-4 px-2 text-right">
                            <div class="flex justify-end gap-1">
                                @if(auth()->user()->isViewStaff())
                                    @if($service->status === 'PENDING')
                                        <flux:button variant="ghost" size="sm" icon="check-circle" class="text-green-600" title="Marcar como cumplido" wire:click="updateStatus('{{ $service->id }}', 'COMPLETED')" />
                                        <flux:button variant="ghost" size="sm" icon="x-circle" class="text-red-600" title="Marcar como no asistió" wire:click="updateStatus('{{ $service->id }}', 'MISSED')" />
                                    @endif
                                @elseif(auth()->user()->isViewParent())
                                    @if(!$service->parent_signature)
                                        <flux:button variant="primary" size="sm" icon="finger-print" wire:click="signService('{{ $service->id }}')">Firmar</flux:button>
                                    @else
                                        <flux:badge color="green" size="sm" inset="left" icon="check">Enterado</flux:badge>
                                    @endif
                                @endif

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-zinc-500 italic">No hay servicios programados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">
            {{ $services->links() }}
        </div>
    </div>

    <!-- Assignment Modal -->
    <flux:modal wire:model.self="showServiceModal" class="md:w-160">
        <form wire:submit="save" class="space-y-6">
            <header>
                <flux:heading size="lg">Asignar Servicio Comunitario</flux:heading>
                <flux:text>Defina la actividad y fecha para el cumplimiento del servicio.</flux:text>
            </header>

            <div class="space-y-4">
                <div class="relative">
                    <flux:input wire:model.live.debounce.300ms="studentSearch" label="Buscar Alumno" icon="user" placeholder="Nombre..." />
                    @if(count($studentResults) > 0)
                        <div class="absolute z-10 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg overflow-hidden">
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

                <flux:input wire:model="activity" label="Actividad" placeholder="Ej: Limpieza de áreas verdes, Apoyo en biblioteca..." />

                <flux:textarea wire:model="description" label="Instrucciones adicionales" placeholder="Opcional..." rows="3" />

                <flux:input type="date" wire:model="scheduledDate" label="Fecha de Cumplimiento" description="Disponible de lunes a sábado. No se permite programar los domingos." />
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button wire:click="$set('showServiceModal', false)">Cancelar</flux:button>
                <flux:button variant="primary" type="submit">Asignar Servicio</flux:button>
            </div>
        </form>
    </flux:modal>
</div>