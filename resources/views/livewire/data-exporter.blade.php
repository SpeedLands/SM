<?php

use App\Models\Cycle;
use App\Models\ClassGroup;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    public ?string $cycleId = null;
    public ?string $groupId = null;
    
    public ?string $parentCycleId = null;
    public ?string $parentGroupId = null;
    public bool $generatePasswords = false;
    public bool $generateTeacherPasswords = false;

    public function mount(): void
    {
        $activeCycle = Cycle::where('is_active', true)->first();
        if ($activeCycle) {
            $this->cycleId = $activeCycle->id;
            $this->parentCycleId = $activeCycle->id;
        }
    }

    #[Computed]
    public function cycles()
    {
        return Cycle::orderBy('name', 'desc')->get();
    }

    #[Computed]
    public function groups()
    {
        if (! $this->cycleId) {
            return collect();
        }

        return ClassGroup::where('cycle_id', $this->cycleId)
            ->orderBy('grade')
            ->orderBy('section')
            ->get();
    }

    #[Computed]
    public function parentGroups()
    {
        if (! $this->parentCycleId) {
            return collect();
        }

        return ClassGroup::where('cycle_id', $this->parentCycleId)
            ->orderBy('grade')
            ->orderBy('section')
            ->get();
    }

    public function updatedCycleId(): void
    {
        $this->groupId = null;
    }
    
    public function updatedParentCycleId(): void
    {
        $this->parentGroupId = null;
    }
}; ?>

<div class="py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-8">
            <flux:heading size="xl" level="1">Exportar Datos a Excel</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">Descargue la información del sistema en formatos compatibles con el importador.</flux:text>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Teachers & Admins -->
            <flux:card class="flex flex-col">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg">
                        <flux:icon name="identification" />
                    </div>
                    <div>
                        <flux:heading size="lg">Maestros y Administradores</flux:heading>
                        <flux:text size="sm">Exporta la lista completa de personal.</flux:text>
                    </div>
                </div>

                <div class="flex flex-col gap-3 mb-6">
                    <flux:checkbox 
                        wire:model.live="generateTeacherPasswords" 
                        label="Generar nuevas contraseñas" 
                        description="Asignará una contraseña aleatoria a cada usuario exportado." 
                    />
                </div>

                <div class="mt-auto pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:button
                        as="a"
                        :href="route('export.teachers', ['generate_passwords' => $generateTeacherPasswords])"
                        variant="primary"
                        icon="document-arrow-down"
                        class="w-full"
                    >
                        Descargar Maestros
                    </flux:button>
                </div>
            </flux:card>

            <!-- Parents -->
            <flux:card class="flex flex-col">
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-4">
                    <div class="flex items-center gap-3 flex-1">
                        <div class="p-2 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-lg">
                            <flux:icon name="users" />
                        </div>
                        <div>
                            <flux:heading size="lg">Padres y Tutores</flux:heading>
                            <flux:text size="sm">Exporta padres con sus hijos vinculados.</flux:text>
                        </div>
                    </div>
                </div>
                
                <div class="flex flex-col gap-3 mb-6">
                    <div class="flex flex-row gap-2 w-full">
                        <flux:select wire:model.live="parentCycleId" placeholder="Ciclo Escolar" class="w-1/2">
                            @foreach ($this->cycles as $cycle)
                                <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model.live="parentGroupId" placeholder="Todos los grupos" class="w-1/2">
                            <option value="">Todos los grupos</option>
                            @foreach ($this->parentGroups as $group)
                                <option value="{{ $group->id }}">{{ $group->grade }} {{ $group->section }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                    <flux:checkbox 
                        wire:model.live="generatePasswords" 
                        label="Generar nuevas contraseñas" 
                        description="Asignará una contraseña aleatoria a cada usuario exportado." 
                    />
                </div>
                
                <div class="mt-auto pt-4 border-t border-zinc-100 dark:border-zinc-800">
                    <flux:button
                        as="a"
                        :href="route('export.parents', ['cycle_id' => $parentCycleId, 'group_id' => $parentGroupId, 'generate_passwords' => $generatePasswords])"
                        variant="primary"
                        icon="document-arrow-down"
                        class="w-full"
                    >
                        Descargar Padres
                    </flux:button>
                </div>
            </flux:card>

            <!-- Students -->
            <flux:card class="md:col-span-2">
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-6">
                    <div class="flex items-center gap-3 flex-1">
                        <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-lg">
                            <flux:icon name="academic-cap" />
                        </div>
                        <div>
                            <flux:heading size="lg">Alumnos</flux:heading>
                            <flux:text size="sm">Exporta alumnos por ciclo y grupo especializado.</flux:text>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                        <flux:select wire:model.live="cycleId" placeholder="Ciclo Escolar" class="w-full sm:w-48">
                            @foreach ($this->cycles as $cycle)
                                <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model.live="groupId" placeholder="Todos los grupos" class="w-full sm:w-48">
                            <option value="">Todos los grupos</option>
                            @foreach ($this->groups as $group)
                                <option value="{{ $group->id }}">{{ $group->grade }} {{ $group->section }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>

                <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <flux:text size="sm" class="flex items-center gap-2">
                            <flux:icon name="information-circle" variant="micro" />
                            Los datos sensibles (PII) se exportarán desencriptados para su lectura.
                    </flux:text>

                    <flux:button
                        as="a"
                        :href="route('export.students', ['cycle_id' => $cycleId, 'group_id' => $groupId])"
                        variant="primary"
                        icon="document-arrow-down"
                        class="w-full sm:w-auto"
                    >
                        Descargar Alumnos
                    </flux:button>
                </div>
            </flux:card>
        </div>

        <div class="mt-8 p-4 bg-zinc-50 dark:bg-zinc-900/50 rounded-xl border border-zinc-200 dark:border-zinc-700">
            <flux:heading size="sm" class="mb-2">¿Cómo funcionan estas exportaciones?</flux:heading>
            <flux:text size="xs" class="leading-relaxed">
                Los archivos generados mantienen exactamente el mismo orden de columnas que el sistema de importación. Esto significa que puede descargar los datos, editarlos en Excel (agregar alumnos, cambiar correos, etc.) y volver a subirlos en la sección "Importar Datos" para aplicar los cambios de forma masiva.
            </flux:text>
        </div>
    </div>
</div>
