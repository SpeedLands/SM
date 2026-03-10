<?php

use App\Models\Cycle;
use App\Models\ClassGroup;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $name = '';
    public string $start_date = '';
    public string $end_date = '';
    public bool $is_active = false;
    public string $search = '';
    
    public ?Cycle $editing = null;

    // Group Management state
    public bool $showGroupsModal = false;
    public ?Cycle $groupCycle = null;
    public string $grade = '';
    public string $section = '';
    public string $tutorId = '';
    public ?ClassGroup $editingGroup = null;

    // Deletion State
    public ?Cycle $cycleToDelete = null;
    public ?ClassGroup $groupToDelete = null;
    public bool $showDeleteGroupModal = false;
    public bool $showDeleteCycleModal = false;

    protected $rules = [
        'name' => 'required|string|max:50',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after:start_date',
        'is_active' => 'boolean',
    ];

    protected $messages = [
        'name.required' => 'El nombre del ciclo es obligatorio.',
        'start_date.required' => 'La fecha de inicio es obligatoria.',
        'end_date.required' => 'La fecha de fin es obligatoria.',
        'end_date.after' => 'La fecha de fin debe ser posterior a la de inicio.',
        'grade.required' => 'El grado es obligatorio.',
        'section.required' => 'La sección es obligatoria.',
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin() && auth()->user()->isViewStaff(), 403);
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editing) {
            if ($validated['is_active']) {
                Cycle::where('id', '!=', $this->editing->id)->update(['is_active' => false]);
            }
            $this->editing->update($validated);
            $this->editing = null;
        } else {
            if ($validated['is_active']) {
                Cycle::query()->update(['is_active' => false]);
            }
            Cycle::create($validated);
        }

        $this->reset(['name', 'start_date', 'end_date', 'is_active']);
        $this->dispatch('cycle-saved');
    }

    public function edit(Cycle $cycle): void
    {
        $this->editing = $cycle;
        $this->name = $cycle->name;
        $this->start_date = $cycle->start_date->format('Y-m-d');
        $this->end_date = $cycle->end_date->format('Y-m-d');
        $this->is_active = $cycle->is_active;
    }

    public function cancel(): void
    {
        $this->editing = null;
        $this->reset(['name', 'start_date', 'end_date', 'is_active']);
    }

    public function confirmDelete(Cycle $cycle): void
    {
        $this->cycleToDelete = $cycle;
        $this->showDeleteCycleModal = true;
    }

    public function delete(): void
    {
        if (!$this->cycleToDelete) return;

        if ($this->cycleToDelete->is_active) {
            $this->dispatch('notify', ['message' => 'No se puede eliminar el ciclo activo.', 'variant' => 'danger']);
            $this->showDeleteCycleModal = false;
            return;
        }

        if ($this->cycleToDelete->groups()->exists() || $this->cycleToDelete->reports()->exists() || $this->cycleToDelete->notices()->exists() || $this->cycleToDelete->citations()->exists()) {
            $this->dispatch('notify', ['message' => 'No se puede eliminar un ciclo que tiene registros asociados (grupos, reportes, avisos, etc).', 'variant' => 'danger']);
            $this->showDeleteCycleModal = false;
            return;
        }

        $this->cycleToDelete->delete();
        $this->cycleToDelete = null;
        $this->showDeleteCycleModal = false;
        $this->dispatch('cycle-saved');
    }

    // Group Management Methods
    public function openGroupsModal(string $cycleId): void
    {
        $this->groupCycle = Cycle::findOrFail($cycleId);
        $this->showGroupsModal = true;
    }

    public function saveGroup(): void
    {
        $this->validate([
            'grade' => [
                'required',
                'string',
                Rule::unique('class_groups', 'grade')
                    ->where('section', $this->section)
                    ->where('cycle_id', $this->groupCycle->id)
                    ->ignore($this->editingGroup?->id),
            ],
            'section' => 'required|string',
            'tutorId' => 'nullable|exists:users,id',
        ], [
            'grade.unique' => 'Este grupo (grado y sección) ya está registrado en este ciclo.',
        ]);

        if ($this->editingGroup) {
            $this->editingGroup->update([
                'grade' => $this->grade,
                'section' => $this->section,
                'tutor_teacher_id' => $this->tutorId ?: null,
            ]);
            $this->editingGroup = null;
        } else {
            ClassGroup::create([
                'id' => (string) Str::uuid(),
                'cycle_id' => $this->groupCycle->id,
                'grade' => $this->grade,
                'section' => $this->section,
                'tutor_teacher_id' => $this->tutorId ?: null,
            ]);
        }

        $this->reset(['grade', 'section', 'tutorId']);
        $this->groupCycle->load('groups');
    }

    public function editGroup(ClassGroup $group): void
    {
        $this->editingGroup = $group;
        $this->grade = $group->grade;
        $this->section = $group->section;
        $this->tutorId = (string) $group->tutor_teacher_id;
    }

    public function cancelGroupEdit(): void
    {
        $this->editingGroup = null;
        $this->reset(['grade', 'section', 'tutorId']);
    }

    public function confirmDeleteGroup(string $id): void
    {
        $this->groupToDelete = ClassGroup::findOrFail($id);
        $this->showDeleteGroupModal = true;
    }

    public function deleteGroup(): void
    {
        if (!$this->groupToDelete) return;
        
        // Check if group has students
        if ($this->groupToDelete->studentCycleAssociations()->exists()) {
            $this->dispatch('notify', ['message' => 'No se puede eliminar un grupo que tiene alumnos inscritos.', 'variant' => 'danger']);
            $this->showDeleteGroupModal = false;
            return;
        }

        $cycleId = $this->groupToDelete->cycle_id;
        $this->groupToDelete->delete();
        $this->groupToDelete = null;
        $this->showDeleteGroupModal = false;
        
        if ($this->groupCycle && $this->groupCycle->id === $cycleId) {
            $this->groupCycle->load('groups');
        }
    }

    public function with(): array
    {
        $cycles = Cycle::query()
            ->withCount(['groups', 'reports', 'notices', 'citations'])
            ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
            ->orderBy('start_date', 'desc')
            ->paginate(10);

        $activeCycle = Cycle::where('is_active', true)->first();
        $totalCycles = Cycle::count();

        return [
            'cycles' => $cycles,
            'activeCycle' => $activeCycle,
            'totalCycles' => $totalCycles,
            'teachers' => User::where('role', 'TEACHER')->get(),
            'currentGroups' => $this->groupCycle 
                ? ClassGroup::with('tutor')
                    ->withCount('studentCycleAssociations')
                    ->where('cycle_id', $this->groupCycle->id)
                    ->get() 
                : collect(),
        ];
    }
}; ?>

