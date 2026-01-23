<?php

use App\Models\Student;
use App\Models\ClassGroup;
use App\Models\Cycle;
use App\Models\User;
use App\Models\StudentCycleAssociation;
use App\Models\StudentPii;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $gradeFilter = 'Todos';
    public string $groupFilter = 'Todos';

    // Student Modal State
    public bool $showStudentModal = false;
    public string $studentId = '';
    
    // Core Student Fields
    public string $name = '';
    public string $birthDate = '';
    public string $turn = 'MATUTINO';
    public int $siblingsCount = 0;
    public int $birthOrder = 1;
    
    // Academic Fields
    public string $classGroupId = '';
    
    // PII Fields
    public string $address = '';
    public string $allergies = '';
    public string $medicalConditions = '';
    public string $emergencyContact = '';
    public string $otherContact = '';
    public string $motherName = '';
    public string $fatherName = '';
    public string $motherWorkplace = '';
    public string $fatherWorkplace = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorize('teacher-or-admin');
        $this->reset(['studentId', 'name', 'birthDate', 'turn', 'siblingsCount', 'birthOrder', 'classGroupId', 'address', 'allergies', 'medicalConditions', 'emergencyContact', 'otherContact', 'motherName', 'fatherName', 'motherWorkplace', 'fatherWorkplace']);
        $this->showStudentModal = true;
    }

    // Parent Association State
    public string $parentSearch = '';
    public string $selectedParentId = '';
    public string $parentRelationship = 'TUTOR';

    public function addParent(): void
    {
        $this->authorize('teacher-or-admin');
        if (!$this->studentId || !$this->selectedParentId) return;

        Student::findOrFail($this->studentId)->parents()->syncWithoutDetaching([
            $this->selectedParentId => ['relationship' => $this->parentRelationship]
        ]);

        $this->parentSearch = '';
        $this->selectedParentId = '';
        $this->dispatch('parent-added');
    }

    public function removeParent(string $parentId): void
    {
        $this->authorize('teacher-or-admin');
        if (!$this->studentId) return;

        Student::findOrFail($this->studentId)->parents()->detach($parentId);
        $this->dispatch('parent-removed');
    }

    public function editStudent(string $id): void
    {
        $this->authorize('teacher-or-admin');
        $student = Student::with(['pii', 'currentCycleAssociation', 'parents'])->findOrFail($id);
        
        $this->studentId = $student->id;
        $this->name = $student->name;
        $this->birthDate = $student->birth_date->format('Y-m-d');
        $this->turn = $student->turn;
        $this->siblingsCount = $student->siblings_count;
        $this->birthOrder = $student->birth_order;
        
        $this->classGroupId = $student->currentCycleAssociation?->class_group_id ?? '';
        
        if ($student->pii) {
            $this->address = $student->pii->address_encrypted ?? '';
            $this->allergies = $student->pii->allergies_encrypted ?? '';
            $this->medicalConditions = $student->pii->medical_conditions_encrypted ?? '';
            $this->emergencyContact = $student->pii->contact_phone_encrypted ?? '';
            $this->otherContact = $student->pii->other_contact_encrypted ?? '';
            $this->motherName = $student->pii->mother_name_encrypted ?? '';
            $this->fatherName = $student->pii->father_name_encrypted ?? '';
            $this->motherWorkplace = $student->pii->mother_workplace_encrypted ?? '';
            $this->fatherWorkplace = $student->pii->father_workplace_encrypted ?? '';
        }

        $this->parentSearch = '';
        $this->selectedParentId = '';
        $this->showStudentModal = true;
    }

    public function save(): void
    {
        $this->authorize('teacher-or-admin');
        $this->validate([
            'name' => 'required|string|max:100',
            'turn' => 'required|in:MATUTINO,VESPERTINO',
            'classGroupId' => 'required|exists:class_groups,id',
        ]);

        $activeCycle = Cycle::where('is_active', true)->first();
        if (!$activeCycle) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'No hay un ciclo escolar activo.']);
            return;
        }

        $group = ClassGroup::findOrFail($this->classGroupId);

        if ($this->studentId) {
            $student = Student::findOrFail($this->studentId);
            $student->update([
                'name' => strtoupper($this->name),
                'birth_date' => $this->birthDate ?: now()->subYears(12)->format('Y-m-d'),
                'grade' => $group->grade,
                'group_name' => $group->section,
                'turn' => $this->turn,
            ]);
        } else {
            $student = Student::create([
                'id' => (string) Str::uuid(),
                'name' => strtoupper($this->name),
                'birth_date' => $this->birthDate ?: now()->subYears(12)->format('Y-m-d'),
                'grade' => $group->grade,
                'group_name' => $group->section,
                'turn' => $this->turn,
            ]);
            $this->studentId = $student->id; // Set ID for new student so parents can be added
        }

        // Handle PII
        StudentPii::updateOrCreate(
            ['student_id' => $student->id],
            [
                'address_encrypted' => $this->address,
                'allergies_encrypted' => $this->allergies,
                'medical_conditions_encrypted' => $this->medicalConditions,
                'contact_phone_encrypted' => $this->emergencyContact,
                'other_contact_encrypted' => $this->otherContact,
                'mother_name_encrypted' => $this->motherName,
                'father_name_encrypted' => $this->fatherName,
                'mother_workplace_encrypted' => $this->motherWorkplace,
                'father_workplace_encrypted' => $this->fatherWorkplace,
            ]
        );

        // Handle Cycle Association
        StudentCycleAssociation::updateOrCreate(
            [
                'student_id' => $student->id,
                'cycle_id' => $activeCycle->id,
            ],
            [
                'class_group_id' => $this->classGroupId,
                'status' => 'ACTIVE',
            ]
        );

        $this->showStudentModal = false;
        $this->dispatch('student-saved');
    }

    public function deleteStudent(string $id): void
    {
        $this->authorize('teacher-or-admin');
        Student::findOrFail($id)->delete();
        $this->dispatch('student-saved'); // Trigger refresh
    }

    public function with(): array
    {
        $activeCycle = Cycle::where('is_active', true)->first();
        $classGroups = $activeCycle ? ClassGroup::where('cycle_id', $activeCycle->id)->get() : collect();
        
        $query = Student::query();

        if (auth()->user()->isParent()) {
            $query->whereHas('parents', function ($q) {
                $q->where('users.id', auth()->id());
            });
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', "%{$this->search}%");
            });
        }

        if ($this->gradeFilter !== 'Todos') {
            $query->where('grade', $this->gradeFilter);
        }

        if ($this->groupFilter !== 'Todos') {
            $query->where('group_name', $this->groupFilter);
        }

        $parentSearchResults = [];
        if (strlen($this->parentSearch) > 2) {
            $parentSearchResults = User::where('role', 'PARENT')
                ->where(function($q) {
                    $q->where('name', 'like', "%{$this->parentSearch}%")
                      ->orWhere('email', 'like', "%{$this->parentSearch}%");
                })
                ->limit(5)
                ->get();
        }

        $currentStudent = $this->studentId ? Student::with('parents')->find($this->studentId) : null;

        return [
            'students' => $query->latest('name')->paginate(10),
            'classGroups' => $classGroups,
            'activeCycle' => $activeCycle,
            'parentSearchResults' => $parentSearchResults,
            'currentParents' => $currentStudent ? $currentStudent->parents : collect(),
        ];
    }
}; ?>

