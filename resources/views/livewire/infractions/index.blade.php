<?php

use App\Models\Infraction;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

new class extends Component {
    use WithPagination;

    // Filters
    public string $search = '';

    // Modal state
    public bool $showModal = false;
    public ?Infraction $editingInfraction = null;

    // Form fields
    public string $description = '';
    public string $severity = 'NORMAL';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin() && auth()->user()->isViewStaff(), 403);
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal(Infraction $infraction): void
    {
        $this->editingInfraction = $infraction;
        $this->description = $infraction->description;
        $this->severity = $infraction->severity;
        $this->showModal = true;
    }

    public function resetForm(): void
    {
        $this->reset(['editingInfraction', 'description', 'severity']);
    }

    public function save(): void
    {
        $this->validate([
            'description' => 'required|string|max:255',
            'severity' => ['required', Rule::in(['NORMAL', 'GRAVE'])],
        ]);

        if ($this->editingInfraction) {
            $this->editingInfraction->update([
                'description' => $this->description,
                'severity' => $this->severity,
            ]);
            $message = 'Tipo de reporte actualizado correctamente.';
        } else {
            Infraction::create([
                'description' => $this->description,
                'severity' => $this->severity,
                'created_at' => now(),
            ]);
            $message = 'Tipo de reporte creado correctamente.';
        }

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('notify', ['message' => $message]);
    }

    public function delete(Infraction $infraction): void
    {
        if ($infraction->reports()->exists()) {
            $this->dispatch('notify', ['message' => 'No se puede eliminar un tipo de reporte que ya ha sido utilizado.', 'variant' => 'danger']);
            return;
        }
        
        try {
            $infraction->delete();
            $this->dispatch('notify', ['message' => 'Tipo de reporte eliminado.']);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['message' => 'No se pudo eliminar el registro.', 'variant' => 'danger']);
        }
    }

    public function with(): array
    {
        $infractions = Infraction::query()
            ->withCount('reports')
            ->when($this->search, fn($q) => $q->where('description', 'like', "%{$this->search}%"))
            ->orderBy('description', 'asc')
            ->paginate(10);

        return [
            'infractions' => $infractions,
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">Tipos de Reporte</flux:heading>
            <flux:subheading>Gestione los tipos de infracciones displinarias.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openCreateModal">Nuevo Tipo</flux:button>
    </div>

    <!-- Filters -->
    <div class="p-4 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Buscar..." />
    </div>

    <!-- Table -->
    <div class="p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="py-3 px-2 font-semibold">Descripción</th>
                    <th class="py-3 px-2 font-semibold text-center">Gravedad</th>
                    <th class="py-3 px-2 text-right font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($infractions as $infraction)
                    <tr wire:key="{{ $infraction->id }}">
                        <td class="py-4 px-2">
                            <div class="font-medium">{{ $infraction->description }}</div>
                        </td>
                        <td class="py-4 px-2 text-center">
                            @if($infraction->severity === 'GRAVE')
                                <flux:badge color="red" size="sm" inset="left">Grave</flux:badge>
                            @else
                                <flux:badge color="neutral" size="sm" inset="left">Normal</flux:badge>
                            @endif
                        </td>
                        <td class="py-4 px-2 text-right">
                            <div class="flex justify-end gap-1">
                                <flux:button variant="ghost" size="sm" icon="pencil" wire:click="openEditModal({{ $infraction->id }})" />
                                @if($infraction->reports_count === 0)
                                    <flux:button variant="ghost" size="sm" icon="trash" class="text-red-500" wire:click="delete({{ $infraction->id }})" wire:confirm="¿Está seguro de eliminar este tipo de reporte?" />
                                @else
                                    <flux:button variant="ghost" size="sm" icon="trash" class="text-zinc-300 dark:text-zinc-600" title="No se puede eliminar porque tiene reportes asociados" disabled />
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="py-12 text-center text-zinc-500 italic">No se encontraron tipos de reporte.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">
            {{ $infractions->links() }}
        </div>
    </div>

    <!-- Modal -->
    <flux:modal wire:model.self="showModal" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <header>
                <flux:heading size="lg">{{ $editingInfraction ? 'Editar Tipo' : 'Nuevo Tipo' }}</flux:heading>
            </header>

            <div class="space-y-4">
                <flux:input wire:model="description" label="Descripción" placeholder="Ej: Falta de tarea..." required />
                
                <flux:select wire:model="severity" label="Gravedad">
                    <option value="NORMAL">Normal</option>
                    <option value="GRAVE">Grave</option>
                </flux:select>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button wire:click="$set('showModal', false)">Cancelar</flux:button>
                <flux:button variant="primary" type="submit">Guardar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
