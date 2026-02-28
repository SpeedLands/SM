<?php

use App\Models\Student;
use App\Models\ClassGroup;
use App\Models\Cycle;
use App\Models\User;
use App\Models\StudentCycleAssociation;
use App\Models\StudentPii;
use App\Models\Citation;
use App\Models\Report;
use App\Models\CommunityService;
use App\Models\NoticeSignature;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $gradeFilter = 'Todos';
    public string $groupFilter = 'Todos';
    public bool $onlyActiveCycle = true;

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
    
    // Deletion State
    public string $idToDelete = '';
    public string $nameToDelete = '';
    public bool $showDeleteModal = false;

    // History Modal State
    public bool $showHistoryModal = false;
    public string $historyStudentName = '';
    public array $historyItems = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingGradeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingGroupFilter(): void
    {
        $this->resetPage();
    }

    public function updatingOnlyActiveCycle(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        if (!auth()->user()->isViewStaff()) abort(403);
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
        if (!auth()->user()->isViewStaff()) abort(403);
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
        if (!auth()->user()->isViewStaff()) abort(403);
        $this->authorize('teacher-or-admin');
        if (!$this->studentId) return;

        Student::findOrFail($this->studentId)->parents()->detach($parentId);
        $this->dispatch('parent-removed');
    }

    public function viewHistory(string $id): void
    {
        $student = Student::findOrFail($id);
        $this->historyStudentName = $student->name;

        $activeCycle = Cycle::where('is_active', true)->first();
        $items = collect();

        // Reports
        $reports = Report::with('teacher', 'infraction')
            ->where('student_id', $id)
            ->when($activeCycle, fn($q) => $q->where('cycle_id', $activeCycle->id))
            ->get();

        foreach ($reports as $r) {
            $items->push([
                'type' => 'report',
                'date' => $r->date ? $r->date->format('Y-m-d') : null,
                'date_display' => $r->date ? $r->date->isoFormat('D [de] MMMM, YYYY') : 'Sin fecha',
                'title' => $r->subject,
                'description' => $r->description,
                'extra' => $r->teacher?->name ?? '',
                'status' => $r->status,
            ]);
        }

        // Community Services
        $services = CommunityService::with('assignedBy')
            ->where('student_id', $id)
            ->when($activeCycle, fn($q) => $q->where('cycle_id', $activeCycle->id))
            ->get();

        foreach ($services as $s) {
            $items->push([
                'type' => 'service',
                'date' => $s->scheduled_date ? $s->scheduled_date->format('Y-m-d') : null,
                'date_display' => $s->scheduled_date ? $s->scheduled_date->isoFormat('D [de] MMMM, YYYY') : 'Sin fecha',
                'title' => $s->activity,
                'description' => $s->description,
                'extra' => $s->assignedBy?->name ?? '',
                'status' => $s->status,
            ]);
        }

        // Citations
        $citations = Citation::with('teacher')
            ->where('student_id', $id)
            ->when($activeCycle, fn($q) => $q->where('cycle_id', $activeCycle->id))
            ->get();

        foreach ($citations as $c) {
            $items->push([
                'type' => 'citation',
                'date' => $c->citation_date ? $c->citation_date->format('Y-m-d') : null,
                'date_display' => $c->citation_date ? $c->citation_date->isoFormat('D [de] MMMM, YYYY') : 'Sin fecha',
                'title' => $c->reason,
                'description' => '',
                'extra' => $c->teacher?->name ?? '',
                'status' => $c->status,
            ]);
        }

        $this->historyItems = $items->sortByDesc('date')->values()->toArray();
        $this->showHistoryModal = true;
    }

    public function editStudent(string $id): void
    {
        if (!auth()->user()->isViewStaff()) abort(403);
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
        if (!auth()->user()->isViewStaff()) abort(403);
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

    public function confirmDelete(string $id): void
    {
        if (!auth()->user()->isViewStaff()) abort(403);
        $this->authorize('teacher-or-admin');
        $student = Student::findOrFail($id);
        $this->idToDelete = $id;
        $this->nameToDelete = $student->name;
        $this->showDeleteModal = true;
    }

    public function deleteStudent(): void
    {
        if (!auth()->user()->isViewStaff()) abort(403);
        $this->authorize('teacher-or-admin');
        if (!$this->idToDelete) {
            \Illuminate\Support\Facades\Log::warning('Intento de borrar alumno sin ID seleccionado.');
            return;
        }

        $student = Student::withCount(['reports', 'communityServices', 'citations', 'noticeSignatures'])->findOrFail($this->idToDelete);
        
        if ($student->reports_count > 0 || $student->community_services_count > 0 || $student->citations_count > 0 || $student->notice_signatures_count > 0) {
            $this->dispatch('notify', [
                'variant' => 'danger', 
                'message' => 'No se puede eliminar un alumno que tiene historial (reportes, servicios, citatorios o avisos firmados).'
            ]);
            $this->showDeleteModal = false;
            return;
        }

        try {
            $student->delete();
            \Illuminate\Support\Facades\Log::info("Alumno eliminado: {$this->nameToDelete} ({$this->idToDelete}) por usuario: " . auth()->id());
            
            $this->idToDelete = '';
            $this->nameToDelete = '';
            $this->showDeleteModal = false;
            $this->dispatch('student-saved');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error al eliminar alumno: " . $e->getMessage());
            $this->dispatch('notify', ['variant' => 'danger', 'message' => 'Error al eliminar el alumno en la base de datos.']);
        }
    }

    public function with(): array
    {
        $activeCycle = Cycle::where('is_active', true)->first();
        $classGroups = $activeCycle ? ClassGroup::where('cycle_id', $activeCycle->id)->get() : collect();
        
        $query = Student::query()
            ->select('students.*')
            ->when(auth()->user()->isViewParent(), function ($q) {
                $q->join('student_parents', 'students.id', '=', 'student_parents.student_id')
                  ->where('student_parents.parent_id', auth()->id());
            })
            ->when($this->onlyActiveCycle && $activeCycle, function ($q) use ($activeCycle) {
                $q->whereHas('currentCycleAssociation', function ($sq) use ($activeCycle) {
                    $sq->where('cycle_id', $activeCycle->id);
                });
            });

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
            'students' => $query->withCount(['reports', 'communityServices', 'citations', 'noticeSignatures'])->latest('name')->paginate(10),
            'classGroups' => $classGroups,
            'activeCycle' => $activeCycle,
            'parentSearchResults' => $parentSearchResults,
            'currentParents' => $currentStudent ? $currentStudent->parents : collect(),
        ];
    }
}; ?>

