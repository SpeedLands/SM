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
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;

new class extends Component {
    use WithPagination;
    use WithFileUploads;

    public string $search = '';
    public string $gradeFilter = 'Todos';
    public string $groupFilter = 'Todos';
    public bool $onlyActiveCycle = true;
    public array $selectedStudents = [];
    public float $scale = 1.0;
    public bool $bulkMode = false;

    public function exitBulkMode(): void
    {
        $this->bulkMode = false;
        $this->selectedStudents = [];
    }

    public function toggleBulkModeForStudent(string $studentId): void
    {
        if ($this->bulkMode && count($this->selectedStudents) === 1 && $this->selectedStudents[0] === $studentId) {
            $this->exitBulkMode();
        } else {
            $this->bulkMode = true;
            $this->selectedStudents = [$studentId];
        }
    }

    public function selectAll(): void
    {
        $allIds = Student::pluck('id')->toArray();
        if (count($this->selectedStudents) === count($allIds)) {
            $this->selectedStudents = [];
        } else {
            $this->selectedStudents = $allIds;
        }
    }

    public function bulkPrint(): void
    {
        if (empty($this->selectedStudents)) {
            $this->dispatch('notify', ['variant' => 'warning', 'message' => 'Seleccione al menos un alumno.']);
            return;
        }

        $this->dispatch('bulk-print', [
            'ids' => $this->selectedStudents,
            'scale' => $this->scale,
        ]);
    }

    // Student Modal State
    public bool $showStudentModal = false;
    public string $studentId = '';
    public string $previewStudentId = '';
    
    // Core Student Fields
    public string $name = '';
    public string $curp = '';
    public string $birthDate = '';
    public string $turn = 'MATUTINO';
    public int $siblingsCount = 0;
    public int $birthOrder = 1;
    public $photo;
    public ?string $currentPhotoUrl = null;
    
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
    public string $historyStudentId = '';
    public bool $historyOnlyActiveCycle = true;
    public array $historyItems = [];

    public function mount(): void
    {
        $this->birthDate = now()->subYears(12)->format('Y-m-d');
    }

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
        $this->reset(['studentId', 'name', 'curp', 'birthDate', 'turn', 'siblingsCount', 'birthOrder', 'classGroupId', 'address', 'allergies', 'medicalConditions', 'emergencyContact', 'otherContact', 'motherName', 'fatherName', 'motherWorkplace', 'fatherWorkplace', 'photo', 'currentPhotoUrl']);
        $this->birthDate = now()->subYears(12)->format('Y-m-d');
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
        $this->historyStudentId = $id;
        $this->historyOnlyActiveCycle = true;
        $this->loadHistoryItems();
        $this->showHistoryModal = true;
    }

    public function updatedHistoryOnlyActiveCycle(): void
    {
        $this->loadHistoryItems();
    }

    protected function loadHistoryItems(): void
    {
        $id = $this->historyStudentId;
        if (!$id) return;

        $activeCycle = Cycle::where('is_active', true)->first();
        $filterCycle = $this->historyOnlyActiveCycle;
        $items = collect();

        // Reports
        $reports = Report::with('teacher', 'infraction')
            ->where('student_id', $id)
            ->when($filterCycle && $activeCycle, fn($q) => $q->where('cycle_id', $activeCycle->id))
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
            ->when($filterCycle && $activeCycle, fn($q) => $q->where('cycle_id', $activeCycle->id))
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
            ->when($filterCycle && $activeCycle, fn($q) => $q->where('cycle_id', $activeCycle->id))
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
    }



    public function editStudent(string $id): void
    {
        if (!auth()->user()->isViewStaff()) abort(403);
        $this->authorize('teacher-or-admin');
        $student = Student::with(['pii', 'currentCycleAssociation', 'parents'])->findOrFail($id);
        
        $this->studentId = $student->id;
        $this->name = $student->name;
        $this->curp = $student->curp ?? '';
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

        $this->photo = null;
        $this->currentPhotoUrl = $student->photo_url;

        $this->parentSearch = '';
        $this->selectedParentId = '';
        $this->showStudentModal = true;
    }



    protected array $rules = [
        'name' => 'required|string|max:100',
        'curp' => 'nullable|string|size:18|unique:students,curp',
        'birthDate' => 'required|date', // Changed from birth_date to birthDate to match property
        // 'gender' => 'required|in:M,F', // Not present in current properties
        // 'blood_type' => 'nullable|string|max:5', // Not present in current properties
        'allergies' => 'nullable|string',
        'emergencyContact' => 'nullable|string|max:20', // Changed from emergency_contact_phone
        // 'emergency_contact_name' => 'required|string|max:100', // Not present in current properties
        // 'grade' => 'required', // Grade is derived from classGroup, not directly set
        'classGroupId' => 'required|exists:class_groups,id', // Matches property
        'turn' => 'required|in:MATUTINO,VESPERTINO',
        'photo' => 'nullable|image|max:2048', // 2MB Max
    ];

    protected array $messages = [
        'name.required' => 'El nombre completo es obligatorio.',
        'curp.required' => 'El CURP es obligatorio.',
        'curp.size' => 'El CURP debe tener exactamente 18 caracteres.',
        'curp.unique' => 'Este CURP ya está registrado.',
        'birthDate.required' => 'La fecha de nacimiento es obligatoria.',
        // 'gender.required' => 'El género es obligatorio.',
        // 'emergency_contact_name.required' => 'El nombre del contacto de emergencia es obligatorio.',
        'emergencyContact.max' => 'El teléfono de emergencia no debe exceder 20 caracteres.',
        // 'grade.required' => 'El grado es obligatorio.',
        'classGroupId.required' => 'El grupo es obligatorio.',
        'turn.required' => 'El turno es obligatorio.',
        'photo.image' => 'El archivo debe ser una imagen.',
        'photo.max' => 'La imagen no debe pesar más de 2MB.',
    ];

    public function rules(): array
    {
        $rules = $this->rules;
        if ($this->studentId) { // Assuming studentId is used for editing
            $rules['curp'] = 'nullable|string|size:18|unique:students,curp,' . $this->studentId;
        }
        return $rules;
    }

    public function save(): void
    {
        if (!auth()->user()->isViewStaff()) abort(403);
        $this->authorize('teacher-or-admin');
        $this->validate($this->rules(), $this->messages);

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
                'curp' => $this->curp ? strtoupper($this->curp) : null,
                'birth_date' => $this->birthDate ?: now()->subYears(12)->format('Y-m-d'),
                'grade' => $group->grade,
                'group_name' => $group->section,
                'turn' => $this->turn,
            ]);
        } else {
            $student = Student::create([
                'id' => (string) Str::uuid(),
                'name' => strtoupper($this->name),
                'curp' => $this->curp ? strtoupper($this->curp) : null,
                'birth_date' => $this->birthDate ?: now()->subYears(12)->format('Y-m-d'),
                'grade' => $group->grade,
                'group_name' => $group->section,
                'turn' => $this->turn,
            ]);
            $this->studentId = $student->id; // Set ID for new student so parents can be added
        }

        if ($this->photo) {
            $path = $this->photo->store('students/photos', 'public');
            $student->update(['photo_path' => $path]);
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

<div x-data="studentPopover()" x-init="init()" class="space-y-6 text-zinc-900 dark:text-white pb-10">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div data-tour="students-heading">
            <flux:heading size="xl" level="1">Gestión de Alumnos</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">Administre el padrón de estudiantes, sus datos de contacto y su situación académica.</flux:text>
        </div>
        @if(auth()->user()->isViewStaff())
            <flux:button variant="primary" icon="user-plus" wire:click="openCreateModal" :disabled="count($classGroups) === 0" data-tour="students-create-btn">Inscribir Alumno</flux:button>
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

    {{-- Filtros Rápidos (Pills for mobile style) --}}
    <div class="flex flex-wrap gap-2 sm:hidden pb-2 overflow-x-auto no-scrollbar">
        @if($search) <flux:badge variant="solid" color="zinc" class="shrink-0">"{{ $search }}"</flux:badge> @endif
        @if($gradeFilter !== 'Todos') <flux:badge variant="solid" color="zinc" class="shrink-0">{{ $gradeFilter }}</flux:badge> @endif
        @if($groupFilter !== 'Todos') <flux:badge variant="solid" color="zinc" class="shrink-0">Sección {{ $groupFilter }}</flux:badge> @endif
        @if($onlyActiveCycle) <flux:badge variant="solid" color="zinc" class="shrink-0">Ciclo Activo</flux:badge> @endif
        <flux:button variant="ghost" size="xs" icon="funnel" class="ml-auto" title="Mostrar/ocultar filtros" x-on:click="$refs.filterPanel.classList.toggle('hidden')" />
    </div>

    <!-- Search and Filters -->
    <div x-ref="filterPanel" id="students-filter-panel" class="hidden sm:block p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm space-y-6 transition-all">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <flux:heading size="lg" level="2">Filtros</flux:heading>
            <flux:switch wire:model.live="onlyActiveCycle" label="Solo mostrar inscritos en ciclo actual" />
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <flux:field data-tour="students-search">
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

    <!-- Mobile Cards (Staff View) -->
    <div class="space-y-4 sm:hidden pb-10" data-tour="students-table-mobile">
        @forelse($students as $student)
            <div wire:key="std-mob-{{ $student->id }}" class="p-4 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm relative">
                <div class="flex justify-between items-start mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center shrink-0 overflow-hidden border border-zinc-200 dark:border-zinc-700">
                            @if($student->photo_url)
                                <img src="{{ $student->photo_url }}" class="w-full h-full object-cover">
                            @else
                                <flux:icon icon="user" class="text-indigo-600 dark:text-indigo-400" variant="solid" />
                            @endif
                        </div>
                        <div class="flex flex-col">
                            <flux:text size="sm" class="font-bold uppercase">{{ $student->name }}</flux:text>
                            <flux:text size="xs" class="text-zinc-500 font-mono">{{ $student->curp }}</flux:text>
                        </div>
                    </div>
                    <div class="flex flex-col items-end">
                        <div class="flex gap-1 mb-1">
                            <flux:badge size="xs" color="blue">{{ $student->grade }}</flux:badge>
                            <flux:badge size="xs" color="neutral">{{ $student->group_name }}</flux:badge>
                        </div>
                        <flux:badge size="xs" variant="outline" color="{{ $student->turn === 'MATUTINO' ? 'sky' : 'orange' }}">
                            {{ $student->turn }}
                        </flux:badge>
                    </div>

                    @if($bulkMode)
                        <div class="ml-4 pt-1">
                            <flux:checkbox wire:model.live="selectedStudents" value="{{ $student->id }}" :disabled="!$student->curp" />
                        </div>
                    @endif
                </div>
                

                <div class="flex justify-end gap-1 pt-3 border-t border-zinc-100 dark:border-zinc-800" data-tour="students-actions-mobile">
                                    @if(auth()->user()->isAdmin())
                                        <flux:button variant="ghost" size="xs" icon="identification" 
                                            :title="$student->curp ? 'Selección para credencial' : 'Falta CURP para generar credencial'" 
                                            :disabled="!$student->curp"
                                            wire:click.stop="toggleBulkModeForStudent('{{ $student->id }}')" 
                                        />
                                    @endif
                    <flux:button variant="ghost" size="xs" icon="eye" wire:click="viewHistory('{{ $student->id }}')" title="Ver historial" />
                    @if(auth()->user()->isViewStaff())
                        {{-- Mobile quick actions --}}
                        <flux:dropdown>
                            <flux:button variant="ghost" size="xs" icon="plus-circle" title="Crear reporte, servicio o citatorio" />
                            <flux:menu>
                                <flux:menu.item icon="document-text" x-on:click="studentId = '{{ $student->id }}'; studentName = '{{ $student->name }}'; goToReport()">Generar Reporte</flux:menu.item>
                                <flux:menu.item icon="briefcase" x-on:click="studentId = '{{ $student->id }}'; studentName = '{{ $student->name }}'; goToService()">Servicio Comunitario</flux:menu.item>
                                <flux:menu.item icon="calendar-days" x-on:click="studentId = '{{ $student->id }}'; studentName = '{{ $student->name }}'; goToCitation()">Citatorio</flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item icon="clock" wire:click="viewHistory('{{ $student->id }}')">Ver Historial</flux:menu.item>
                                <flux:menu.item icon="pencil" wire:click="editStudent('{{ $student->id }}')">Editar Datos</flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>

                        @if($student->reports_count === 0 && $student->community_services_count === 0 && $student->citations_count === 0 && $student->notice_signatures_count === 0)
                            <flux:button variant="ghost" size="xs" icon="trash" class="text-red-500" wire:click="confirmDelete('{{ $student->id }}')" />
                        @else
                            <flux:button variant="ghost" size="xs" icon="trash" class="text-zinc-300 dark:text-zinc-600" disabled />
                        @endif
                    @endif
                </div>
            </div>
        @empty
            <div class="py-12 text-center text-zinc-500 italic bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-dashed border-zinc-300">
                No se encontraron alumnos coincidentes
            </div>
        @endforelse
        <div class="mt-4">
            {{ $students->links() }}
        </div>
    </div>

    <!-- Students Table (Desktop View) -->
    <div class="hidden sm:block p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm overflow-hidden" data-tour="students-table-desktop">
        <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700 text-xs uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        @if($bulkMode)
                        <th class="py-3 px-2 w-10">
                            {{-- We only count students WITH CURP for the select all comparison, as others can't be selected --}}
                            <flux:checkbox wire:click="selectAll" :checked="count($selectedStudents) > 0 && count($selectedStudents) === \App\Models\Student::whereNotNull('curp')->count()" />
                        </th>
                        @endif
                        <th class="py-3 px-2 font-semibold">Alumno</th>
                        <th class="py-3 px-2 font-semibold text-center">Grado / Grupo</th>
                        <th class="py-3 px-2 font-semibold text-center">Turno</th>
                        <th class="py-3 px-2 text-right font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($students as $student)
                        <tr wire:key="std-desk-{{ $student->id }}" 
                        @if(auth()->user()->isViewStaff())
                            class="hover:bg-zinc-800/5 dark:hover:bg-white/5 transition-colors cursor-pointer {{ in_array($student->id, $selectedStudents) ? 'bg-indigo-50 dark:bg-indigo-900/10' : '' }}"
                        @else
                            class="hover:bg-zinc-800/5 dark:hover:bg-white/5 transition-colors"
                        @endif
                        >
                            @if($bulkMode)
                            <td class="py-4 px-2" x-on:click.stop>
                                <flux:checkbox wire:model.live="selectedStudents" value="{{ $student->id }}" :disabled="!$student->curp" />
                            </td>
                            @endif
                            <td class="py-4 px-2"
                                @if(auth()->user()->isViewStaff())
                                    x-on:click="select($event)" data-id="{{ $student->id }}" data-name="{{ $student->name }}"
                                @endif
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center overflow-hidden">
                                        @if($student->photo_url)
                                            <img src="{{ $student->photo_url }}" class="w-full h-full object-cover">
                                        @else
                                            <flux:icon icon="user" class="text-indigo-600 dark:text-indigo-400" variant="solid" />
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-bold text-zinc-900 dark:text-white uppercase">{{ $student->name }}</div>
                                        @if($student->curp)
                                            <div class="text-[10px] font-mono text-zinc-400 uppercase tracking-tighter">{{ $student->curp }}</div>
                                        @endif
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
                                <div class="flex justify-end gap-1" data-tour="students-actions-desktop">
                                    @if(auth()->user()->isAdmin())
                                        <flux:button variant="ghost" size="sm" icon="identification" 
                                            :title="$student->curp ? 'Selección para credencial' : 'Falta CURP para generar credencial'" 
                                            :disabled="!$student->curp"
                                            wire:click.stop="toggleBulkModeForStudent('{{ $student->id }}')" 
                                        />
                                    @endif
                                    <flux:button x-on:click.stop variant="ghost" size="sm" icon="eye" wire:click="viewHistory('{{ $student->id }}')" title="Ver historial" />
                                    @if(auth()->user()->isViewStaff())
                                        <flux:button x-on:click.stop variant="ghost" size="sm" icon="pencil" wire:click="editStudent('{{ $student->id }}')" title="Editar alumno" />
                                        @if($student->reports_count === 0 && $student->community_services_count === 0 && $student->citations_count === 0 && $student->notice_signatures_count === 0)
                                            <flux:button x-on:click.stop variant="ghost" size="sm" icon="trash" class="text-red-500" wire:click="confirmDelete('{{ $student->id }}')" title="Eliminar alumno" />
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
                        
                        <div class="flex flex-col md:flex-row gap-6">
                            <div class="flex flex-col items-center gap-2">
                                <flux:label>Foto del Alumno</flux:label>
                                <div class="w-32 h-40 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 flex items-center justify-center overflow-hidden relative group">
                                    @if($photo)
                                        <img src="{{ $photo->temporaryUrl() }}" class="w-full h-full object-cover">
                                    @elseif($currentPhotoUrl)
                                        <img src="{{ $currentPhotoUrl }}" class="w-full h-full object-cover">
                                    @else
                                        <flux:icon icon="user" size="xl" class="text-zinc-300 dark:text-zinc-600" variant="solid" />
                                    @endif

                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer" onclick="document.getElementById('student-photo-input').click()">
                                        <flux:icon icon="pencil" class="text-white" />
                                    </div>
                                    
                                    <div wire:loading.flex wire:target="photo" class="absolute inset-0 bg-white/80 dark:bg-zinc-900/80 items-center justify-center z-10 rounded-xl">
                                        <flux:icon icon="arrow-path" class="size-8 animate-spin text-indigo-600 dark:text-indigo-400" />
                                    </div>
                                </div>
                                <input type="file" id="student-photo-input" class="hidden" wire:model="photo" accept="image/*">
                                <flux:error name="photo" />
                            </div>

                            <div class="grow grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <flux:input 
                                        label="Nombre Completo" 
                                        wire:model="name" 
                                        placeholder="Ej. JUAN PEREZ LOPEZ" 
                                        class="uppercase"
                                        x-on:input="name = $event.target.value.toUpperCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^A-Z ]/g, '')"
                                    />
                                    <flux:error name="name" />
                                </div>
                                <div>
                                    <flux:input 
                                        label="CURP" 
                                        wire:model="curp" 
                                        placeholder="ABCD010101XXXXX000" 
                                        class="uppercase"
                                        x-on:input="curp = $event.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 18)"
                                    />
                                    <flux:error name="curp" />
                                </div>
                                <div class="flex flex-col gap-1">
                                    <flux:select label="Turno" wire:model="turn">
                                        <option value="MATUTINO">Matutino</option>
                                        <option value="VESPERTINO">Vespertino</option>
                                    </flux:select>
                                    <flux:error name="turn" />
                                </div>
                                <div class="md:col-span-2 flex flex-col gap-1">
                                    <flux:select label="Grupo / Grado" wire:model="classGroupId">
                                        <option value="">Seleccione grupo...</option>
                                        @foreach($classGroups as $group)
                                            <option value="{{ $group->id }}">{{ $group->grade }} {{ $group->section }}</option>
                                        @endforeach
                                    </flux:select>
                                    <flux:error name="classGroupId" />
                                </div>
                            </div>
                        </div>
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
    @endif

    <!-- Student History Modal -->
    <flux:modal wire:model="showHistoryModal" class="w-full max-w-3xl">
        <div class="space-y-6">
            <header>
                <flux:heading size="lg">Historial del Alumno</flux:heading>
                <flux:text class="uppercase font-bold">{{ $historyStudentName }}</flux:text>
            </header>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
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
                <flux:switch wire:model.live="historyOnlyActiveCycle" label="Solo ciclo activo" />
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

    <!-- Shared Popover (floating for Desktop) -->
    <div x-show="show" x-cloak class="z-50" x-bind:style="popoverStyle()" @click.away="hide()">
        <div :class="popoverClass" class="bg-white dark:bg-zinc-900 rounded shadow-lg p-2 border border-zinc-200 dark:border-zinc-700" x-ref="popover">
            <button type="button" class="w-full text-left px-3 py-2 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 hover:text-black dark:hover:text-black rounded" x-on:click="goToReport()">Reporte</button>
            <button type="button" class="w-full text-left px-3 py-2 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 hover:text-black dark:hover:text-black rounded" x-on:click="goToService()">Servicio Comunitario</button>
            <button type="button" class="w-full text-left px-3 py-2 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 hover:text-black dark:hover:text-black rounded" x-on:click="goToCitation()">Citatorio</button>
        </div>
    </div>

    @if(auth()->user()->isAdmin())
        <!-- Bulk Actions Bar -->
        <div 
            x-show="$wire.selectedStudents.length > 0"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-10"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 w-full max-w-2xl px-4"
        >
            <div class="bg-zinc-900 text-white p-4 rounded-2xl shadow-2xl flex flex-col md:flex-row items-center justify-between gap-4 md:gap-6 border border-zinc-800">
                <div class="flex items-center gap-4 border-b md:border-b-0 md:border-r border-zinc-800 pb-3 md:pb-0 md:pr-6 w-full md:w-auto">
                    <flux:badge color="blue" size="sm" variant="solid" class="rounded-full h-8 w-8 flex items-center justify-center p-0 shrink-0">
                        <span x-text="$wire.selectedStudents.length"></span>
                    </flux:badge>
                    <div class="flex flex-col">
                        <span class="text-xs text-zinc-400 font-bold uppercase shrink-0">Seleccionados</span>
                        <span class="text-sm">Imprimir</span>
                    </div>
                </div>

                <div class="flex items-center gap-4 grow w-full">
                    <div class="flex flex-col grow min-w-0">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-[10px] text-zinc-500 uppercase font-black">Escala</span>
                            <span class="text-xs font-mono" x-text="Math.round($wire.scale * 100) + '%'"></span>
                        </div>
                        <input type="range" wire:model.live="scale" min="0.5" max="2.0" step="0.1" class="w-full h-1 bg-zinc-700 rounded-lg appearance-none cursor-pointer accent-blue-500">
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <flux:button 
                            variant="primary" 
                            size="sm"
                            icon="printer" 
                            type="submit"
                            form="bulk-print-form"
                        >
                            <span class="hidden sm:inline">Generar PDF</span>
                            <span class="sm:hidden">PDF</span>
                        </flux:button>
                        
                        <flux:button 
                            variant="ghost" 
                            size="sm" 
                            icon="x-mark" 
                            wire:click="exitBulkMode"
                            title="Cancelar selección"
                        />
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(auth()->user()->isAdmin())
        <!-- Hidden form for bulk printing (native submission to bypass popup blockers) -->
        <form id="bulk-print-form" method="POST" action="{{ route('students.credential.bulk') }}" target="_blank" class="hidden">
            @csrf
            @foreach($selectedStudents as $id)
                <input type="hidden" name="ids[]" value="{{ $id }}">
            @endforeach
            <input type="hidden" name="scale" value="{{ $scale }}">
        </form>
    @endif
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