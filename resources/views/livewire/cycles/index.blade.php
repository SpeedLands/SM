<?php

use App\Models\Cycle;
use App\Models\ClassGroup;
use App\Models\User;
use Illuminate\Support\Collection;
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

    protected $rules = [
        'name' => 'required|string|max:50',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after:start_date',
        'is_active' => 'boolean',
    ];

    public function mount(): void
    {
        $this->authorize('admin-only');
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

    public function delete(Cycle $cycle): void
    {
        $cycle->delete();
    }

    // Group Management Methods
    public function openGroupsModal(string $cycleId): void
    {
        $this->groupCycle = Cycle::findOrFail($cycleId);
        $this->showGroupsModal = true;
    }

    public function addGroup(): void
    {
        $this->validate([
            'grade' => 'required|string',
            'section' => 'required|string',
            'tutorId' => 'nullable|exists:users,id',
        ]);

        ClassGroup::create([
            'cycle_id' => $this->groupCycle->id,
            'grade' => $this->grade,
            'section' => $this->section,
            'tutor_teacher_id' => $this->tutorId ?: null,
        ]);

        $this->reset(['grade', 'section', 'tutorId']);
        $this->groupCycle->load('groups');
    }

    public function deleteGroup(string $id): void
    {
        ClassGroup::findOrFail($id)->delete();
        $this->groupCycle->load('groups');
    }

    public function with(): array
    {
        $cycles = Cycle::query()
            ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
            ->orderBy('start_date', 'desc')
            ->paginate(10);

        $activeCycle = Cycle::where('is_active', true)->first();
        $totalCycles = Cycle::count();
        $nextCycle = Cycle::where('start_date', '>', now())->orderBy('start_date', 'asc')->first();

        return [
            'cycles' => $cycles,
            'activeCycle' => $activeCycle,
            'totalCycles' => $totalCycles,
            'nextCycle' => $nextCycle,
            'teachers' => User::where('role', 'TEACHER')->get(),
            'currentGroups' => $this->groupCycle ? ClassGroup::with('tutor')->where('cycle_id', $this->groupCycle->id)->get() : collect(),
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
            <flux:text class="uppercase text-xs font-semibold tracking-wider text-zinc-500 dark:text-zinc-400">Próximo Inicio</flux:text>
            <flux:heading size="xl" class="mt-2">
                {{ $nextCycle ? \Carbon\Carbon::parse($nextCycle->start_date)->format('M Y') : 'TBD' }}
            </flux:heading>
            <flux:text class="mt-4 text-xs text-blue-600 font-medium dark:text-blue-400">
                @if($nextCycle)
                    Faltan {{ now()->diffInDays($nextCycle->start_date) }} días
                @else
                    Sin planeación futura
                @endif
            </flux:text>
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
                <flux:input wire:model="name" :label="__('Nombre del Ciclo')" placeholder="Ej: 2024-2025" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input type="date" wire:model="start_date" :label="__('Inicio')" />
                    <flux:input type="date" wire:model="end_date" :label="__('Fin')" />
                </div>

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
                                    @if($cycle->is_active)
                                        <flux:badge color="green" size="sm" inset="left">Activo</flux:badge>
                                    @elseif(\Carbon\Carbon::parse($cycle->start_date) > now())
                                        <flux:badge color="blue" size="sm" inset="left">Planificado</flux:badge>
                                    @else
                                        <flux:badge color="neutral" size="sm" inset="left">Cerrado</flux:badge>
                                    @endif
                                </td>
                                <td class="py-4 px-2 text-right">
                                    <div class="flex justify-end gap-1">
                                        <flux:button variant="ghost" size="sm" icon="users" wire:click="openGroupsModal('{{ $cycle->id }}')" />
                                        <flux:button variant="ghost" size="sm" icon="pencil" wire:click="edit({{ $cycle->id }})" />
                                        <flux:button variant="ghost" size="sm" icon="trash" wire:click="delete({{ $cycle->id }})" />
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

            <form wire:submit="addGroup" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700">
                <flux:select label="Grado" wire:model="grade">
                    <option value="">Grado...</option>
                    <option value="1º">1º Secundaria</option>
                    <option value="2º">2º Secundaria</option>
                    <option value="3º">3º Secundaria</option>
                </flux:select>
                <flux:select label="Sección" wire:model="section">
                    <option value="">Sección...</option>
                    @foreach(range('A', 'F') as $letter)
                        <option value="{{ $letter }}">{{ $letter }}</option>
                    @endforeach
                </flux:select>
                <flux:select label="Tutor" wire:model="tutorId">
                    <option value="">Seleccione tutor...</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                    @endforeach
                </flux:select>
                <flux:button variant="primary" type="submit">Añadir</flux:button>
            </form>

            <div class="space-y-2">
                <flux:heading size="sm">Grupos Registrados</flux:heading>
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800 border rounded-lg overflow-hidden border-zinc-200 dark:border-zinc-700">
                    @forelse($currentGroups as $group)
                        <div class="flex items-center justify-between p-3 bg-white dark:bg-zinc-900" wire:key="group-{{ $group->id }}">
                            <div class="flex items-center gap-4">
                                <div class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-bold rounded text-lg">{{ $group->grade }} {{ $group->section }}</div>
                                <div>
                                    <div class="text-sm font-medium uppercase">Tutor: {{ $group->tutor?->name ?? 'No asignado' }}</div>
                                </div>
                            </div>
                            <flux:button variant="ghost" size="sm" icon="trash" class="text-red-500" wire:click="deleteGroup('{{ $group->id }}')" />
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
</div>



