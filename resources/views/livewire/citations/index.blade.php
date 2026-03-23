<?php

use App\Models\Citation;
use App\Models\Student;
use App\Models\Cycle;
use App\Http\Requests\StoreCitationRequest;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public bool $onlyActiveCycle = true;

    // Create Modal
    public bool $showCreateModal = false;
    public bool $showDeleteModal = false;
    public ?string $editingCitationId = null;
    public ?string $deletingCitationId = null;
    public string $studentSearch = '';
    public ?string $selectedStudentId = null;
    public string $reason = '';
    public string $citationDate = '';
    public string $citationTime = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingOnlyActiveCycle(): void
    {
        $this->resetPage();
    }

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

        if ($search = request()->query('search')) {
            $this->search = $search;
        }
    }

    public function openCreateModal(): void
    {
        $this->authorize('teacher-or-admin');
        $this->resetValidation();
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
        $request = new StoreCitationRequest();
        $this->validate($request->rules(), $request->messages(), $request->attributes());

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

            $citation = Citation::create([
                'cycle_id' => $activeCycle->id,
                'student_id' => $this->selectedStudentId,
                'teacher_id' => auth()->id(),
                'reason' => $this->reason,
                'citation_date' => \Carbon\Carbon::parse($this->citationDate . ' ' . $this->citationTime),
                'status' => 'PENDING',
            ]);

            // Notify parents via FCM asíncronamente (Hallazgo #3 y #6)
            $student = Student::with('parents')->find($this->selectedStudentId);
            $parentIds = $student->parents->pluck('id')->toArray();

            if (!empty($parentIds)) {
                \App\Jobs\SendBulkFcmNotifications::dispatch(
                    $parentIds,
                    'Nuevo Citatorio Escolar',
                    "Se ha generado un citatorio para los padres de {$student->name}.",
                    [],
                    route('citations.index')
                );
            }

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
        $this->resetValidation();
        $citation = Citation::findOrFail($id);

        $this->editingCitationId = $citation->id;
        $this->selectedStudentId = $citation->student_id;
        $this->studentSearch = $citation->student->name;
        $this->reason = $citation->reason;
        $this->citationDate = $citation->citation_date->format('Y-m-d');
        $this->citationTime = $citation->citation_date->format('H:i');

        $this->showCreateModal = true;
    }

    public function confirmDelete(string $id): void
    {
        $this->authorize('teacher-or-admin');
        $this->deletingCitationId = $id;
        $this->showDeleteModal = true;
    }

    public function deleteCitation(): void
    {
        $this->authorize('teacher-or-admin');
        if ($this->deletingCitationId) {
            Citation::findOrFail($this->deletingCitationId)->delete();
            $this->showDeleteModal = false;
            $this->deletingCitationId = null;
            $this->dispatch('notify', ['message' => 'Citatorio eliminado correctamente.']);
        }
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
        $this->dispatch('navigation-refresh');
        $this->dispatch('notify', ['message' => 'Citatorio firmado correctamente.']);
    }

    public function with(): array
    {
        $activeCycle = Cycle::where('is_active', true)->first();
        $user = auth()->user();
        $isStaff = $user->isViewStaff();

        $query = Citation::with(['student', 'teacher'])
            ->when($this->onlyActiveCycle && $activeCycle, fn($q) => $q->where('cycle_id', $activeCycle->id))
            ->orderBy('citation_date', 'asc');

        if ($isStaff) {
            $citations = $query->select('citations.*')
                ->when($this->statusFilter, fn($q) => $q->where('citations.status', $this->statusFilter))
                ->when($this->search, function ($q) {
                    $q->join('students', 'citations.student_id', '=', 'students.id')
                        ->where(function ($sq) {
                            $sq->where('students.name', 'like', "%{$this->search}%")
                                ->orWhere('citations.reason', 'like', "%{$this->search}%");
                        });
                })
                ->paginate(10);
        } else {
            $studentIds = $user->students->pluck('id');
            $citations = $query->whereIn('student_id', $studentIds)->paginate(10);
        }

        return [
            'citations' => $citations,
            'isStaff' => $isStaff,
            'user' => $user,
            'studentResults' => $isStaff && strlen($this->studentSearch) >= 3 && !$this->selectedStudentId
                ? Student::where('name', 'like', "%{$this->studentSearch}%")->limit(5)->get()
                : [],
        ];
    }
}; ?>