<div class="space-y-6 text-zinc-900 dark:text-white pb-10">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Gestión de Alumnos</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">Administre el padrón de estudiantes, sus datos de contacto y su situación académica.</flux:text>
        </div>
        @if(auth()->user()->isViewStaff())
            <flux:button variant="primary" icon="user-plus" wire:click="openCreateModal" :disabled="count($classGroups) === 0">Inscribir Alumno</flux:button>
        @endif
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
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <flux:heading size="lg" level="2">Filtros</flux:heading>
            <flux:switch wire:model.live="onlyActiveCycle" label="Solo mostrar inscritos en ciclo actual" />
        </div>
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
                    @foreach(['A', 'B', 'C', 'D', 'G', 'H', 'I'] as $section)
                        <option value="{{ $section }}">Sección {{ $section }}</option>
                    @endforeach
                </flux:select>
            </flux:field>
        </div>
    </div>

    <!-- Students Table -->
    <div class="p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <div x-data="studentPopover()" x-init="init()" x-cloak class="relative">
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
                        <tr wire:key="{{ $student->id }}" 
                        @if(auth()->user()->isViewStaff())
                            x-on:click="select($event)" data-id="{{ $student->id }}" data-name="{{ $student->name }}" class="hover:bg-zinc-800/5 dark:hover:bg-white/5 transition-colors cursor-pointer"
                        @else
                            class="hover:bg-zinc-800/5 dark:hover:bg-white/5 transition-colors"
                        @endif
                        >
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
                                    <flux:button x-on:click.stop variant="ghost" size="sm" icon="eye" wire:click="viewHistory('{{ $student->id }}')" title="Ver historial" />
                                    @if(auth()->user()->isViewStaff())
                                        <flux:button x-on:click.stop variant="ghost" size="sm" icon="pencil" wire:click="editStudent('{{ $student->id }}')" />
                                        @if($student->reports_count === 0 && $student->community_services_count === 0 && $student->citations_count === 0 && $student->notice_signatures_count === 0)
                                            <flux:button x-on:click.stop variant="ghost" size="sm" icon="trash" class="text-red-500" wire:click="confirmDelete('{{ $student->id }}')" />
                                        @else
                                            <flux:button x-on:click.stop variant="ghost" size="sm" icon="trash" class="text-zinc-300 dark:text-zinc-600" title="No se puede eliminar por historial asociado" disabled />
                                        @endif
                                    @endif

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

                <!-- Popover (fixed) -->
                <div x-show="show" class="z-50" x-bind:style="popoverStyle()" @click.away="hide()">
                    <div :class="popoverClass" class="bg-white dark:bg-zinc-900 rounded shadow-lg p-2 border border-zinc-200 dark:border-zinc-700" x-ref="popover">
                        <button class="w-full text-left px-3 py-2 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 hover:text-black dark:hover:text-black rounded" x-on:click="goToReport()">Reporte</button>
                        <button class="w-full text-left px-3 py-2 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 hover:text-black dark:hover:text-black rounded" x-on:click="goToService()">Servicio Comunitario</button>
                        <button class="w-full text-left px-3 py-2 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 hover:text-black dark:hover:text-black rounded" x-on:click="goToCitation()">Citatorio</button>
                    </div>
                </div>

            </div>
        </div>

        <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between text-sm text-zinc-500">
            <div>{{ $students->links() }}</div>
        </div>
    </div>

    @if(auth()->user()->isViewStaff())
        <!-- Student Modal -->
        <flux:modal wire:model="showStudentModal" class="w-full max-w-2xl">
            <!-- ... -->
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
                                                    <div class="whitespace-normal">
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

    <!-- Student History Modal -->
    <flux:modal wire:model="showHistoryModal" class="w-full max-w-3xl">
        <div class="space-y-6">
            <header>
                <flux:heading size="lg">Historial del Alumno</flux:heading>
                <flux:text class="uppercase font-bold">{{ $historyStudentName }}</flux:text>
            </header>

            <!-- Legend -->
            <div class="flex flex-wrap gap-3">
                <div class="flex items-center gap-1.5">
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                    <span class="text-xs text-zinc-600 dark:text-zinc-400">Reportes</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                    <span class="text-xs text-zinc-600 dark:text-zinc-400">Servicios Comunitarios</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                    <span class="text-xs text-zinc-600 dark:text-zinc-400">Citatorios</span>
                </div>
            </div>

            @if(count($historyItems) === 0)
                <div class="py-12 text-center border border-dashed rounded-2xl border-zinc-300 dark:border-zinc-700">
                    <flux:icon icon="check-circle" class="mx-auto text-emerald-400 mb-3" size="xl" />
                    <flux:heading size="md" class="text-zinc-400">Sin historial</flux:heading>
                    <flux:text size="sm" class="text-zinc-500">Este alumno no tiene reportes, servicios comunitarios ni citatorios registrados en el ciclo activo.</flux:text>
                </div>
            @else
                <div class="max-h-[60vh] overflow-y-auto pr-1 space-y-6">
                    @php
                        $grouped = collect($historyItems)->groupBy('date');
                    @endphp

                    @foreach($grouped as $date => $dateItems)
                        <div class="space-y-3">
                            <!-- Date Header -->
                            <div class="flex items-center gap-2 px-1 sticky top-0 bg-white dark:bg-zinc-800 py-1 z-10">
                                <flux:badge color="zinc" size="sm" inset="left">
                                    {{ $date ? \Carbon\Carbon::parse($date)->isoFormat('dddd') : 'N/A' }}
                                </flux:badge>
                                <flux:text size="sm" class="font-bold">
                                    {{ $dateItems->first()['date_display'] }}
                                </flux:text>
                            </div>

                            @foreach($dateItems as $item)
                                @if($item['type'] === 'report')
                                    {{-- Report Card - Red --}}
                                    <div class="p-4 rounded-xl border border-red-200 dark:border-red-900/50 bg-red-50 dark:bg-red-900/20">
                                        <div class="flex items-center gap-2 mb-2">
                                            <flux:icon icon="document-text" class="text-red-600" />
                                            <flux:badge size="xs" color="red" variant="outline">Reporte</flux:badge>
                                        </div>
                                        <div class="font-bold text-red-900 dark:text-red-100">{{ $item['title'] }}</div>
                                        @if($item['description'])
                                            <div class="text-xs text-red-600 dark:text-red-400 mt-1 line-clamp-2 italic">{{ $item['description'] }}</div>
                                        @endif
                                        @if($item['extra'])
                                            <div class="text-xs text-red-500 dark:text-red-400 mt-2">
                                                Reportado por: {{ $item['extra'] }}
                                            </div>
                                        @endif
                                        <div class="mt-2">
                                            @if($item['status'] === 'PENDING_SIGNATURE')
                                                <flux:badge size="sm" color="amber">Pendiente firma</flux:badge>
                                            @elseif($item['status'] === 'SIGNED')
                                                <flux:badge size="sm" color="green">Firmado</flux:badge>
                                            @elseif($item['status'] === 'PENDING')
                                                <flux:badge size="sm" color="amber">Pendiente firma</flux:badge>
                                            @else
                                                <flux:badge size="sm" color="zinc">{{ $item['status'] }}</flux:badge>
                                            @endif
                                        </div>
                                    </div>

                                @elseif($item['type'] === 'service')
                                    {{-- Service Card - Green --}}
                                    <div class="p-4 rounded-xl border border-green-200 dark:border-green-900/50 bg-green-50 dark:bg-green-900/20">
                                        <div class="flex items-center gap-2 mb-2">
                                            <flux:icon icon="briefcase" class="text-green-600" />
                                            <flux:badge size="xs" color="green" variant="outline">Servicio Comunitario</flux:badge>
                                        </div>
                                        <div class="font-bold text-green-900 dark:text-green-100">{{ $item['title'] }}</div>
                                        @if($item['description'])
                                            <div class="text-xs text-green-600 dark:text-green-400 mt-2 italic">{{ $item['description'] }}</div>
                                        @endif
                                        @if($item['extra'])
                                            <div class="text-xs text-green-500 dark:text-green-400 mt-2">
                                                Asignado por: {{ $item['extra'] }}
                                            </div>
                                        @endif
                                        <div class="mt-2">
                                            @if($item['status'] === 'PENDING')
                                                <flux:badge size="sm" color="amber">Pendiente</flux:badge>
                                            @elseif($item['status'] === 'COMPLETED')
                                                <flux:badge size="sm" color="green">Completado</flux:badge>
                                            @else
                                                <flux:badge size="sm" color="red">Incumplido</flux:badge>
                                            @endif
                                        </div>
                                    </div>

                                @elseif($item['type'] === 'citation')
                                    {{-- Citation Card - Amber --}}
                                    <div class="p-4 rounded-xl border border-amber-200 dark:border-amber-900/50 bg-amber-50 dark:bg-amber-900/20">
                                        <div class="flex items-center gap-2 mb-2">
                                            <flux:icon icon="calendar-days" class="text-amber-600" />
                                            <flux:badge size="xs" color="amber" variant="outline">Citatorio</flux:badge>
                                        </div>
                                        <div class="font-bold text-amber-900 dark:text-amber-100">{{ $item['title'] }}</div>
                                        @if($item['extra'])
                                            <div class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                                                Solicitado por: {{ $item['extra'] }}
                                            </div>
                                        @endif
                                        <div class="mt-2">
                                            @if($item['status'] === 'PENDING')
                                                <flux:badge size="sm" color="amber">Agendado</flux:badge>
                                            @elseif($item['status'] === 'ATTENDED')
                                                <flux:badge size="sm" color="green">Asistió</flux:badge>
                                            @else
                                                <flux:badge size="sm" color="red">No asistió</flux:badge>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="flex justify-end pt-2">
                <flux:button wire:click="$set('showHistoryModal', false)">Cerrar</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Deletion Confirmation Modal -->
    <flux:modal wire:model="showDeleteModal" class="min-w-80">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Confirmar Eliminación</flux:heading>
                <flux:subheading class="whitespace-normal wrap-break-word">
                    ¿Estás seguro de eliminar al alumno <span class="font-bold text-zinc-900 dark:text-white uppercase wrap-break-word">{{ $nameToDelete }}</span>?
                    Esta acción es irreversible.
                </flux:subheading>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">Cancelar</flux:button>
                <flux:button variant="danger" wire:click="deleteStudent">Eliminar Alumno</flux:button>
            </div>
        </div>
    </flux:modal>
