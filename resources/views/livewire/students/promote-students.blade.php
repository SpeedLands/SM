<?php

use App\Models\Cycle;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\StudentCycleAssociation;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

new class extends Component {
    // Selection state
    public string $sourceCycleId = '';
    public string $sourceGroupId = '';
    public string $destinationCycleId = '';
    public string $destinationGroupId = '';

    // Data state
    public array $sourceGroups = [];
    public array $destinationGroups = [];
    public array $students = [];
    public array $selectedStudents = [];
    public bool $selectAll = false;

    public function mount(): void
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
    }

    #[Computed]
    public function cycles(): Collection
    {
        return Cycle::orderByDesc('start_date')->get();
    }

    public function updatedSourceCycleId($value): void
    {
        $this->sourceGroups = ClassGroup::where('cycle_id', $value)
            ->orderBy('grade')
            ->orderBy('section')
            ->get()
            ->map(fn($g) => ['id' => $g->id, 'name' => "{$g->grade}º {$g->section}"])
            ->toArray();
            
        $this->reset(['sourceGroupId', 'students', 'selectedStudents', 'selectAll']);
    }

    public function updatedDestinationCycleId($value): void
    {
        $this->destinationGroups = ClassGroup::where('cycle_id', $value)
            ->orderBy('grade')
            ->orderBy('section')
            ->get()
            ->map(fn($g) => ['id' => $g->id, 'name' => "{$g->grade}º {$g->section}"])
            ->toArray();
            
        $this->reset(['destinationGroupId']);
    }

    public function updatedSourceGroupId(): void
    {
        $this->loadStudents();
    }

    public function loadStudents(): void
    {
        $this->reset(['students', 'selectedStudents', 'selectAll']);

        if (!$this->sourceCycleId || !$this->sourceGroupId) {
            return;
        }

        // SCRITICAL: Strict filtering by cycle association
        $this->students = Student::query()
            ->whereHas('cycleAssociations', function ($query) {
                $query->where('cycle_id', $this->sourceCycleId)
                      ->where('class_group_id', $this->sourceGroupId);
            })
            ->orderBy('name')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id, 
                'name' => $s->name,
                'current_grade' => $s->grade,
                'current_group' => $s->group_name
            ])
            ->toArray();
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedStudents = array_column($this->students, 'id');
        } else {
            $this->selectedStudents = [];
        }
    }

    public function promote(): void
    {
        $this->validate([
            'sourceCycleId' => 'required',
            'sourceGroupId' => 'required',
            'destinationCycleId' => 'required|different:sourceCycleId',
            'destinationGroupId' => 'required',
            'selectedStudents' => 'required|array|min:1',
        ], [
            'destinationCycleId.different' => 'El ciclo de destino debe ser diferente al de origen.',
            'selectedStudents.required' => 'Debe seleccionar al menos un alumno.',
        ]);

        $destinationGroup = ClassGroup::findOrFail($this->destinationGroupId);

        DB::transaction(function () use ($destinationGroup) {
            foreach ($this->selectedStudents as $studentId) {
                // 1. Create or Update association for the NEW cycle
                StudentCycleAssociation::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'cycle_id' => $this->destinationCycleId,
                    ],
                    [
                        'class_group_id' => $this->destinationGroupId,
                        'status' => 'ACTIVE', // Assuming they are active in the new cycle
                    ]
                );

                // 2. Update the Student main record to reflect the new "Current" status
                // This keeps the "view_students" table up to date with where they are NOW
                Student::where('id', $studentId)->update([
                    'grade' => $destinationGroup->grade,
                    'group_name' => $destinationGroup->section,
                ]);
            }
        });

        $this->dispatch('notify', [
            'variant' => 'success', 
            'title' => 'Promoción Exitosa',
            'message' => count($this->selectedStudents) . ' alumnos han sido promovidos correctamente.'
        ]);

        // Reset selection
        $this->selectedStudents = [];
        $this->selectAll = false;
        
        // Reload students to remove them from list?
        // Actually, they are still in the source cycle technically (history), 
        // so they should likely stay in the list unless we filter out those who are ALSO in destination.
        // For now, keying them as "done" might be nice, but simple refresh is okay.
    }
}; ?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl" level="1">Promoción de Alumnos</flux:heading>
            <flux:text class="text-zinc-500">Mueva grupos de alumnos de un ciclo escolar a otro masivamente.</flux:text>
        </div>
    </div>

    <!-- Configuration Panel -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Source -->
        <div class="p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm space-y-4">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center font-bold">1</div>
                <flux:heading size="lg">Origen</flux:heading>
            </div>

            <flux:field>
                <flux:label>Ciclo Escolar Actual</flux:label>
                <flux:select wire:model.live="sourceCycleId" placeholder="Seleccione ciclo...">
                    @foreach($this->cycles as $cycle)
                        <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Grupo Actual</flux:label>
                <flux:select wire:model.live="sourceGroupId" placeholder="Seleccione grupo..." :disabled="empty($sourceGroups)">
                    @foreach($sourceGroups as $group)
                        <option value="{{ $group['id'] }}">{{ $group['name'] }}</option>
                    @endforeach
                </flux:select>
            </flux:field>
        </div>

        <!-- Destination -->
        <div class="p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm space-y-4">
             <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold">2</div>
                <flux:heading size="lg">Destino</flux:heading>
            </div>

            <flux:field>
                <flux:label>Ciclo Escolar Siguiente</flux:label>
                <flux:select wire:model.live="destinationCycleId" placeholder="Seleccione ciclo...">
                    @foreach($this->cycles as $cycle)
                        <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Grupo Siguiente</flux:label>
                <flux:select wire:model.live="destinationGroupId" placeholder="Seleccione grupo..." :disabled="empty($destinationGroups)">
                    @foreach($destinationGroups as $group)
                        <option value="{{ $group['id'] }}">{{ $group['name'] }}</option>
                    @endforeach
                </flux:select>
            </flux:field>
        </div>
    </div>

    <!-- Student Selection -->
    @if(!empty($students))
        <div class="p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="lg">Selección de Alumnos ({{ count($selectedStudents) }}/{{ count($students) }})</flux:heading>
                
                <div class="flex items-center gap-2">
                    <flux:checkbox wire:model.live="selectAll" label="Seleccionar Todos" />
                </div>
            </div>

            <div class="max-h-125 overflow-y-auto border border-zinc-200 dark:border-zinc-700 rounded-lg">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider w-10">
                                #
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">
                                Nombre del Alumno
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">
                                Grado/Grupo Actual
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($students as $student)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <flux:checkbox wire:model.live="selectedStudents" value="{{ $student['id'] }}" />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $student['name'] }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <flux:badge size="sm">{{ $student['current_grade'] }}º {{ $student['current_group'] }}</flux:badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex justify-end">
                <flux:button variant="primary" wire:click="promote" icon="arrow-right" :disabled="empty($selectedStudents) || !$destinationGroupId">
                    Promover Alumnos Seleccionados
                </flux:button>
            </div>
        </div>
    @elseif($sourceCycleId && $sourceGroupId)
        <flux:callout variant="warning">No hay alumnos registrados en el grupo seleccionado.</flux:callout>
    @endif
</div>