<div class="space-y-6 text-zinc-900 dark:text-white pb-10">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">Gestión de Alumnos</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">Administre el padrón de estudiantes, sus datos de contacto y su situación académica.</flux:text>
        </div>
        @can('teacher-or-admin')
            <flux:button variant="primary" icon="user-plus" wire:click="openCreateModal" :disabled="count($classGroups) === 0">Inscribir Alumno</flux:button>
        @endcan
    </div>

    @if($activeCycle && count($classGroups) === 0)
        <flux:callout variant="warning" heading="Faltan Grupos Académicos">
            No hay grupos (grados/secciones) configurados para el ciclo activo ({{ $activeCycle->name }}). Debe registrar grupos antes de poder inscribir alumnos.
            <flux:link href="{{ route('cycles.index') }}" icon="arrow-right-start-on-rectangle" class="ml-2 font-bold">Ir a Configuración de Ciclos</flux:link>
        </flux:callout>
    @endif

    @if(!$activeCycle)
        <flux:callout variant="danger" heading="Ciclo Activo No Encontrado">
            No hay un ciclo escolar marcado como activo. Por favor, configure uno para poder gestionar alumnos.
            <flux:link href="{{ route('cycles.index') }}" class="ml-2">Configurar Ciclo</flux:link>
        </flux:callout>
    @endif

    <!-- Search and Filters -->
    <div class="p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <flux:field>
                <flux:label>Buscar Alumno</flux:label>
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Nombre..." />
            </flux:field>

            <flux:field>
                <flux:label>Grado</flux:label>
                <flux:select wire:model.live="gradeFilter">
                    <option value="Todos">Todos los grados</option>
                    <option value="1º">1º Secundaria</option>
                    <option value="2º">2º Secundaria</option>
                    <option value="3º">3º Secundaria</option>
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Grupo</flux:label>
                <flux:select wire:model.live="groupFilter">
                    <option value="Todos">Todos los grupos</option>
                    @foreach(['A', 'B', 'C', 'D', 'E', 'F'] as $section)
                        <option value="{{ $section }}">Sección {{ $section }}</option>
                    @endforeach
                </flux:select>
            </flux:field>
        </div>
    </div>

    <!-- Students Table -->
    <div class="p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700 text-xs uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        <th class="py-3 px-2 font-semibold">Alumno</th>
                        <th class="py-3 px-2 font-semibold text-center">Grado / Grupo</th>
                        <th class="py-3 px-2 font-semibold text-center">Turno</th>
                        <th class="py-3 px-2 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($students as $student)
                        <tr wire:key="{{ $student->id }}" class="hover:bg-zinc-800/5 dark:hover:bg-white/5 transition-colors">
                            <td class="py-4 px-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                                        <flux:icon icon="user" class="text-indigo-600 dark:text-indigo-400" variant="solid" />
                                    </div>
                                    <div>
                                        <div class="font-bold text-zinc-900 dark:text-white uppercase">{{ $student->name }}</div>
                                        <div class="text-xs text-zinc-500">Inscrito en {{ $activeCycle?->name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-2 text-center">
                                <div class="inline-flex items-center gap-1">
                                    <flux:badge size="sm" color="blue">{{ $student->grade }}</flux:badge>
                                    <flux:badge size="sm" color="neutral">{{ $student->group_name }}</flux:badge>
                                </div>
                            </td>
                            <td class="py-4 px-2 text-center">
                                <flux:badge size="sm" variant="outline" color="{{ $student->turn === 'MATUTINO' ? 'sky' : 'orange' }}">
                                    {{ $student->turn }}
                                </flux:badge>
                            </td>
                            <td class="py-4 px-2 text-right">
                                <div class="flex justify-end gap-1">
                                    @can('teacher-or-admin')
                                        <flux:button variant="ghost" size="sm" icon="pencil" wire:click="editStudent('{{ $student->id }}')" />
                                        <flux:button variant="ghost" size="sm" icon="trash" class="text-red-500" wire:click="deleteStudent('{{ $student->id }}')" />
                                    @endcan
                                    <flux:button variant="ghost" size="sm" icon="eye" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center text-zinc-500 italic">No se encontraron alumnos coincidentes</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between text-sm text-zinc-500">
            <div>{{ $students->links() }}</div>
        </div>
    </div>

    @can('teacher-or-admin')
        <!-- Student Modal -->
        <flux:modal wire:model="showStudentModal" class="w-full max-w-2xl">
            <div class="space-y-6">
                <header>
                    <flux:heading size="lg">{{ $studentId ? 'Editar Información de Alumno' : 'Inscripción de Nuevo Alumno' }}</flux:heading>
                    <flux:text>Complete los datos pedagógicos y personales del estudiante.</flux:text>
                </header>

                <form wire:submit="save" class="space-y-8" x-data="{ name: @entangle('name'), emergencyContact: @entangle('emergencyContact') }">
                    <!-- Section: Basic Info -->
                    <div class="space-y-4">
                        <flux:separator text="Información Básica" />
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <flux:input 
                                label="Nombre Completo" 
                                wire:model="name" 
                                placeholder="Ej. JUAN PEREZ LOPEZ" 
                                class="uppercase md:col-span-1"
                                x-on:input="name = $event.target.value.toUpperCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^A-Z ]/g, '')"
                            />
                            <flux:select label="Turno" wire:model="turn">
                                <option value="MATUTINO">Matutino</option>
                                <option value="VESPERTINO">Vespertino</option>
                            </flux:select>
                            <flux:select label="Grupo / Grado" wire:model="classGroupId">
                                <option value="">Seleccione grupo...</option>
                                @foreach($classGroups as $group)
                                    <option value="{{ $group->id }}">{{ $group->grade }} {{ $group->section }}</option>
                                @endforeach
                            </flux:select>
                        </div>
                    <!-- Section: Contact Info -->
                    <div class="space-y-4">
                        <flux:separator text="Información de Contacto" />
                        <flux:textarea label="Dirección" wire:model="address" placeholder="Calle, número, colonia..." rows="2" />
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <flux:input 
                                label="Teléfonos de contacto" 
                                wire:model="emergencyContact" 
                                placeholder="Ej. 12345678, 87654321"
                                x-on:input="emergencyContact = $event.target.value.replace(/\D/g, '')"
                            />
                            <flux:input label="Otro contacto / Parentesco" wire:model="otherContact" placeholder="Ej. Abuela - 1234..." />
                        </div>
                    </div>

                    <!-- Hidden Fields (Stored for compatibility but not shown) -->
                    <div class="hidden">
                        <input type="date" wire:model="birthDate">
                        <input type="number" wire:model="siblingsCount">
                        <input type="number" wire:model="birthOrder">
                        <input type="text" wire:model="motherName">
                        <input type="text" wire:model="motherWorkplace">
                        <input type="text" wire:model="fatherName">
                        <input type="text" wire:model="fatherWorkplace">
                        <input type="text" wire:model="allergies">
                        <input type="text" wire:model="medicalConditions">
                    </div>

                    <!-- Section: Parents / Tutores -->
                    <div class="space-y-4">
                        <flux:separator text="Padres de Familia" />
                        
                        <div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-800/30">
                            <div class="flex items-start gap-3">
                                <flux:icon icon="information-circle" class="text-blue-600 dark:text-blue-400 shrink-0" />
                                <flux:text size="sm" class="text-blue-900 dark:text-blue-200">
                                    Los datos de contacto detallados, puestos y ocupaciones de los padres se gestionan directamente a través de sus <b>Cuentas de Usuario</b> vinculadas aquí.
                                </flux:text>
                            </div>
                        </div>

                        @if($studentId)
                            <div class="space-y-4">
                                <!-- Parent Search -->
                                <div class="flex gap-2 items-end">
                                    <flux:field class="grow">
                                        <flux:label>Vincular nuevo Padre/Madre</flux:label>
                                        <flux:input wire:model.live.debounce.300ms="parentSearch" icon="user-plus" placeholder="Buscar por nombre o email..." />
                                    </flux:field>
                                    <flux:select wire:model="parentRelationship" class="w-1/3">
                                        <option value="PADRE">Padre</option>
                                        <option value="MADRE">Madre</option>
                                    </flux:select>
                                    <flux:button wire:click="addParent" variant="primary" :disabled="!$selectedParentId">Vincular</flux:button>
                                </div>

                                @if(count($parentSearchResults) > 0)
                                    <div class="p-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 shadow-inner max-h-40 overflow-y-auto">
                                        @foreach($parentSearchResults as $parent)
                                            <button type="button" 
                                                wire:click="$set('selectedParentId', '{{ $parent->id }}')"
                                                @class([
                                                    'w-full flex items-center justify-between p-2 rounded text-left transition-colors',
                                                    'bg-blue-100 dark:bg-blue-900/40 border border-blue-200 dark:border-blue-800' => $selectedParentId === $parent->id,
                                                    'hover:bg-zinc-200 dark:hover:bg-zinc-700' => $selectedParentId !== $parent->id
                                                ])
                                            >
                                                <div class="flex items-center gap-2">
                                                    <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-600 flex items-center justify-center text-xs font-bold">{{ $parent->initials() }}</div>
                                                    <div>
                                                        <div class="text-xs font-bold">{{ $parent->name }}</div>
                                                        <div class="text-[10px] text-zinc-500">{{ $parent->email }}</div>
                                                    </div>
                                                </div>
                                                @if($selectedParentId === $parent->id)
                                                    <flux:icon icon="check" size="sm" class="text-blue-600" />
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                @endif


                                <!-- Current Parents List -->
                                <div class="space-y-2">
                                    <flux:heading size="sm">Padres Vinculados</flux:heading>
                                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                        @forelse($currentParents as $parent)
                                            <div class="flex items-center justify-between py-2">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-xs font-bold text-purple-600">{{ $parent->initials() }}</div>
                                                    <div>
                                                        <div class="text-sm font-bold uppercase">{{ $parent->name }}</div>
                                                        <div class="text-xs text-zinc-500">{{ $parent->pivot->relationship }} · {{ $parent->phone ?? 'Sin teléfono' }}</div>
                                                    </div>
                                                </div>
                                                <flux:button variant="ghost" size="sm" icon="x-mark" class="text-red-500" wire:click="removeParent('{{ $parent->id }}')" />
                                            </div>
                                        @empty
                                            <flux:text class="italic text-xs text-zinc-500">No hay padres vinculados a este alumno.</flux:text>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="p-4 rounded-lg bg-zinc-100 dark:bg-zinc-800/50 border border-dashed border-zinc-300 dark:border-zinc-700 text-center">
                                <flux:text size="sm" class="italic text-zinc-500">Primero debe guardar los datos básicos del alumno para poder vincular padres o tutores.</flux:text>
                            </div>
                        @endif
                    </div>

                    <div class="flex gap-2 pt-4">
                        <flux:spacer />
                        <flux:button wire:click="$set('showStudentModal', false)">Cancelar</flux:button>
                        <flux:button type="submit" variant="primary">
                            {{ $studentId ? 'Actualizar Registro' : 'Inscribir Alumno' }}
                        </flux:button>
                    </div>
                </form>
            </div>
        </flux:modal>
    @endcan
</div>