<div class="space-y-6" x-data="{ showFilters: false }">
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
    {{-- Filtros Rápidos (Pills for mobile style) --}}
    <div class="flex flex-wrap gap-2 sm:hidden pb-2 overflow-x-auto no-scrollbar">
        @if($search) <flux:badge variant="solid" color="zinc" class="shrink-0">"{{ $search }}"</flux:badge> @endif
        @if($statusFilter)
        <flux:badge variant="solid" color="zinc" class="shrink-0">
            {{ match($statusFilter) { 'PENDING' => 'Pendiente', 'ATTENDED' => 'Asistió', 'NO_SHOW' => 'Inasistencia', default => $statusFilter } }}
        </flux:badge>
        @endif
        @if($onlyActiveCycle) <flux:badge variant="solid" color="zinc" class="shrink-0">Ciclo Activo</flux:badge> @endif
        <flux:button variant="ghost" size="xs" icon="funnel" class="ml-auto" title="Mostrar/ocultar filtros" x-on:click="showFilters = !showFilters" />
    </div>

    <!-- Admin/Teacher View -->
    <div x-show="showFilters" class="sm:block! p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm transition-all mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <flux:field class="md:col-span-2">
                <flux:label>Búsqueda</flux:label>
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Buscar por alumno o motivo..." />
            </flux:field>

            <flux:field>
                <flux:label>Estado</flux:label>
                <flux:select wire:model.live="statusFilter">
                    <option value="">Todos los estados</option>
                    <option value="PENDING">Pendientes</option>
                    <option value="ATTENDED">Asistió</option>
                    <option value="NO_SHOW">No asistió</option>
                </flux:select>
            </flux:field>

            <div class="flex items-center gap-2 h-10 mb-0.5">
                <flux:switch wire:model.live="onlyActiveCycle" label="Solo ciclo activo" />
            </div>
        </div>
    </div>

    <!-- Mobile Cards (Staff View) -->
    <div class="space-y-4 sm:hidden pb-10">
        @forelse($citations as $citation)
        <div wire:key="cit-mob-{{ $citation->id }}" class="p-4 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm relative">
            <div class="flex justify-between items-start mb-3">
                <div class="flex flex-col">
                    <flux:text size="sm" class="font-bold">{{ $citation->student->name }}</flux:text>
                    <flux:text size="xs" class="text-zinc-500">{{ $citation->student->grade }}{{ $citation->student->group_name }}</flux:text>
                </div>
                <div class="flex flex-col items-end">
                    <flux:text size="xs" class="font-medium text-blue-600 dark:text-blue-400">{{ $citation->citation_date->format('d/m/Y') }}</flux:text>
                    <flux:text size="xs" class="text-zinc-500">{{ $citation->citation_date->format('H:i') }} hrs</flux:text>
                </div>
            </div>

            <div class="mb-4 bg-zinc-50 dark:bg-zinc-800/50 p-3 rounded-lg border border-zinc-100 dark:border-zinc-800 text-xs text-zinc-700 dark:text-zinc-300">
                <flux:text size="xs" class="font-bold uppercase text-[9px] text-zinc-400 mb-1">Motivo:</flux:text>
                <div class="line-clamp-2">{{ $citation->reason }}</div>
                @if($citation->teacher)
                <div class="text-[10px] text-zinc-400 mt-1">Generado por: {{ $citation->teacher->name }}</div>
                @endif
            </div>

            <div class="flex justify-between items-center mt-4 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                <div class="flex gap-2">
                    @if($citation->status === 'PENDING')
                    <flux:badge color="amber" size="xs">Agendado</flux:badge>
                    @elseif($citation->status === 'ATTENDED')
                    <flux:badge color="green" size="xs">Asistió</flux:badge>
                    @else
                    <flux:badge color="red" size="xs">Inasistencia</flux:badge>
                    @endif

                    @if($citation->parent_signature)
                    <flux:badge color="zinc" variant="outline" size="xs" icon="check-badge">Firmado</flux:badge>
                    @endif
                </div>

                <div class="flex gap-1">
                    @if($citation->status === 'PENDING')
                    <flux:button variant="ghost" size="xs" icon="check-circle" class="text-green-600" title="Marcar asistencia" wire:click="updateStatus('{{ $citation->id }}', 'ATTENDED')" />
                    <flux:button variant="ghost" size="xs" icon="x-circle" class="text-red-600" title="Marcar inasistencia" wire:click="updateStatus('{{ $citation->id }}', 'NO_SHOW')" />
                    <flux:button variant="ghost" size="xs" icon="pencil" wire:click="editCitation('{{ $citation->id }}')" title="Editar citatorio" />
                    <flux:button variant="ghost" size="xs" icon="trash" color="red" wire:click="confirmDelete('{{ $citation->id }}')" title="Eliminar citatorio" />
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="py-12 text-center text-zinc-500 italic bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-dashed border-zinc-300">
            No se encontraron citatorios.
        </div>
        @endforelse
        <div class="mt-4">
            {{ $citations->links() }}
        </div>
    </div>

    <!-- Desktop Table (Staff View) -->
    <div class="hidden sm:block p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm overflow-x-auto">
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
                <tr wire:key="cit-desk-{{ $citation->id }}">
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
                        <flux:badge color="red" size="sm" inset="left">Inasistencia</flux:badge>
                        @endif
                    </td>
                    <td class="py-4 px-2 text-right">
                        <div class="flex justify-end gap-1">
                            @if($citation->status === 'PENDING')
                            <flux:button variant="ghost" size="sm" icon="check-circle" class="text-green-600" title="Marcar asistencia" wire:click="updateStatus('{{ $citation->id }}', 'ATTENDED')" />
                            <flux:button variant="ghost" size="sm" icon="x-circle" class="text-red-600" title="Marcar inasistencia" wire:click="updateStatus('{{ $citation->id }}', 'NO_SHOW')" />
                            <flux:button variant="ghost" size="sm" icon="pencil" wire:click="editCitation('{{ $citation->id }}')" title="Editar citatorio" />
                            <flux:button variant="ghost" size="sm" icon="trash" color="red" wire:click="confirmDelete('{{ $citation->id }}')" title="Eliminar citatorio" />
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
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:hidden">
        @forelse($citations as $citation)
        <div wire:key="cit-{{ $citation->id }}" class="p-6 rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm relative">
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center gap-3">
                    <flux:icon icon="calendar-days" class="text-blue-500" size="xl" />
                    <div class="whitespace-normal pr-4">
                        <flux:heading level="3" size="md">Citatorio Escolar</flux:heading>
                        <flux:text size="sm" class="text-zinc-500">Para padre/tutor de: <strong class="wrap-break-word">{{ $citation->student->name }}</strong></flux:text>
                    </div>
                </div>
                @if($citation->parent_signature)
                <flux:badge color="green" variant="outline">Enterado</flux:badge>
                @endif
            </div>

            <div class="space-y-4">
                <div class="p-4 bg-zinc-50 dark:bg-zinc-800 rounded-xl border border-zinc-100 dark:border-zinc-800">
                    <flux:text size="xs" class="uppercase font-bold text-zinc-500 mb-1">Motivo de la Cita</flux:text>
                    <flux:text class="text-zinc-700 dark:text-zinc-300 font-medium whitespace-normal wrap-break-word">{{ $citation->reason }}</flux:text>
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

            @if($user->isViewParent() && !$citation->parent_signature)
            <div class="mt-6 pt-6 border-t border-zinc-100 dark:border-zinc-800">
                <flux:button variant="primary" class="w-full py-4 shadow-lg shadow-blue-500/20" icon="finger-print" wire:click="signCitation('{{ $citation->id }}')">
                    Confirmar de Enterado
                </flux:button>
            </div>
            @elseif($citation->parent_signature)
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
        <div class="mt-4">
            {{ $citations->links() }}
        </div>
    </div>

    <!-- Desktop Table (Parent View) -->
    <div class="hidden sm:block p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700 text-zinc-500">
                    <th class="py-3 px-2 font-semibold uppercase tracking-wider text-xs">Fecha y Hora</th>
                    <th class="py-3 px-2 font-semibold uppercase tracking-wider text-xs">Alumno</th>
                    <th class="py-3 px-2 font-semibold uppercase tracking-wider text-xs">Motivo</th>
                    <th class="py-3 px-2 font-semibold uppercase tracking-wider text-xs text-center">Estado</th>
                    <th class="py-3 px-2 font-semibold uppercase tracking-wider text-xs text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($citations as $citation)
                <tr wire:key="cit-par-desk-{{ $citation->id }}">
                    <td class="py-4 px-2">
                        <div class="font-medium">{{ $citation->citation_date->format('d/m/Y') }}</div>
                        <div class="text-xs text-zinc-500">{{ $citation->citation_date->format('H:i') }} hrs</div>
                    </td>
                    <td class="py-4 px-2">
                        <div class="font-bold">{{ $citation->student->name }}</div>
                    </td>
                    <td class="py-4 px-2">
                        <div class="font-medium max-w-xs truncate" title="{{ $citation->reason }}">{{ $citation->reason }}</div>
                        <div class="text-[10px] text-zinc-400">Solicitado por: {{ $citation->teacher->name }}</div>
                    </td>
                    <td class="py-4 px-2 text-center">
                        @if($citation->status === 'PENDING')
                        <flux:badge color="amber" size="sm" inset="left">Agendado</flux:badge>
                        @elseif($citation->status === 'ATTENDED')
                        <flux:badge color="green" size="sm" inset="left">Asistió</flux:badge>
                        @else
                        <flux:badge color="red" size="sm" inset="left">Inasistencia</flux:badge>
                        @endif

                        @if($citation->parent_signature)
                        <div class="mt-1">
                            <flux:badge color="green" size="sm" inset="left" icon="check-badge">Enterado</flux:badge>
                        </div>
                        @endif
                    </td>
                    <td class="py-4 px-2 text-right">
                        @if(auth()->user()->isViewParent() && !$citation->parent_signature)
                        <flux:button variant="primary" size="sm" icon="finger-print" wire:click="signCitation('{{ $citation->id }}')">
                            Confirmar
                        </flux:button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-12 text-center text-zinc-500 italic">No tiene citatorios pendientes.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">
            {{ $citations->links() }}
        </div>
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
                    <flux:field>
                        <flux:label>Buscar Alumno</flux:label>
                        <flux:input wire:model.live.debounce.300ms="studentSearch" icon="user" placeholder="Nombre..." autofocus />
                        <flux:error name="selectedStudentId" />
                    </flux:field>
                    @if(count($studentResults) > 0)
                    <div class="absolute z-60 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg overflow-hidden">
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
                    <flux:field>
                        <flux:label>Fecha de la Cita</flux:label>
                        <flux:input type="date" wire:model="citationDate" />
                        <flux:error name="citationDate" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Hora</flux:label>
                        <flux:input type="time" wire:model="citationTime" />
                        <flux:error name="citationTime" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Motivo de la Cita</flux:label>
                    <flux:textarea wire:model="reason" rows="4" placeholder="Ej: Revisión de desempeño académico, Comportamiento en clase..." />
                    <flux:error name="reason" />
                </flux:field>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button wire:click="$set('showCreateModal', false)">Cancelar</flux:button>
                <flux:button variant="primary" type="submit">{{ $editingCitationId ? 'Actualizar Citatorio' : 'Generar Citatorio' }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Delete Confirmation Modal -->
    <flux:modal name="delete-citation" wire:model="showDeleteModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">¿Eliminar citatorio?</flux:heading>
                <flux:text class="mt-2 text-zinc-500">Esta acción no se puede deshacer. Los padres del alumno ya no podrán ver este citatorio.</flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button wire:click="$set('showDeleteModal', false)">Cancelar</flux:button>
                <flux:button variant="danger" wire:click="deleteCitation">Eliminar</flux:button>
            </div>
        </div>
    </flux:modal>
    @endif
</div>