</div>

<script>
function studentPopover(){
    return {
        show: false,
        x: 0,
        y: 0,
        width: 180,
        studentId: null,
        studentName: null,
        popoverClass: '',
        init(){
            // close popover when scrolling to avoid misplacement
            window.addEventListener('scroll', () => { if(this.show) this.hide(); }, true);
            window.addEventListener('resize', () => { if(this.show) this.hide(); });
        },
        popoverStyle(){
            return `position:fixed; left:${this.x}px; top:${this.y}px; width:${this.width}px; z-index:9999;`;
        },
        select(ev){
            // prevent the click from bubbling to the document and triggering @click.away
            ev.stopPropagation();
            const tr = ev.currentTarget;
            const rect = tr.getBoundingClientRect();
            this.studentId = tr.dataset.id;
            this.studentName = tr.dataset.name;

            const padding = 8;
            const estimatedHeight = 120; // approximate popover height

            // Responsive width: full width on small screens with small margins
            if (window.innerWidth <= 640) {
                this.width = Math.max(200, Math.min(window.innerWidth - 32, 360));
                this.popoverClass = '';
                // horizontal center
                this.x = Math.round((window.innerWidth - this.width) / 2);
            } else {
                this.width = 180;
                // center horizontally above the row
                let left = rect.left + (rect.width / 2) - (this.width / 2);
                if (left < padding) left = padding;
                if (left + this.width > window.innerWidth - padding) left = window.innerWidth - this.width - padding;
                this.x = Math.round(left);
            }

            // position above the row if there's space, otherwise below
            let top = rect.top - estimatedHeight - padding;
            if (top < 8) {
                top = rect.bottom + padding; // place below
            }

            // ensure popover not off-screen vertically
            if (top + estimatedHeight > window.innerHeight - padding) {
                top = Math.max(padding, window.innerHeight - estimatedHeight - padding);
            }

            this.y = Math.round(top + window.scrollY - window.scrollY); // keep fixed viewport coords
            this.show = true;
        },
        hide(){ this.show = false; this.studentId = null; this.studentName = null; },
        goToReport(){
            if(!this.studentId) return;
            const url = "{{ route('reports.index') }}" + "?open_create=1&student_id=" + encodeURIComponent(this.studentId) + "&student_name=" + encodeURIComponent(this.studentName);
            window.location.href = url;
        },
        goToService(){
            if(!this.studentId) return;
            const url = "{{ route('community-services.index') }}" + "?open_create=1&student_id=" + encodeURIComponent(this.studentId) + "&student_name=" + encodeURIComponent(this.studentName);
            window.location.href = url;
        },
        goToCitation(){
            if(!this.studentId) return;
            const url = "{{ route('citations.index') }}" + "?open_create=1&student_id=" + encodeURIComponent(this.studentId) + "&student_name=" + encodeURIComponent(this.studentName);
            window.location.href = url;
        }
    }
}
</script>