<?php

use App\Http\Requests\StoreReportRequest;
use App\Models\Report;
use App\Models\Student;
use App\Models\Infraction;
use App\Models\Cycle;
use App\Models\CommunityService;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;

    // Filters
    public string $search = '';
    public string $status = '';
    public string $severity = '';
    public string $gradeFilter = '';
    public string $groupFilter = '';
    public bool $onlyActiveCycle = true;
    public bool $onlyPending = false;

    // Modal state
    public bool $showReportModal = false;
    public bool $showDeleteModal = false;
    public ?Report $editingReport = null;
    public ?string $reportIdToDelete = null;

    // Form fields
    public string $studentSearch = '';
    public ?string $selectedStudentId = null;
    public ?string $teacherId = null;
    public ?string $infractionId = null;
    public string $subject = '';
    public string $description = '';
    public string $reportDate = '';
    public string $reportTime = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingSeverity(): void
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

    public function updatingOnlyPending(): void
    {
        $this->resetPage();
    }

    public function updatingOnlyActiveCycle(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        $this->teacherId = auth()->id();
        $this->reportDate = now()->format('Y-m-d');
        $this->reportTime = now()->format('H:i');
        // Open create modal automatically when navigated with query params
        if (request()->query('open_create')) {
            $this->selectedStudentId = request()->query('student_id');
            $this->studentSearch = request()->query('student_name') ?? '';
            $this->showReportModal = true;
        }

        if ($search = request()->query('search')) {
            $this->search = $search;
        }

        if ($grade = request()->query('gradeFilter')) {
            $this->gradeFilter = $grade;
        }

        if ($group = request()->query('groupFilter')) {
            $this->groupFilter = $group;
        }
    }

    public function openCreateModal(): void
    {
        $this->authorize('teacher-or-admin');
        $this->resetValidation();
        $this->resetForm();
        $this->showReportModal = true;
    }

    public function resetForm(): void
    {
        $this->reset(['editingReport', 'selectedStudentId', 'studentSearch', 'infractionId', 'subject', 'description']);
        $this->teacherId = auth()->id();
        $this->reportDate = now()->format('Y-m-d');
        $this->reportTime = now()->format('H:i');
    }

    public function selectStudent(string $id): void
    {
        $this->selectedStudentId = $id;
        $this->studentSearch = Student::find($id)->name;
    }

    public function editReport(string $id): void
    {
        $this->authorize('teacher-or-admin');
        $this->resetValidation();

        $report = Report::findOrFail($id);
        $this->editingReport = $report;
        $this->selectedStudentId = $report->student_id;
        $this->studentSearch = $report->student->name;
        $this->teacherId = $report->teacher_id;
        $this->infractionId = $report->infraction_id;
        $this->subject = $report->subject ?? '';
        $this->description = $report->description;
        $this->reportDate = Carbon::parse($report->date)->format('Y-m-d');
        $this->reportTime = Carbon::parse($report->date)->format('H:i');

        $this->showReportModal = true;
    }

    public function save(): void
    {
        $this->authorize('teacher-or-admin');

        $this->validate([
            'selectedStudentId' => 'required|exists:students,id',
            'infractionId' => 'required|exists:infractions,id',
            'subject' => 'nullable|string|max:255',
            'description' => 'required|string|max:1000',
            'reportDate' => [
                'required',
                'date',
                'before_or_equal:tomorrow',
                function ($attribute, $value, $fail) {
                    if ($value && \Carbon\Carbon::parse($value)->isWeekend()) {
                        $fail('No se pueden registrar reportes para fines de semana.');
                    }
                },
            ],
            'reportTime' => 'required',
        ], [
            'selectedStudentId.required' => 'Debe seleccionar un alumno.',
            'infractionId.required' => 'Debe seleccionar un tipo de infracción.',
            'description.required' => 'La descripción es obligatoria.',
            'reportDate.required' => 'La fecha del reporte es obligatoria.',
            'reportDate.before_or_equal' => 'La fecha del reporte debe ser una fecha anterior o igual a mañana.',
            'reportTime.required' => 'La hora del reporte es obligatoria.',
        ], [
            'selectedStudentId' => 'alumno',
            'infractionId' => 'tipo de infracción',
            'subject' => 'asunto',
            'description' => 'descripción',
            'reportDate' => 'fecha del reporte',
            'reportTime' => 'hora del reporte',
        ]);

        $activeCycle = Cycle::where('is_active', true)->first();

        if (!$activeCycle) {
            $this->dispatch('notify', ['message' => 'No hay un ciclo activo configurado.', 'variant' => 'danger']);
            return;
        }

        $reportDateTime = Carbon::parse($this->reportDate . ' ' . $this->reportTime);

        $data = [
            'cycle_id' => $activeCycle->id,
            'student_id' => $this->selectedStudentId,
            'teacher_id' => $this->teacherId,
            'infraction_id' => $this->infractionId,
            'subject' => $this->subject,
            'description' => $this->description,
            'date' => $reportDateTime,
        ];

        if ($this->editingReport) {
            $this->editingReport->update($data);
            $message = 'Reporte actualizado exitosamente.';
        } else {
            $data['status'] = 'PENDING_SIGNATURE';
            $report = Report::create($data);
            $message = 'Reporte registrado exitosamente.';

            // Notify parents via FCM asíncronamente (Hallazgo #3 y #6)
            $student = Student::with('parents')->find($this->selectedStudentId);
            $infraction = Infraction::find($this->infractionId);
            $parentIds = $student->parents->pluck('id')->toArray();

            if (!empty($parentIds)) {
                \App\Jobs\SendBulkFcmNotifications::dispatch(
                    $parentIds,
                    'Nuevo Reporte Disciplinario',
                    "Se ha registrado un reporte para {$student->name}: {$infraction->description}",
                    [],
                    route('reports.index')
                );
            }

            // "Rule of 3" Check
            $this->checkCommunityServiceTrigger($this->selectedStudentId, $activeCycle->id);
        }

        $this->showReportModal = false;
        $this->resetForm();
        $this->dispatch('notify', ['message' => $message]);
    }

    protected function checkCommunityServiceTrigger(string $studentId, int $cycleId): void
    {
        $reportsCount = Report::countForStudentInCycle($studentId, $cycleId);

        // Every 3rd report triggers a suggested community service
        if ($reportsCount > 0 && $reportsCount % 3 === 0) {
            // Check if one is already pending for this count to avoid duplicates if re-triggered
            // For now, we skip duplicates. In a real scenario, we might want to be more specific.

            $student = Student::find($studentId);
            $this->dispatch('community-service-suggested', [
                'student_name' => $student->name,
                'count' => $reportsCount
            ]);
        }
    }

    public function confirmDelete(string $id): void
    {
        $this->authorize('teacher-or-admin');
        $this->reportIdToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteReport(): void
    {
        $this->authorize('teacher-or-admin');

        if (!$this->reportIdToDelete) {
            return;
        }

        $report = Report::findOrFail($this->reportIdToDelete);

        if ($report->status === 'SIGNED') {
            $this->dispatch('notify', ['message' => 'No se puede eliminar un reporte que ya ha sido firmado.', 'variant' => 'danger']);
            $this->showDeleteModal = false;
            $this->reportIdToDelete = null;
            return;
        }

        $report->delete();
        $this->showDeleteModal = false;
        $this->reportIdToDelete = null;
        $this->dispatch('notify', ['message' => 'Reporte eliminado correctamente.']);
    }

    public function signReport(string $id): void
    {
        $this->authorize('parent-only');

        $report = Report::findOrFail($id);

        // Ensure the report belongs to one of the parent's students
        $parentStudentIds = auth()->user()->students->pluck('id')->toArray();
        if (!in_array($report->student_id, $parentStudentIds)) {
            abort(403, 'No tiene permiso para firmar este reporte.');
        }

        $report->update([
            'status' => 'SIGNED',
            'signed_at' => now(),
            'signed_by_parent_id' => auth()->id(),
        ]);

        $this->dispatch('navigation-refresh');
        $this->dispatch('notify', ['message' => 'Reporte firmado correctamente.']);
    }

    public function updatingStudentSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $activeCycle = Cycle::where('is_active', true)->first();

        $reports = Report::with(['student', 'teacher', 'infraction', 'parent'])
            ->select('reports.*')
            ->join('students', 'reports.student_id', '=', 'students.id')
            ->when(auth()->user()->isViewParent(), function ($q) {
                $q->join('student_parents', 'reports.student_id', '=', 'student_parents.student_id')
                    ->where('student_parents.parent_id', auth()->id());
            })
            ->when($this->onlyActiveCycle && $activeCycle, fn($q) => $q->where('reports.cycle_id', $activeCycle->id))
            ->when($this->status, fn($q) => $q->where('reports.status', $this->status))
            ->when($this->severity, function ($q) {
                $q->join('infractions', 'reports.infraction_id', '=', 'infractions.id')
                    ->where('infractions.severity', $this->severity);
            })
            ->when($this->gradeFilter, fn($q) => $q->where('students.grade', $this->gradeFilter))
            ->when($this->groupFilter, fn($q) => $q->where('students.group_name', $this->groupFilter))
            ->when($this->onlyPending, fn($q) => $q->where('reports.status', 'PENDING_SIGNATURE'))
            ->when($this->search, function ($q) {
                $q->where(function ($sq) {
                    $sq->where('students.name', 'like', "%{$this->search}%")
                        ->orWhere('reports.subject', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('reports.date', 'desc')
            ->paginate(10);

        $studentResults = [];
        if (strlen($this->studentSearch) >= 3 && !$this->selectedStudentId) {
            $studentResults = Student::where('name', 'like', "%{$this->studentSearch}%")
                ->limit(5)
                ->get();
        }

        // Cache infractions for 1 hour to avoid repeated DB calls on every render
        $infractions = cache()->remember('infractions_all', 3600, fn() => Infraction::orderBy('description')->get());

        return [
            'reports' => $reports,
            'infractions' => $infractions,
            'studentResults' => $studentResults,
            'activeCycle' => $activeCycle,
            'availableGroups' => $activeCycle ? \App\Models\ClassGroup::where('cycle_id', $activeCycle->id)->select('section')->distinct()->orderBy('section')->get() : collect(),
            'staffMembers' => auth()->user()->isViewStaff() ? \App\Models\User::whereIn('role', ['ADMIN', 'TEACHER'])->orderBy('name')->get() : collect(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Reportes Disciplinarios</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">Seguimiento de conducta y faltas al reglamento.</flux:text>
        </div>
        @if(auth()->user()->isViewStaff())
        <div class="flex flex-col gap-2 w-full sm:w-auto">
            @can('admin-only')
            <flux:button variant="ghost" icon="cog-6-tooth" href="{{ route('infractions.index') }}" wire:navigate class="w-full sm:w-auto justify-center">Gestionar Tipos</flux:button>
            @endcan
            <flux:button variant="primary" icon="plus-circle" wire:click="openCreateModal" class="w-full sm:w-auto">Nuevo Reporte</flux:button>
        </div>
        @endif
    </div>

    <x-filter-bar class="mb-6">
        <x-slot:pills>
            @if($search) <flux:badge variant="solid" color="zinc" class="shrink-0">"{{ $search }}"</flux:badge> @endif
            @if($status)
            <flux:badge variant="solid" color="zinc" class="shrink-0">
                {{ match($status) { 'PENDING_SIGNATURE' => 'Pendiente de Firma', 'SIGNED' => 'Firmado', default => $status } }}
            </flux:badge>
            @endif
            @if($severity)
            <flux:badge variant="solid" color="zinc" class="shrink-0">
                {{ match($severity) { 'NORMAL' => 'Normal', 'GRAVE' => 'Grave', default => $severity } }}
            </flux:badge>
            @endif
            @if($gradeFilter) <flux:badge variant="solid" color="zinc" class="shrink-0">Grado: {{ $gradeFilter }}</flux:badge> @endif
            @if($groupFilter) <flux:badge variant="solid" color="zinc" class="shrink-0">Grupo: {{ $groupFilter }}</flux:badge> @endif
            @if($onlyActiveCycle) <flux:badge variant="solid" color="zinc" class="shrink-0">Ciclo Activo</flux:badge> @endif
            @if($onlyPending) <flux:badge variant="solid" color="zinc" class="shrink-0">Pendientes</flux:badge> @endif
        </x-slot:pills>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <flux:field class="md:col-span-2">
                <flux:label>Búsqueda</flux:label>
                <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Buscar por alumno o asunto..." />
            </flux:field>

            <flux:field>
                <flux:label>Estado</flux:label>
                <flux:select wire:model.live="status">
                    <option value="">Todos los estados</option>
                    <option value="PENDING_SIGNATURE">Pendiente de Firma</option>
                    <option value="SIGNED">Firmado</option>
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Gravedad</flux:label>
                <flux:select wire:model.live="severity">
                    <option value="">Todas las gravedades</option>
                    <option value="NORMAL">Normal</option>
                    <option value="GRAVE">Grave</option>
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Grado</flux:label>
                <flux:select wire:model.live="gradeFilter">
                    <option value="">Todos los grados</option>
                    <option value="1º">1º</option>
                    <option value="2º">2º</option>
                    <option value="3º">3º</option>
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Grupo</flux:label>
                <flux:select wire:model.live="groupFilter">
                    <option value="">Todos los grupos</option>
                    @foreach($availableGroups as $group)
                        <option value="{{ $group->section }}">{{ $group->section }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            <div class="md:col-span-4 mt-2">
                @if(auth()->user()->isViewParent())
                <flux:checkbox wire:model.live="onlyPending" label="Solo pendientes de firma" />
                @else
                <flux:switch wire:model.live="onlyActiveCycle" label="Solo mostrar reportes del ciclo activo" />
                @endif
            </div>
        </div>
    </x-filter-bar>

    @if(auth()->user()->isViewStaff())
    <!-- Reports Table (Staff View) -->
    <!-- Mobile Cards (Staff View) -->
    <div class="space-y-4 sm:hidden pb-10">
        @forelse ($reports as $report)
        <div wire:key="rep-mob-{{ $report->id }}" class="p-4 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm relative">
            <div class="flex justify-between items-start mb-3">
                <div class="flex flex-col">
                    <flux:text size="sm" class="font-bold">{{ $report->student->name }}</flux:text>
                    <flux:text size="xs" class="text-zinc-500">{{ $report->date->format('d/m/Y H:i') }}</flux:text>
                </div>
                <div class="flex flex-col items-end">
                    @if($report->status === 'SIGNED')
                    <flux:badge color="green" size="xs">Firmado</flux:badge>
                    @else
                    <flux:badge color="amber" size="xs">Pendiente</flux:badge>
                    @endif
                </div>
            </div>

            <div class="mb-4 bg-red-50/30 dark:bg-zinc-800/50 p-3 rounded-lg border border-red-50 dark:border-zinc-800 text-xs text-zinc-700 dark:text-zinc-300">
                <div class="flex items-center gap-2 mb-1">
                    <flux:badge size="xs" color="{{ $report->infraction->severity === 'GRAVE' ? 'red' : 'blue' }}" variant="solid" class="scale-90 origin-left">
                        {{ $report->infraction->severity === 'GRAVE' ? 'Grave' : 'Normal' }}
                    </flux:badge>
                    <div class="font-bold text-blue-600 dark:text-blue-400 truncate">{{ $report->infraction->description }}</div>
                </div>
                @if($report->subject)
                <div class="text-[10px] font-bold uppercase text-zinc-400 mb-1">Asunto: {{ $report->subject }}</div>
                @endif
                <div class="line-clamp-2 italic text-zinc-500">{{ $report->description }}</div>
                @if($report->teacher)
                <div class="text-[10px] text-zinc-400 mt-1">Asignado por: {{ $report->teacher->name }}</div>
                @endif
            </div>

            <div class="flex justify-end gap-1 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                <flux:dropdown>
                    <flux:button variant="ghost" size="xs" icon="plus-circle" title="Crear citatorio o servicio" />
                    <flux:menu>
                        <flux:menu.item icon="calendar-days" href="{{ route('citations.index', ['open_create' => 1, 'student_id' => $report->student_id, 'student_name' => $report->student->name]) }}" wire:navigate>Generar Citatorio</flux:menu.item>
                        <flux:menu.item icon="briefcase" href="{{ route('community-services.index', ['open_create' => 1, 'student_id' => $report->student_id, 'student_name' => $report->student->name]) }}" wire:navigate>Servicio Comunitario</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
                <flux:button variant="ghost" size="xs" icon="pencil" wire:click="editReport('{{ $report->id }}')" title="Editar reporte" />
                <flux:button variant="ghost" size="xs" icon="trash" class="text-red-500" wire:click="confirmDelete('{{ $report->id }}')" title="Eliminar reporte" />
            </div>
        </div>
        @empty
        <x-empty-state icon="document-text" heading="Sin reportes" description="No se encontraron reportes." class="bg-zinc-50 dark:bg-zinc-800/50 border border-dashed border-zinc-300" />
        @endforelse
        <div class="mt-4">
            {{ $reports->links() }}
        </div>
    </div>

    <!-- Desktop Table (Staff View) -->
    <div class="hidden sm:block p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="py-3 px-2 font-semibold">Fecha</th>
                    <th class="py-3 px-2 font-semibold">Alumno</th>
                    <th class="py-3 px-2 font-semibold">Infracción / Asunto</th>
                    <th class="py-3 px-2 font-semibold text-center">Estado</th>
                    <th class="py-3 px-2 text-right font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($reports as $report)
                <tr wire:key="rep-desk-{{ $report->id }}">
                    <td class="py-4 px-2">
                        <div class="font-medium">{{ $report->date->format('d/m/Y') }}</div>
                        <div class="text-xs text-zinc-500">{{ $report->date->format('H:i') }}</div>
                    </td>
                    <td class="py-4 px-2">
                        <div class="font-bold">{{ $report->student->name }}</div>
                    </td>
                    <td class="py-4 px-2">
                        <div class="font-medium text-blue-600 dark:text-blue-400 whitespace-normal">{{ $report->infraction->description }}</div>
                        @if($report->subject)
                        <div class="text-xs font-semibold uppercase mt-1 whitespace-normal">Asunto: {{ $report->subject }}</div>
                        @endif
                        <div class="text-xs text-zinc-500 whitespace-normal line-clamp-2 italic">{{ $report->description }}</div>
                        @if($report->teacher)
                        <div class="text-[10px] text-zinc-400">Asignado por: {{ $report->teacher->name }}</div>
                        @endif
                    </td>
                    <td class="py-4 px-2 text-center">
                        @if($report->status === 'SIGNED')
                        <div class="flex flex-col items-center">
                            <flux:badge color="green" size="sm" inset="left" icon="check-badge">Firmado</flux:badge>
                            @if($report->signed_at)
                            <span class="text-[10px] text-zinc-500 mt-1">{{ $report->signed_at->format('d/m/Y H:i') }}</span>
                            @endif
                        </div>
                        @else
                        <flux:badge color="amber" size="sm" inset="left" icon="clock">Pendiente</flux:badge>
                        @endif
                    </td>
                    <td class="py-4 px-2 text-right">
                        <div class="flex justify-end gap-1">
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" title="Crear citatorio o servicio" />
                                <flux:menu>
                                    <flux:menu.item icon="calendar-days" href="{{ route('citations.index', ['open_create' => 1, 'student_id' => $report->student_id, 'student_name' => $report->student->name]) }}" wire:navigate>Generar Citatorio</flux:menu.item>
                                    <flux:menu.item icon="briefcase" href="{{ route('community-services.index', ['open_create' => 1, 'student_id' => $report->student_id, 'student_name' => $report->student->name]) }}" wire:navigate>Servicio Comunitario</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                            <flux:button variant="ghost" size="sm" icon="pencil" wire:click="editReport('{{ $report->id }}')" title="Editar reporte" />
                            <flux:button variant="ghost" size="sm" icon="trash" class="text-red-500" wire:click="confirmDelete('{{ $report->id }}')" title="Eliminar reporte" />
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-12 text-center text-zinc-500 italic">No se encontraron reportes.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">
            {{ $reports->links() }}
        </div>
    </div>
    @else
    <!-- Parent View: Feed style (Modern Cards) -->
    <div class="space-y-6 max-w-3xl mx-auto sm:hidden">
        @forelse ($reports as $report)
        <div wire:key="rep-{{ $report->id }}" class="p-6 rounded-2xl border {{ $report->status === 'SIGNED' ? 'border-zinc-200 bg-zinc-50/50' : 'border-amber-200 bg-white shadow-lg' }} dark:border-zinc-700 dark:bg-zinc-900 relative transition-all hover:shadow-xl group">
            @if($report->status !== 'SIGNED' && $report->infraction->severity === 'GRAVE')
            <div class="absolute -top-3 -right-3">
                <flux:badge color="red" size="sm" class="animate-pulse shadow-md">Falta Grave</flux:badge>
            </div>
            @endif

            <div class="flex justify-between items-start mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-linear-to-br from-zinc-500 to-zinc-700 flex items-center justify-center text-white font-black text-xl shadow-inner uppercase">
                        {{ substr($report->student->name, 0, 1) }}
                    </div>
                    <div class="whitespace-normal">
                        <div class="flex items-center gap-2">
                            <flux:text size="xs" class="uppercase tracking-widest font-black text-zinc-400 shrink-0">Reporte de:</flux:text>
                            <span class="px-2 py-0.5 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-[10px] font-bold wrap-break-word">{{ $report->student->name }}</span>
                        </div>
                        <flux:heading level="3" size="lg" class="mt-0.5 whitespace-normal">{{ $report->infraction->description }}</flux:heading>
                    </div>
                </div>
                <div class="text-right">
                    <flux:text size="xs" class="text-zinc-500 block">{{ $report->date->format('d M, Y') }}</flux:text>
                    <flux:text size="xs" class="text-zinc-400">{{ $report->date->format('H:i') }} hrs</flux:text>
                </div>
            </div>

            @if($report->subject)
            <div class="mb-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 uppercase">
                    Asunto: {{ $report->subject }}
                </span>
            </div>
            @endif

            <div class="prose prose-zinc dark:prose-invert max-w-none text-zinc-700 dark:text-zinc-300 bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-xl border border-zinc-100 dark:border-zinc-800 italic leading-relaxed">
                "{{ $report->description }}"
            </div>

            @if($report->teacher)
            <flux:text size="sm" class="text-zinc-500 italic mt-4">Registrado por: Prof(a). {{ $report->teacher->name }}</flux:text>
            @endif

            <div class="mt-8 pt-6 border-t border-zinc-100 dark:border-zinc-800">
                @if($report->status !== 'SIGNED')
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2 text-amber-600 dark:text-amber-400">
                        <flux:icon icon="information-circle" variant="micro" />
                        <flux:text size="sm" class="font-medium text-inherit">Requiere su firma de enterado</flux:text>
                    </div>
                    <flux:button variant="primary" icon="finger-print" class="w-full sm:w-auto px-10 shadow-lg shadow-amber-500/30" wire:click="signReport('{{ $report->id }}')">
                        Firmar Reporte
                    </flux:button>
                </div>
                @else
                <div class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/10 rounded-xl border border-green-100 dark:border-green-800/30">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center text-white shadow-md">
                            <flux:icon icon="check" variant="micro" />
                        </div>
                        <div>
                            <flux:text size="sm" class="font-bold text-green-800 dark:text-green-200 uppercase tracking-tight">Reporte Firmado / Enterado</flux:text>
                            <flux:text size="xs" class="text-green-700/60 dark:text-green-400/60 font-medium">Registrado el {{ $report->signed_at->format('d/m/Y H:i') }}</flux:text>
                        </div>
                    </div>
                    <flux:icon icon="shield-check" class="text-green-200 dark:text-green-800" size="xl" />
                </div>
                @endif
            </div>
        </div>
        @empty
        <x-empty-state icon="check-circle" heading="Sin reportes registrados" description="No se han encontrado incidencias disciplinarias para sus hijos en este ciclo." class="py-20" />
        @endforelse
        <div class="mt-4">
            {{ $reports->links() }}
        </div>
    </div>

    <!-- Desktop Table (Parent View) -->
    <div class="hidden sm:block p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="py-3 px-2 font-semibold">Fecha</th>
                    <th class="py-3 px-2 font-semibold">Alumno</th>
                    <th class="py-3 px-2 font-semibold">Infracción / Asunto</th>
                    <th class="py-3 px-2 font-semibold text-center">Estado</th>
                    <th class="py-3 px-2 text-right font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($reports as $report)
                <tr wire:key="rep-par-desk-{{ $report->id }}">
                    <td class="py-4 px-2">
                        <div class="font-medium">{{ $report->date->format('d/m/Y') }}</div>
                        <div class="text-xs text-zinc-500">{{ $report->date->format('H:i') }}</div>
                    </td>
                    <td class="py-4 px-2">
                        <div class="font-bold">{{ $report->student->name }}</div>
                    </td>
                    <td class="py-4 px-2">
                        <div class="font-medium text-blue-600 dark:text-blue-400 whitespace-normal">{{ $report->infraction->description }}</div>
                        @if($report->subject)
                        <div class="text-xs font-semibold uppercase mt-1 whitespace-normal">Asunto: {{ $report->subject }}</div>
                        @endif
                        <div class="text-xs text-zinc-500 whitespace-normal line-clamp-2 italic">{{ $report->description }}</div>
                        @if($report->teacher)
                        <div class="text-[10px] text-zinc-400">Asignado por: {{ $report->teacher->name }}</div>
                        @endif
                    </td>
                    <td class="py-4 px-2 text-center">
                        @if($report->status === 'SIGNED')
                        <div class="flex flex-col items-center">
                            <flux:badge color="green" size="sm" inset="left" icon="check-badge">Firmado</flux:badge>
                            @if($report->signed_at)
                            <span class="text-[10px] text-zinc-500 mt-1">{{ $report->signed_at->format('d/m/Y H:i') }}</span>
                            @endif
                        </div>
                        @else
                        <flux:badge color="amber" size="sm" inset="left" icon="clock">Pendiente</flux:badge>
                        @endif
                    </td>
                    <td class="py-4 px-2 text-right">
                        @if($report->status !== 'SIGNED')
                        <flux:button variant="primary" size="sm" icon="finger-print" wire:click="signReport('{{ $report->id }}')">
                            Firmar
                        </flux:button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-12 text-center text-zinc-500 italic">No se han encontrado incidencias disciplinarias para sus hijos en este ciclo.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">
            {{ $reports->links() }}
        </div>
    </div>
    @endif

    @can('teacher-or-admin')
    <!-- Create/Edit Modal -->
    <flux:modal wire:model.self="showReportModal" class="md:w-160">
        <form wire:submit="save" class="space-y-6">
            <header>
                <flux:heading size="lg">{{ $editingReport ? 'Editar Reporte' : 'Registrar Reporte Disciplinario' }}</flux:heading>
                <flux:text>{{ $editingReport ? 'Actualice los detalles de la incidencia.' : 'Complete los detalles de la incidencia académica o conductual.' }}</flux:text>
            </header>

            <div class="space-y-4">
                <!-- Student Search -->
                <div class="relative">
                    <flux:input wire:model.live.debounce.300ms="studentSearch" label="Buscar Alumno (Nombre)" icon="user" placeholder="Escriba al menos 3 caracteres..." autofocus />
                    @if(count($studentResults) > 0)
                    <div class="absolute z-10 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg overflow-hidden">
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
                    <flux:error name="selectedStudentId" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input type="date" wire:model="reportDate" label="Fecha" />
                    <flux:input type="time" wire:model="reportTime" label="Hora" />
                </div>

                <flux:select label="Infracción (Reglamento)" wire:model="infractionId">
                    <option value="">Seleccione el tipo de falta...</option>
                    @foreach($infractions as $infraction)
                    <option value="{{ $infraction->id }}">{{ $infraction->description }}</option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="subject" label="Asunto / Materia (Opcional)" placeholder="Ej: Clase de Matemáticas, Receso..." />

                <flux:textarea wire:model="description" label="Descripción de los hechos" placeholder="Detalle lo sucedido de forma objetiva..." rows="4" />

                <flux:select label="Asignado por" wire:model="teacherId">
                    @foreach($staffMembers as $staff)
                        <option value="{{ $staff->id }}">{{ $staff->name }} ({{ $staff->role === 'ADMIN' ? 'Admin' : 'Docente' }})</option>
                    @endforeach
                </flux:select>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button wire:click="$set('showReportModal', false)">Cancelar</flux:button>
                <flux:button variant="primary" type="submit">Guardar Reporte</flux:button>
            </div>
        </form>
    </flux:modal>
    @endcan

    @can('teacher-or-admin')
    <!-- Delete Confirmation Modal -->
    <flux:modal wire:model.self="showDeleteModal" class="min-w-88">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">¿Eliminar reporte?</flux:heading>
                <flux:text class="mt-2">
                    Esta acción no se puede deshacer. El registro del reporte será eliminado permanentemente.
                </flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deleteReport">Eliminar Reporte</flux:button>
            </div>
        </div>
    </flux:modal>
    @endcan

    <!-- Notification logic (Toast) would go here or in layout -->
</div>