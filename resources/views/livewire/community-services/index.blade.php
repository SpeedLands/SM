<?php

use App\Models\CommunityService;
use App\Models\Student;
use App\Models\Cycle;
use App\Models\Report;
use App\Http\Requests\StoreCommunityServiceRequest;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public bool $onlyActiveCycle = true;
    
    // Modal state
    public bool $showServiceModal = false;
    
    // Form fields
    public string $studentSearch = '';
    public ?string $selectedStudentId = null;
    public string $activity = '';
    public string $description = '';
    public string $scheduledDate = '';
    
    // Deletion
    public bool $showDeleteModal = false;
    public ?string $idToDelete = null;
    public ?string $editingServiceId = null;

    public function updatingOnlyActiveCycle(): void
    {
        $this->resetPage();
    }

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

    public function editService(string $id): void
    {
        $this->authorize('teacher-or-admin');
        
        $service = CommunityService::findOrFail($id);
        $this->editingServiceId = $service->id;
        $this->selectedStudentId = $service->student_id;
        $this->studentSearch = $service->student->name;
        $this->activity = $service->activity;
        $this->description = $service->description ?? '';
        $this->scheduledDate = Carbon::parse($service->scheduled_date)->format('Y-m-d');
        
        $this->showServiceModal = true;
    }

    public function save(): void
    {
        $this->authorize('teacher-or-admin');
        $request = new StoreCommunityServiceRequest();
        $this->validate($request->rules(), $request->messages(), $request->attributes());

        $activeCycle = Cycle::where('is_active', true)->first();
        
        if (!$activeCycle) {
            $this->dispatch('notify', ['message' => 'No hay un ciclo activo.', 'variant' => 'danger']);
            return;
        }

        $data = [
            'cycle_id' => $activeCycle->id,
            'student_id' => $this->selectedStudentId,
            'activity' => $this->activity,
            'description' => $this->description,
            'scheduled_date' => $this->scheduledDate,
        ];

        if ($this->editingServiceId) {
            $service = CommunityService::findOrFail($this->editingServiceId);
            $service->update($data);
            $message = 'Servicio comunitario actualizado.';
        } else {
            $data['assigned_by_id'] = auth()->id();
            $data['status'] = 'PENDING';
            $service = CommunityService::create($data);
            $message = 'Servicio comunitario asignado.';

            // Notify parents via FCM asíncronamente (Hallazgo #3 y #6)
            $student = Student::with('parents')->find($this->selectedStudentId);
            $parentIds = $student->parents->pluck('id')->toArray();
            
            if (!empty($parentIds)) {
                \App\Jobs\SendBulkFcmNotifications::dispatch(
                    $parentIds,
                    'Nuevo Servicio Comunitario Asignado',
                    "Se ha asignado una actividad de servicio comunitario para {$student->name}: {$this->activity}.",
                    [],
                    route('community-services.index')
                );
            }
        }

        $this->showServiceModal = false;
        $this->resetForm();
        $this->editingServiceId = null;
        $this->dispatch('notify', ['message' => $message]);
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

    public function updateStatus(string $id, string $status): void
    {
        $this->authorize('teacher-or-admin');
        
        $service = CommunityService::findOrFail($id);
        
        $data = ['status' => $status];
        
        if ($status === 'COMPLETED') {
            $data['completed_at'] = now();
            $data['authority_signature_id'] = auth()->id();
        }

        $service->update($data);
        
        $this->dispatch('notify', ['message' => 'Estado del servicio actualizado.']);
    }

    public function confirmDelete(string $id): void
    {
        $this->authorize('teacher-or-admin');
        $this->idToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('teacher-or-admin');
        if (!$this->idToDelete) return;

        $service = CommunityService::findOrFail($this->idToDelete);
        $service->delete();

        $this->idToDelete = null;
        $this->showDeleteModal = false;
        $this->dispatch('notify', ['message' => 'Servicio comunitario eliminado.']);
    }

    public function with(): array
    {
        $activeCycle = Cycle::where('is_active', true)->first();

        $services = CommunityService::with(['student', 'assignedBy'])
            ->select('community_services.*')
            ->join('students', 'community_services.student_id', '=', 'students.id')
            ->when(auth()->user()->isViewParent(), function ($q) {
                $q->join('student_parents', 'students.id', '=', 'student_parents.student_id')
                  ->where('student_parents.parent_id', auth()->id());
            })
            ->when($this->onlyActiveCycle && $activeCycle, fn($q) => $q->where('community_services.cycle_id', $activeCycle->id))
            ->when($this->statusFilter, fn($q) => $q->where('community_services.status', $this->statusFilter))
            ->when($this->search, function($q) {
                $q->where(function($sq) {
                    $sq->where('students.name', 'like', "%{$this->search}%")
                      ->orWhere('community_services.activity', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('community_services.scheduled_date', 'asc')
            ->paginate(10);

        // Suggestions logic
        $suggestedStudents = [];
        if ($activeCycle && auth()->user()->isViewStaff()) {
            $suggestedStudents = Student::whereHas('reports', function ($query) use ($activeCycle) {
                    $query->where('cycle_id', $activeCycle->id);
                }, '>=', 3)
                ->withCount(['reports' => function ($query) use ($activeCycle) {
                    $query->where('cycle_id', $activeCycle->id);
                }])
                ->get()
                ->filter(function ($student) use ($activeCycle) {
                    $servicesCount = CommunityService::where('student_id', $student->id)
                        ->where('cycle_id', $activeCycle->id)
                        ->count();
                    return $servicesCount < floor($student->reports_count / 3);
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

<div class="space-y-6" x-data="{ showFilters: false }">
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

    {{-- Filtros Rápidos (Pills for mobile style) --}}
    <div class="flex flex-wrap gap-2 sm:hidden pb-2 overflow-x-auto no-scrollbar">
        @if($search) <flux:badge variant="solid" color="zinc" class="shrink-0">"{{ $search }}"</flux:badge> @endif
        @if($statusFilter) 
            <flux:badge variant="solid" color="zinc" class="shrink-0">
                {{ match($statusFilter) { 'PENDING' => 'Pendiente', 'COMPLETED' => 'Completado', 'MISSED' => 'Incumplido', default => $statusFilter } }}
            </flux:badge> 
        @endif
        @if($onlyActiveCycle) <flux:badge variant="solid" color="zinc" class="shrink-0">Ciclo Activo</flux:badge> @endif
        <flux:button variant="ghost" size="xs" icon="funnel" class="ml-auto" title="Mostrar/ocultar filtros" x-on:click="showFilters = !showFilters" />
    </div>

    <!-- Filters -->
    <div x-show="showFilters" class="sm:block! p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm transition-all mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <flux:field class="md:col-span-2">
                <flux:label>Búsqueda</flux:label>
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Buscar alumno o actividad..." />
            </flux:field>

            <flux:field>
                <flux:label>Estado</flux:label>
                <flux:select wire:model.live="statusFilter">
                    <option value="">Todos los estados</option>
                    <option value="PENDING">Pendientes</option>
                    <option value="COMPLETED">Completados</option>
                    <option value="MISSED">No asistió</option>
                </flux:select>
            </flux:field>

            <div class="flex items-center gap-2 h-10 mb-0.5">
                <flux:switch wire:model.live="onlyActiveCycle" label="Solo ciclo activo" />
            </div>
        </div>
    </div>

    @if(auth()->user()->isViewStaff())
        <!-- Services Table (Staff View) -->
        <!-- Mobile Cards (Staff View) -->
        <div class="space-y-4 sm:hidden pb-10">
            @forelse($services as $service)
                <div wire:key="svc-mob-{{ $service->id }}" class="p-4 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm relative">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex flex-col">
                            <flux:text size="sm" class="font-bold">{{ $service->student->name }}</flux:text>
                            <flux:text size="xs" class="text-zinc-500">{{ $service->student->grade }}{{ $service->student->group_name }}</flux:text>
                        </div>
                        <div class="flex flex-col items-end">
                            <flux:text size="xs" class="font-medium text-indigo-600 dark:text-indigo-400">{{ $service->scheduled_date->format('d/m/Y') }}</flux:text>
                            <flux:text size="xs" class="text-zinc-500 italic">{{ $service->scheduled_date->diffForHumans() }}</flux:text>
                        </div>
                    </div>

                    <div class="mb-4 bg-blue-50/30 dark:bg-zinc-800/50 p-3 rounded-lg border border-blue-50 dark:border-zinc-800 text-xs text-zinc-700 dark:text-zinc-300">
                        <flux:text size="xs" class="font-bold uppercase text-[9px] text-zinc-400 mb-1">Actividad:</flux:text>
                        <div class="font-medium mb-1">{{ $service->activity }}</div>
                        <div class="line-clamp-2 italic text-zinc-500">{{ $service->description }}</div>
                        @if($service->assignedBy)
                            <div class="text-[10px] text-zinc-400 mt-1">Asignado por: {{ $service->assignedBy->name }}</div>
                        @endif
                    </div>

                    <div class="flex justify-between items-center mt-4 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                        <div class="flex gap-2">
                             @if($service->status === 'PENDING')
                                <flux:badge color="amber" size="xs">Pendiente</flux:badge>
                            @elseif($service->status === 'COMPLETED')
                                <flux:badge color="green" size="xs">Completado</flux:badge>
                            @else
                                <flux:badge color="red" size="xs">Incumplido</flux:badge>
                            @endif
                        </div>

                        <div class="flex gap-1">
                            @if($service->status === 'PENDING')
                                <flux:button variant="ghost" size="xs" icon="check-circle" class="text-green-600" title="Marcar como cumplido" wire:click="updateStatus('{{ $service->id }}', 'COMPLETED')" />
                                <flux:button variant="ghost" size="xs" icon="x-circle" class="text-red-600" title="Marcar como no asistió" wire:click="updateStatus('{{ $service->id }}', 'MISSED')" />
                                <flux:button variant="ghost" size="xs" icon="pencil" title="Editar servicio" wire:click="editService('{{ $service->id }}')" />
                            @endif
                            <flux:button variant="ghost" size="xs" icon="trash" class="text-red-500" title="Eliminar servicio" wire:click="confirmDelete('{{ $service->id }}')" />
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-zinc-500 italic bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-dashed border-zinc-300">
                    No hay servicios programados.
                </div>
            @endforelse
            <div class="mt-4">
                {{ $services->links() }}
            </div>
        </div>

        <!-- Desktop Table (Staff View) -->
        <div class="hidden sm:block p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm overflow-x-auto">
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
                        <tr wire:key="svc-desk-{{ $service->id }}">
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
                                @if($service->assignedBy)
                                    <div class="text-[10px] text-zinc-400">Asignado por: {{ $service->assignedBy->name }}</div>
                                @endif
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
                                        @if($service->status === 'PENDING')
                                            <flux:button variant="ghost" size="sm" icon="check-circle" class="text-green-600" title="Marcar como cumplido" wire:click="updateStatus('{{ $service->id }}', 'COMPLETED')" />
                                            <flux:button variant="ghost" size="sm" icon="x-circle" class="text-red-600" title="Marcar como no asistió" wire:click="updateStatus('{{ $service->id }}', 'MISSED')" />
                                            <flux:button variant="ghost" size="sm" icon="pencil" title="Editar servicio" wire:click="editService('{{ $service->id }}')" />
                                        @endif
                                        <flux:button variant="ghost" size="sm" icon="trash" class="text-red-500" title="Eliminar servicio" wire:click="confirmDelete('{{ $service->id }}')" />
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
    @else
        <!-- Parent View: Feed style (Modern Cards) -->
        <div class="space-y-6 max-w-3xl mx-auto sm:hidden">
            @forelse ($services as $service)
                <div wire:key="svc-{{ $service->id }}" class="p-6 rounded-2xl border {{ !$service->parent_signature ? 'border-blue-200 bg-white shadow-lg' : 'border-zinc-200 bg-zinc-50/50' }} dark:border-zinc-700 dark:bg-zinc-900 relative transition-all hover:shadow-xl group">
                    
                    <div class="flex justify-between items-start mb-6">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-linear-to-br from-indigo-500 to-indigo-700 flex items-center justify-center text-white font-black text-xl shadow-inner uppercase">
                                {{ substr($service->student->name, 0, 1) }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <flux:text size="xs" class="uppercase tracking-widest font-black text-zinc-400">Asignado a:</flux:text>
                                    <span class="px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-[10px] font-bold">{{ $service->student->name }}</span>
                                </div>
                                <flux:heading level="3" size="lg" class="mt-0.5">{{ $service->activity }}</flux:heading>
                            </div>
                        </div>
                        <div class="text-right">
                             <flux:text size="xs" class="text-zinc-500 block">{{ $service->scheduled_date->format('d M, Y') }}</flux:text>
                             <flux:text size="xs" class="text-zinc-400 italic">{{ $service->scheduled_date->diffForHumans() }}</flux:text>
                        </div>
                    </div>

                    @if($service->description)
                        <div class="prose prose-zinc dark:prose-invert max-w-none text-zinc-700 dark:text-zinc-300 bg-blue-50/50 dark:bg-zinc-800/50 p-4 rounded-xl border border-blue-100 dark:border-zinc-800 italic leading-relaxed mb-6">
                            "{{ $service->description }}"
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-4 items-center">
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">
                            <flux:icon icon="user" variant="micro" />
                            <flux:text size="xs" class="font-medium">Asignado por: {{ $service->assignedBy->name }}</flux:text>
                        </div>
                        
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg {{ $service->status === 'COMPLETED' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' }}">
                            <flux:icon icon="{{ $service->status === 'COMPLETED' ? 'check-circle' : 'clock' }}" variant="micro" />
                            <flux:text size="xs" class="font-bold uppercase tracking-tighter">Estado: {{ $service->status === 'COMPLETED' ? 'Completado' : ($service->status === 'PENDING' ? 'Pendiente' : 'Incumplido') }}</flux:text>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-zinc-100 dark:border-zinc-800">
                        @if(!$service->parent_signature)
                            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                                    <flux:icon icon="information-circle" variant="micro" />
                                    <flux:text size="sm" class="font-medium text-inherit">Requiere su firma de enterado</flux:text>
                                </div>
                                <flux:button variant="primary" icon="finger-print" class="w-full sm:w-auto px-10 shadow-lg shadow-blue-500/30" wire:click="signService('{{ $service->id }}')">
                                    Firmar de Enterado
                                </flux:button>
                            </div>
                        @else
                            <div class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/10 rounded-xl border border-green-100 dark:border-green-800/30">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center text-white shadow-md">
                                        <flux:icon icon="check" variant="micro" />
                                    </div>
                                    <div>
                                        <flux:text size="sm" class="font-bold text-green-800 dark:text-green-200 uppercase tracking-tight">Servicio Firmado</flux:text>
                                        <flux:text size="xs" class="text-green-700/60 dark:text-green-400/60 font-medium">
                                            @if($service->parent_signed_at)
                                                Enterado el {{ $service->parent_signed_at->format('d/m/Y H:i') }}
                                            @else
                                                Firma registrada
                                            @endif
                                        </flux:text>
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
                        <flux:icon icon="star" size="xl" />
                    </div>
                    <flux:heading size="md" class="text-zinc-400">Sin servicios comunitarios</flux:heading>
                    <flux:text class="text-zinc-500">No hay actividades de servicio programadas para sus hijos.</flux:text>
                </div>
            @endforelse
            <div class="mt-4">
                {{ $services->links() }}
            </div>
        </div>

        <!-- Desktop Table (Parent View) -->
        <div class="hidden sm:block p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm overflow-x-auto">
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
                        <tr wire:key="svc-par-desk-{{ $service->id }}">
                            <td class="py-4 px-2">
                                <div class="font-medium">{{ $service->scheduled_date->format('d/m/Y') }}</div>
                                <div class="text-xs text-zinc-500">{{ $service->scheduled_date->diffForHumans() }}</div>
                            </td>
                            <td class="py-4 px-2">
                                <div class="font-bold">{{ $service->student->name }}</div>
                            </td>
                            <td class="py-4 px-2">
                                <div class="font-medium">{{ $service->activity }}</div>
                                <div class="text-[10px] text-zinc-400">Asignado por: {{ $service->assignedBy->name }}</div>
                            </td>
                            <td class="py-4 px-2 text-center">
                                @if($service->status === 'PENDING')
                                    <flux:badge color="amber" size="sm" inset="left">Pendiente</flux:badge>
                                @elseif($service->status === 'COMPLETED')
                                    <flux:badge color="green" size="sm" inset="left">Completado</flux:badge>
                                @else
                                    <flux:badge color="red" size="sm" inset="left">Incumplido</flux:badge>
                                @endif
                                
                                @if($service->parent_signature)
                                    <div class="mt-1">
                                        <flux:badge color="green" size="sm" inset="left" icon="check-badge">Firmado</flux:badge>
                                    </div>
                                @endif
                            </td>
                            <td class="py-4 px-2 text-right">
                                @if(!$service->parent_signature)
                                    <flux:button variant="primary" size="sm" icon="finger-print" wire:click="signService('{{ $service->id }}')">
                                        Firmar
                                    </flux:button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-zinc-500 italic">No hay actividades de servicio programadas para sus hijos.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">
                {{ $services->links() }}
            </div>
        </div>
    @endif

    <!-- Assignment Modal -->
    <flux:modal wire:model.self="showServiceModal" class="md:w-160">
        <form wire:submit="save" class="space-y-6">
            <header>
                <flux:heading size="lg">{{ $editingServiceId ? 'Editar Servicio Comunitario' : 'Asignar Servicio Comunitario' }}</flux:heading>
                <flux:text>{{ $editingServiceId ? 'Actualice la actividad y fecha para el cumplimiento del servicio.' : 'Defina la actividad y fecha para el cumplimiento del servicio.' }}</flux:text>
            </header>

            <div class="space-y-4">
                <div class="relative">
                    <flux:input wire:model.live.debounce.300ms="studentSearch" label="Buscar Alumno" icon="user" placeholder="Nombre..." autofocus />
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
                <flux:button variant="primary" type="submit">{{ $editingServiceId ? 'Actualizar Servicio' : 'Asignar Servicio' }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Deletion Confirmation Modal -->
    <flux:modal wire:model.self="showDeleteModal" class="min-w-80">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Confirmar Eliminación</flux:heading>
                <flux:subheading>
                    ¿Estás seguro de eliminar este registro de servicio comunitario? Esta acción no se puede deshacer.
                </flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Cancelar</flux:button>
                <flux:button variant="danger" wire:click="delete">Eliminar</flux:button>
            </div>
        </div>
    </flux:modal>
</div>