<div class="space-y-6 text-zinc-900 dark:text-white">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">Gestión de Ciclos Escolares</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">Administra los periodos académicos y define el año activo.</flux:text>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm flex flex-col justify-between">
            <div>
                <flux:text class="uppercase text-xs font-semibold tracking-wider text-zinc-500 dark:text-zinc-400">Ciclo Activo</flux:text>
                <flux:heading size="xl" class="mt-2">{{ $activeCycle->name ?? 'Ninguno' }}</flux:heading>
            </div>
            <div class="mt-4 flex items-center gap-2">
                @if($activeCycle)
                    <flux:badge color="green" size="sm" inset="left">
                        <flux:icon icon="check-circle" variant="micro" class="mr-1" />
                        En curso
                    </flux:badge>
                @else
                    <flux:text size="sm" class="italic">No hay ciclo activo</flux:text>
                @endif
            </div>
        </div>

        <div class="p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm">
            <flux:text class="uppercase text-xs font-semibold tracking-wider text-zinc-500 dark:text-zinc-400">Total Registrados</flux:text>
            <flux:heading size="xl" class="mt-2">{{ $totalCycles }}</flux:heading>
            <flux:text class="mt-4 text-xs">Periodos históricos</flux:text>
        </div>

        <div class="p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm">
            <flux:text class="uppercase text-xs font-semibold tracking-wider text-zinc-500 dark:text-zinc-400">Estado del Sistema</flux:text>
            <flux:heading size="xl" class="mt-2">{{ $activeCycle ? 'En Operación' : 'Inactivo' }}</flux:heading>
            <flux:text class="mt-4 text-xs">Monitoreo de disponibilidad</flux:text>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Register Form -->
        <div class="lg:col-span-1 p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm self-start">
            <flux:heading size="lg" level="2" class="flex items-center gap-2 mb-6">
                <flux:icon icon="plus-circle" />
                {{ $editing ? 'Editar Ciclo' : 'Registrar Nuevo Ciclo' }}
            </flux:heading>

            <form wire:submit="save" class="space-y-4">
                <flux:input wire:model="name" :label="__('Nombre del Ciclo')" placeholder="Ej: 2024-2025" autofocus />

                <flux:input wire:model="start_date" type="date" :label="__('Fecha de Inicio')" />

                <flux:input wire:model="end_date" type="date" :label="__('Fecha de Fin')" />

                <div class="flex items-center justify-between py-2">
                    <div>
                        <flux:text weight="medium" class="text-sm">Ciclo Activo</flux:text>
                        <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">Será el ciclo predeterminado</flux:text>
                    </div>
                    <flux:switch wire:model="is_active" />
                </div>

                <div class="flex gap-2 pt-4">
                    <flux:button class="flex-1" wire:click="cancel">Cancelar</flux:button>
                    <flux:button variant="primary" type="submit" class="flex-1" icon="check">
                        {{ $editing ? 'Actualizar' : 'Guardar' }}
                    </flux:button>
                </div>
            </form>
        </div>

        <!-- Cycles List -->
        <div class="lg:col-span-2 p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm">
            <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <flux:heading size="lg" level="2">Lista de Ciclos</flux:heading>
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Buscar..." class="w-full sm:w-64" />
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="py-3 px-2 font-semibold text-zinc-900 dark:text-white">Nombre</th>
                            <th class="py-3 px-2 font-semibold text-zinc-900 dark:text-white">Periodo</th>
                            <th class="py-3 px-2 font-semibold text-zinc-900 dark:text-white">Estado</th>
                            <th class="py-3 px-2 text-right font-semibold text-zinc-900 dark:text-white">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($cycles as $cycle)
                            <tr wire:key="{{ $cycle->id }}">
                                <td class="py-4 px-2 font-bold">{{ $cycle->name }}</td>
                                <td class="py-4 px-2 text-zinc-600 dark:text-zinc-400">
                                    {{ \Carbon\Carbon::parse($cycle->start_date)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($cycle->end_date)->format('M d, Y') }}
                                </td>
                                <td class="py-4 px-2">
                                    @php
                                        $remaining = (int) now()->diffInDays($cycle->end_date);
                                        $until = (int) now()->diffInDays(\Carbon\Carbon::parse($cycle->start_date));
                                    @endphp
                                    @if($cycle->is_active)
                                        <flux:badge color="green" size="sm" inset="left">Activo</flux:badge>
                                        <flux:text class="text-xs text-zinc-500">Días restantes: {{ $remaining }}</flux:text>
                                    @elseif(\Carbon\Carbon::parse($cycle->start_date) > now())
                                        <flux:badge color="blue" size="sm" inset="left">Planificado</flux:badge>
                                        <flux:text class="text-xs text-zinc-500">Inicia en: {{ $until }} días</flux:text>
                                    @else
                                        <flux:badge color="neutral" size="sm" inset="left">Cerrado</flux:badge>
                                    @endif
                                </td>
                                <td class="py-4 px-2 text-right">
                                    <div class="flex justify-end gap-1">
                                        <flux:button variant="ghost" size="sm" icon="users" title="Gestionar grupos" wire:click="openGroupsModal('{{ $cycle->id }}')" />
                                        <flux:button variant="ghost" size="sm" icon="pencil" title="Editar ciclo" wire:click="edit({{ $cycle->id }})" />
                                        @if($cycle->groups_count === 0 && $cycle->reports_count === 0 && $cycle->notices_count === 0 && $cycle->citations_count === 0)
                                            <flux:button variant="ghost" size="sm" icon="trash" title="Eliminar ciclo" wire:click="confirmDelete('{{ $cycle->id }}')" />
                                        @else
                                            <flux:button variant="ghost" size="sm" icon="trash" class="text-zinc-300 dark:text-zinc-600" title="No se puede eliminar por registros asociados" disabled />
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $cycles->links() }}
            </div>
        </div>
    </div>

    <!-- Groups Modal -->
    <flux:modal wire:model.self="showGroupsModal" class="md:w-160">
        <div class="space-y-6">
            <header>
                <flux:heading size="lg">Grupos del Ciclo: {{ $groupCycle?->name }}</flux:heading>
                <flux:text>Administra los grupos asignados a este periodo académico.</flux:text>
            </header>

            <form wire:submit="saveGroup" class="space-y-3 bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <flux:select label="Grado" wire:model="grade" class="w-full">
                            <option value="">Grado...</option>
                            <option value="1º">1º Secundaria</option>
                            <option value="2º">2º Secundaria</option>
                            <option value="3º">3º Secundaria</option>
                        </flux:select>
                    </div>
                    <div>
                        <flux:select label="Sección" wire:model="section" class="w-full">
                            <option value="">Sección...</option>
                            @foreach(['A', 'B', 'C', 'D', 'G', 'H', 'I'] as $letter)
                                <option value="{{ $letter }}">{{ $letter }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div>
                        <flux:select label="Tutor" wire:model="tutorId" class="w-full">
                            <option value="">Seleccione tutor...</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
                <div class="flex gap-2 pt-1">
                    <flux:button variant="primary" type="submit" icon="{{ $editingGroup ? 'check' : 'plus' }}" class="flex-1">
                        {{ $editingGroup ? 'Actualizar Grupo' : 'Añadir Grupo' }}
                    </flux:button>
                    @if($editingGroup)
                        <flux:button type="button" wire:click="cancelGroupEdit" variant="ghost" icon="x-mark">
                            Cancelar
                        </flux:button>
                    @endif
                </div>
            </form>

            <div class="space-y-3">
                <flux:heading size="sm">Grupos Registrados</flux:heading>
                <div class="max-h-96 overflow-y-auto pr-2 divide-y divide-zinc-100 dark:divide-zinc-800 border rounded-lg border-zinc-200 dark:border-zinc-700">
                    @forelse($currentGroups as $group)
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between p-3 bg-white dark:bg-zinc-900 gap-3" wire:key="group-{{ $group->id }}">
                            <div class="flex items-center gap-4 min-w-0">
                                <div class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-bold rounded text-lg shrink-0">{{ $group->grade }} {{ $group->section }}</div>
                                <div class="min-w-0">
                                    <div class="text-[10px] text-zinc-400 dark:text-zinc-500 font-bold uppercase tracking-wider mb-0.5">Asesor</div>
                                    <div class="text-sm font-medium text-zinc-900 dark:text-white leading-tight wrap-break-word" title="Asesor: {{ $group->tutor?->name ?? 'No asignado' }}">
                                        {{ $group->tutor?->name ?? 'No asignado' }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <flux:button variant="ghost" size="sm" icon="pencil" title="Editar grupo" wire:click="editGroup('{{ $group->id }}')" />
                                @if($group->student_cycle_associations_count === 0)
                                    <flux:button variant="ghost" size="sm" icon="trash" class="text-red-500" title="Eliminar grupo" wire:click="confirmDeleteGroup('{{ $group->id }}')" />
                                @else
                                    <flux:button variant="ghost" size="sm" icon="trash" class="text-zinc-300 dark:text-zinc-600" title="No se puede eliminar porque tiene alumnos" disabled />
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center bg-zinc-50 dark:bg-zinc-800/20 italic text-zinc-500">
                            No hay grupos registrados para este ciclo.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button wire:click="$set('showGroupsModal', false)">Cerrar</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Deletion Confirmation Modal: Cycle -->
    <flux:modal wire:model="showDeleteCycleModal" class="min-w-80">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Confirmar Eliminación de Ciclo</flux:heading>
                <flux:subheading>
                    ¿Estás seguro de eliminar el ciclo escolar: <span class="font-bold text-zinc-900 dark:text-white uppercase">{{ $cycleToDelete?->name }}</span>?
                    Esta acción no se puede deshacer.
                </flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="$set('showDeleteCycleModal', false)">Cancelar</flux:button>
                <flux:button variant="danger" wire:click="delete">Eliminar Ciclo</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Deletion Confirmation Modal: Group -->
    <flux:modal wire:model="showDeleteGroupModal" class="min-w-80">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Confirmar Eliminación de Grupo</flux:heading>
                <flux:subheading>
                    ¿Estás seguro de eliminar el grupo <span class="font-bold text-zinc-900 dark:text-white font-mono">{{ $groupToDelete?->grade }} {{ $groupToDelete?->section }}</span>?
                    Esta acción solo es posible si el grupo no tiene alumnos registrados.
                </flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="$set('showDeleteGroupModal', false)">Cancelar</flux:button>
                <flux:button variant="danger" wire:click="deleteGroup">Eliminar Grupo</flux:button>
            </div>
        </div>
    </flux:modal>
</div>



