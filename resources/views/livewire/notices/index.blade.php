<?php

use App\Models\Notice;
use App\Models\NoticeSignature;
use App\Models\Student;
use App\Models\Cycle;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $typeFilter = '';
    public bool $onlyActiveCycle = true;
    public bool $onlyPending = false;

    // Create Modal
    public bool $showCreateModal = false;
    public bool $showDeleteModal = false;
    public ?string $editingNoticeId = null;
    public ?string $deletingNoticeId = null;
    public string $title = '';
    public string $content = '';
    public string $type = 'GENERAL';
    public string $targetAudience = 'PARENTS';
    public array $targetGrades = [];
    public array $targetClassGroups = [];
    public bool $requiresAuthorization = false;
    public string $eventDate = '';
    public string $endDate = '';
    public string $eventTime = '';
    public ?string $targetStudentId = null;
    public string $studentSearch = '';
    
    // Signatures Modal
    public bool $showSignaturesModal = false;
    public ?string $viewingSignaturesNoticeId = null;
    public array $signatureStats = [];
    public $signedList = [];
    public $pendingList = [];
    public array $groupStats = [];
    public string $activeTab = 'signed';
    public ?string $groupFilter = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
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

    public function updatedType($value): void
    {
        if ($value === 'TRABAJO_EN_CASA') {
            $this->title = 'Trabajo en Casa';
            $this->requiresAuthorization = false;
            $this->targetAudience = 'STUDENT';
            $this->targetStudentId = null;
            $this->studentSearch = '';
            if (empty($this->content)) {
                $this->content = "Por medio de la presente le notificamos que su hijo(a) realizará estudio y trabajo en casa.\n\nMotivo: ";
            }
        }
    }

    public function selectStudent(string $id): void
    {
        $this->targetStudentId = $id;
        $this->studentSearch = Student::find($id)->name;
    }

    public function mount(): void
    {
        $this->eventDate = now()->format('Y-m-d');
        $this->eventTime = now()->format('10:00');
    }

    public function openCreateModal(): void
    {
        $this->authorize('teacher-or-admin');
        $this->resetValidation();
        $this->reset(['editingNoticeId', 'title', 'content', 'type', 'targetAudience', 'targetGrades', 'targetClassGroups', 'targetStudentId', 'studentSearch', 'requiresAuthorization', 'endDate']);
        $this->type = 'GENERAL';
        $this->targetAudience = 'PARENTS';
        $this->eventDate = now()->format('Y-m-d');
        $this->eventTime = now()->format('10:00');
        $this->showCreateModal = true;
    }

    public function saveNotice(): void
    {
        $this->authorize('teacher-or-admin');
        
        $this->validate([
            'title' => 'required|string|max:100',
            'content' => 'required|string',
            'type' => 'required',
            'targetAudience' => 'required',
            'targetStudentId' => 'required_if:targetAudience,STUDENT|required_if:type,TRABAJO_EN_CASA',
            'eventDate' => 'required_if:type,TRABAJO_EN_CASA|date|nullable',
            'endDate' => 'required_if:type,TRABAJO_EN_CASA|date|after_or_equal:eventDate|nullable',
        ], [
            'title.required' => 'El título del aviso es obligatorio.',
            'content.required' => 'El contenido o mensaje del aviso es obligatorio.',
            'targetStudentId.required_if' => 'Debe seleccionar un alumno.',
            'eventDate.required_if' => 'La fecha de inicio es obligatoria para trabajo en casa.',
            'endDate.required_if' => 'La fecha de término es obligatoria para trabajo en casa.',
            'endDate.after_or_equal' => 'La fecha de término debe ser igual o posterior a la de inicio.',
        ]);

        if ($this->editingNoticeId) {
            $notice = Notice::findOrFail($this->editingNoticeId);
            $notice->update([
                'title' => $this->title,
                'content' => $this->content,
                'type' => $this->type,
                'target_audience' => $this->targetAudience,
                'target_grades' => count($this->targetGrades) > 0 ? $this->targetGrades : null,
                'target_class_groups' => count($this->targetClassGroups) > 0 ? $this->targetClassGroups : null,
                'target_student_id' => $this->targetStudentId ?: null,
                'requires_authorization' => $this->requiresAuthorization,
                'event_date' => $this->eventDate ?: null,
                'end_date' => $this->endDate ?: null,
                'event_time' => $this->eventTime ?: null,
            ]);
            $message = 'Aviso actualizado exitosamente.';
        } else {
            $activeCycle = Cycle::where('is_active', true)->first();
            if (!$activeCycle) {
                $this->dispatch('notify', ['message' => 'No hay un ciclo escolar activo.', 'variant' => 'danger']);
                return;
            }

            $notice = Notice::create([
                'cycle_id' => $activeCycle->id,
                'author_id' => auth()->id(),
                'title' => $this->title,
                'content' => $this->content,
                'type' => $this->type,
                'target_audience' => $this->targetAudience,
                'target_grades' => count($this->targetGrades) > 0 ? $this->targetGrades : null,
                'target_class_groups' => count($this->targetClassGroups) > 0 ? $this->targetClassGroups : null,
                'target_student_id' => $this->targetStudentId ?: null,
                'requires_authorization' => $this->requiresAuthorization,
                'event_date' => $this->eventDate ?: null,
                'end_date' => $this->endDate ?: null,
                'event_time' => $this->eventTime ?: null,
                'date' => now(),
            ]);

            // Notify parents via FCM asíncronamente (Hallazgo #3)
            $students = $notice->getExpectedRecipientsQuery()->with('parents')->get();
            $parentIds = $students->flatMap(fn($s) => $s->parents)->pluck('id')->unique()->toArray();
            
            if (!empty($parentIds)) {
                \App\Jobs\SendBulkFcmNotifications::dispatch(
                    $parentIds,
                    'Nuevo Aviso Escolar: ' . $this->title,
                    Str::limit($this->content, 100),
                    [],
                    route('notices.index')
                );
            }

            $message = 'Aviso publicado exitosamente.';
        }

        $this->showCreateModal = false;
        $this->editingNoticeId = null;
        $this->reset(['title', 'content', 'requiresAuthorization', 'type', 'targetAudience', 'targetGrades', 'targetClassGroups', 'targetStudentId', 'studentSearch', 'endDate']);
        $this->dispatch('notify', ['message' => $message]);
    }

    public function editNotice(string $id): void
    {
        $this->authorize('teacher-or-admin');
        $this->resetValidation();
        $notice = Notice::findOrFail($id);
        
        $this->editingNoticeId = $notice->id;
        $this->title = $notice->title;
        $this->content = $notice->content;
        $this->type = $notice->type;
        $this->targetAudience = $notice->target_audience;
        $this->targetGrades = $notice->target_grades ?? [];
        $this->targetClassGroups = $notice->target_class_groups ?? [];
        $this->targetStudentId = $notice->target_student_id;
        $this->studentSearch = $notice->targetStudent?->name ?? '';
        $this->requiresAuthorization = (bool) $notice->requires_authorization;
        $this->eventDate = $notice->event_date ? $notice->event_date->format('Y-m-d') : '';
        $this->endDate = $notice->end_date ? $notice->end_date->format('Y-m-d') : '';
        $this->eventTime = $notice->event_time ?? '';
        
        $this->showCreateModal = true;
    }

    public function confirmDelete(string $id): void
    {
        $this->authorize('teacher-or-admin');
        $this->deletingNoticeId = $id;
        $this->showDeleteModal = true;
    }

    public function deleteNotice(): void
    {
        $this->authorize('teacher-or-admin');
        if ($this->deletingNoticeId) {
            Notice::findOrFail($this->deletingNoticeId)->delete();
            $this->showDeleteModal = false;
            $this->deletingNoticeId = null;
            $this->dispatch('notify', ['message' => 'Aviso eliminado correctamente.']);
        }
    }

    public function viewSignatures(string $id): void
    {
        $this->authorize('teacher-or-admin');
        $notice = Notice::with(['signatures.parent', 'signatures.student'])->findOrFail($id);
        $this->viewingSignaturesNoticeId = $notice->id;
        $this->groupFilter = null;
        
        $stats = $notice->getSignatureStats();
        $this->signatureStats = $stats;
        
        // Get signed list
        $this->signedList = $notice->signatures->map(fn($s) => [
            'student_name' => $s->student->name,
            'student_grade_group' => $s->student->grade . $s->student->group_name,
            'parent_name' => $s->parent->name,
            'date' => $s->signed_at->format('d/m/Y H:i'),
            'authorized' => $notice->requires_authorization ? $s->authorized : null,
        ])->toArray();
        
        // Get pending list
        $signedStudentIds = $notice->signatures->pluck('student_id')->toArray();
        $this->pendingList = $notice->getExpectedRecipientsQuery()
            ->whereNotIn('id', $signedStudentIds)
            ->get()->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'grade_group' => $s->grade . $s->group_name,
            ])
            ->toArray();

        // Calculate group stats
        $groupStatsMap = [];
        
        // Combine signed and pending to get totals per group
        foreach ($this->signedList as $item) {
            $group = $item['student_grade_group'];
            if (!isset($groupStatsMap[$group])) {
                $groupStatsMap[$group] = ['signed' => 0, 'pending' => 0, 'total' => 0];
            }
            $groupStatsMap[$group]['signed']++;
            $groupStatsMap[$group]['total']++;
        }
        
        foreach ($this->pendingList as $item) {
            $group = $item['grade_group'];
            if (!isset($groupStatsMap[$group])) {
                $groupStatsMap[$group] = ['signed' => 0, 'pending' => 0, 'total' => 0];
            }
            $groupStatsMap[$group]['pending']++;
            $groupStatsMap[$group]['total']++;
        }
        
        ksort($groupStatsMap);
        $this->groupStats = $groupStatsMap;
            
        $this->activeTab = 'signed';
        $this->showSignaturesModal = true;
    }

    public function signNotice(string $noticeId, string $studentId, bool $isAuthorized = true): void
    {
        $this->authorize('parent-only');
        // Prevención de firmas duplicadas: comprobar si ya existe una firma para este aviso y alumno
        $existing = NoticeSignature::where('notice_id', $noticeId)
            ->where('student_id', $studentId)
            ->first();

        if ($existing && $existing->signed_at) {
            // Si ya fue firmada por otro padre, mostrar toast informativo y no duplicar
            $this->dispatch('notify', ['message' => 'Este aviso ya ha sido firmado.', 'variant' => 'info']);
            $this->dispatch('navigation-refresh');
            return;
        }

        try {
            $signature = NoticeSignature::updateOrCreate(
                ['notice_id' => $noticeId, 'student_id' => $studentId, 'parent_id' => auth()->id()],
                ['signed_at' => now(), 'authorized' => $isAuthorized]
            );

            // Si es un permiso de Trabajo en Casa y fue autorizado (firmado), generar asistencias
            $notice = Notice::find($noticeId);
            if ($notice && $notice->type === 'TRABAJO_EN_CASA' && $isAuthorized) {
                $start = $notice->event_date;
                $end = $notice->end_date ?: $start;
                
                $current = $start->copy();
                while ($current->lte($end)) {
                    // Solo marcar si es día de semana (opcional, pero recomendado)
                    if (!$current->isWeekend()) {
                        \App\Models\Attendance::updateOrCreate(
                            ['student_id' => $studentId, 'date' => $current->toDateString()],
                            ['status' => 'TRABAJO_EN_CASA']
                        );
                    }
                    $current->addDay();
                }
            }

            $this->dispatch('navigation-refresh');
            $this->dispatch('notify', ['message' => 'Firma registrada correctamente.']);
        } catch (\Illuminate\Database\QueryException $e) {
            // Si la excepción es por clave duplicada, informar al usuario que ya fue firmado
            $sqlState = $e->getCode();
            if (in_array($sqlState, ['23000', '23505'])) { // MySQL/MariaDB and Postgres unique violation codes
                $this->dispatch('notify', ['message' => 'Este aviso ya ha sido firmado.', 'variant' => 'info']);
                $this->dispatch('navigation-refresh');
                return;
            }

            throw $e; // Re-lanzar otras excepciones
        }
    }

    public function with(): array
    {
        $user = auth()->user();
        $isStaffView = $user->isViewStaff();
        $activeCycle = Cycle::where('is_active', true)->first();

        if ($isStaffView) {
            $notices = Notice::with(['author'])
                ->withCount('signatures')
                ->when($this->onlyActiveCycle && $activeCycle, fn($q) => $q->where('cycle_id', $activeCycle->id))
                ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
                ->when($this->typeFilter, fn($q) => $q->where('type', $this->typeFilter))
                ->orderBy('date', 'desc')
                ->paginate(10);
                
            $notices->each(function($notice) {
                $notice->cached_stats = $notice->getSignatureStats();
            });
                
            return [
                'notices' => $notices,
                'isStaff' => true,
                'availableGroups' => $activeCycle ? \App\Models\ClassGroup::where('cycle_id', $activeCycle->id)->withCount('students')->get() : collect(),
                'studentResults' => (strlen($this->studentSearch) >= 3 && !$this->targetStudentId) 
                    ? \App\Models\Student::where('name', 'like', "%{$this->studentSearch}%")->limit(5)->get() 
                    : [],
            ];
        } else {
            // Parent view (Normal parent or Staff in Parent mode)
            $students = $user->students()->with(['cycleAssociations'])->get();
            $studentIds = $students->pluck('id')->toArray();
            $studentGrades = $students->pluck('grade')->unique()->toArray();
            $studentGroupIds = $students->map(function($student) use ($activeCycle) {
                return $activeCycle ? $student->cycleAssociations->firstWhere('cycle_id', $activeCycle->id)?->class_group_id : null;
            })->filter()->unique()->toArray();
            
            $notices = Notice::with(['author', 'signatures' => fn($q) => $q->whereIn('student_id', $studentIds)])
                ->when($this->onlyActiveCycle && $activeCycle, fn($q) => $q->where('cycle_id', $activeCycle->id))
                ->whereIn('target_audience', ['PARENTS', 'ALL', 'STUDENT'])
                ->where(function($query) use ($studentGrades, $studentGroupIds, $studentIds) {
                    $query->where('target_audience', 'ALL')
                        ->orWhere(function($q) use ($studentGrades, $studentGroupIds) {
                            $q->where('target_audience', 'PARENTS')
                                ->where(function($sq) use ($studentGrades, $studentGroupIds) {
                                    $sq->where(function($ssq) {
                                        $ssq->whereNull('target_grades')
                                            ->whereNull('target_class_groups');
                                    })
                                    ->orWhere(function($ssq) use ($studentGrades) {
                                        foreach ($studentGrades as $grade) {
                                            $ssq->orWhereJsonContains('target_grades', $grade);
                                        }
                                    })
                                    ->orWhere(function($ssq) use ($studentGroupIds) {
                                        foreach ($studentGroupIds as $groupId) {
                                            $ssq->orWhereJsonContains('target_class_groups', $groupId);
                                        }
                                    });
                                });
                        })
                        ->orWhere(function($q) use ($studentIds) {
                            $q->where('target_audience', 'STUDENT')
                                ->whereIn('target_student_id', $studentIds);
                        });
                })
                ->when($this->onlyPending, function($q) use ($studentIds) {
                    $q->whereDoesntHave('signatures', fn($sq) => $sq->whereIn('student_id', $studentIds));
                })
                ->orderBy('date', 'desc')
                ->paginate(10);

            return [
                'notices' => $notices,
                'isStaff' => false,
                'myStudents' => $students,
                'availableGroups' => collect(),
            ];
        }
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg" level="1">Avisos y Comunicados</flux:heading>
            <flux:text class="text-zinc-500">Mural digital de avisos escolares.</flux:text>
        </div>
        @if($isStaff)
            <flux:button variant="primary" icon="plus" wire:click="openCreateModal">Nuevo Aviso</flux:button>
        @endif
    </div>

    @if($isStaff)
        <!-- Admin/Teacher View: Dashboard with stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <flux:select wire:model.live="typeFilter" placeholder="Filtrar por tipo...">
                <option value="">Todos los tipos</option>
                <option value="GENERAL">General</option>
                <option value="URGENT">Urgente</option>
                <option value="EVENT">Evento</option>
                <option value="TRABAJO_EN_CASA">Trabajo en Casa</option>
            </flux:select>
            <div class="flex items-center gap-2 px-2">
                <flux:switch wire:model.live="onlyActiveCycle" label="Solo ciclo activo" />
            </div>
        </div>

        <div class="space-y-4">
            @forelse($notices as $notice)
                <div wire:key="{{ $notice->id }}" class="p-5 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm relative overflow-hidden group">
                    @if($notice->type === 'URGENT')
                        <div class="absolute top-0 left-0 w-1.5 h-full bg-red-500"></div>
                    @endif
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <flux:badge size="sm" color="{{ $notice->type === 'URGENT' ? 'red' : ($notice->type === 'EVENT' ? 'blue' : 'neutral') }}" inset="left">
                                    {{ $notice->type === 'GENERAL' ? 'General' : ($notice->type === 'URGENT' ? 'Urgente' : 'Evento') }}
                                </flux:badge>
                                <flux:text size="sm" class="text-zinc-500">{{ $notice->date->format('d M, Y H:i') }}</flux:text>
                                <flux:text size="sm" class="text-zinc-400">· Por {{ $notice->author->name }}</flux:text>
                            </div>
                            <flux:heading level="3" size="md">{{ $notice->title }}</flux:heading>
                            <p class="mt-2 text-zinc-600 dark:text-zinc-400 line-clamp-2 text-sm leading-relaxed">{{ $notice->content }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <div class="flex gap-1 mb-1">
                                <flux:button variant="ghost" size="sm" icon="pencil" title="Editar aviso" wire:click="editNotice('{{ $notice->id }}')" />
                                <flux:button variant="ghost" size="sm" icon="trash" color="red" title="Eliminar aviso" wire:click="confirmDelete('{{ $notice->id }}')" />
                            </div>
                            
                            @php
                                $stats = $notice->cached_stats ?? $notice->getSignatureStats();
                            @endphp
                            
                            <flux:button 
                                variant="outline" 
                                size="sm" 
                                icon="finger-print" 
                                color="{{ $stats['percentage'] === 100 ? 'green' : 'zinc' }}"
                                title="Ver firmas y progreso"
                                wire:click="viewSignatures('{{ $notice->id }}')"
                                class="cursor-pointer"
                            >
                                {{ $stats['signed'] }} de {{ $stats['expected'] }}
                            </flux:button>

                            @if($notice->requires_authorization)
                                <flux:badge color="purple" size="sm">Requiere Autorización</flux:badge>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center text-zinc-500 italic border border-dashed rounded-xl border-zinc-300 dark:border-zinc-700">
                    No hay avisos publicados.
                </div>
            @endforelse
            <div class="mt-4">
                {{ $notices->links() }}
            </div>
        </div>
    @else
        <!-- Parent View Filters -->
        <div class="flex items-center gap-4 mb-6">
            <flux:checkbox wire:model.live="onlyPending" label="Solo mostrar avisos pendientes" />
        </div>

        <!-- Parent View: Feed style -->
        <div class="space-y-8 max-w-3xl mx-auto sm:hidden">
            @forelse($notices as $notice)
                @foreach($myStudents as $student)
                    @if($notice->isTargeting($student))
                        @php 
                            $signature = $notice->signatures->where('student_id', $student->id)->first();
                        @endphp
                        <div wire:key="n-{{ $notice->id }}-s-{{ $student->id }}" class="p-6 rounded-2xl border {{ $signature ? 'border-zinc-200 bg-zinc-50/50' : 'border-blue-200 bg-white' }} dark:border-zinc-700 dark:bg-zinc-900 shadow-lg relative transition-all hover:shadow-xl">
                        @if(!$signature && $notice->type === 'URGENT')
                            <div class="absolute -top-3 -right-3">
                                <flux:badge color="red" size="sm" class="animate-pulse shadow-md">Urgente</flux:badge>
                            </div>
                        @endif

                        <div class="flex justify-between items-start mb-6">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-linear-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-black text-xl shadow-inner">
                                    {{ substr($student->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <flux:text size="xs" class="uppercase tracking-widest font-black text-zinc-400">Aviso para:</flux:text>
                                        <span class="px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-[10px] font-bold">{{ $student->name }} - {{ $student->grade }}{{ $student->group_name }}</span>
                                    </div>
                                    <flux:heading level="3" size="lg" class="mt-0.5">{{ $notice->title }}</flux:heading>
                                </div>
                            </div>
                        </div>

                        <div class="prose prose-zinc dark:prose-invert max-w-none text-zinc-700 dark:text-zinc-300 bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-xl border border-zinc-100 dark:border-zinc-800">
                            {!! nl2br(e($notice->content)) !!}
                        </div>

                        @if(($notice->type === 'EVENT' || $notice->type === 'TRABAJO_EN_CASA') && $notice->event_date)
                            <div class="mt-6 p-4 {{ $notice->type === 'TRABAJO_EN_CASA' ? 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-100' : 'bg-blue-50 dark:bg-blue-900/20 border-blue-100' }} rounded-xl border dark:border-blue-800/30 flex items-center gap-4">
                                <div class="p-3 rounded-lg {{ $notice->type === 'TRABAJO_EN_CASA' ? 'bg-indigo-600' : 'bg-blue-500' }} text-white shadow-lg">
                                    <flux:icon icon="{{ $notice->type === 'TRABAJO_EN_CASA' ? 'home' : 'calendar-days' }}" />
                                </div>
                                <div>
                                    <flux:text size="sm" class="font-bold {{ $notice->type === 'TRABAJO_EN_CASA' ? 'text-indigo-800 dark:text-indigo-200' : 'text-blue-800 dark:text-blue-200' }}">
                                        {{ $notice->type === 'TRABAJO_EN_CASA' ? 'Periodo de Trabajo en Casa' : 'Detalles del Evento' }}
                                    </flux:text>
                                    <flux:text size="lg" class="{{ $notice->type === 'TRABAJO_EN_CASA' ? 'text-indigo-700 dark:text-indigo-300' : 'text-blue-700 dark:text-blue-300' }} font-medium">
                                        @if($notice->type === 'TRABAJO_EN_CASA' && $notice->end_date)
                                            Del {{ $notice->event_date->format('d/m/Y') }} Al {{ $notice->end_date->format('d/m/Y') }}
                                        @else
                                            {{ $notice->event_date->format('l, d de F Y') }} {{ $notice->event_time ? 'a las '.$notice->event_time : '' }}
                                        @endif
                                    </flux:text>
                                </div>
                            </div>
                        @endif

                        <div class="mt-8 pt-6 border-t border-zinc-100 dark:border-zinc-800">
                            @if(!$signature)
                                @if($notice->requires_authorization)
                                    <div class="space-y-4">
                                        <div class="flex items-start gap-3 p-3 bg-purple-50 dark:bg-purple-900/10 rounded-lg border border-purple-100 dark:border-purple-800/30">
                                            <flux:icon icon="information-circle" class="text-purple-600 shrink-0 mt-0.5" />
                                            <flux:text size="sm" class="text-purple-900 dark:text-purple-200 italic">
                                                {{ $notice->type === 'TRABAJO_EN_CASA' ? 'Este documento digital sustituye al formato impreso. Al firmar, usted autoriza a su hijo(a) a realizar estudio en casa en el periodo indicado.' : 'Este comunicado requiere una respuesta de su parte para autorizar o denegar la participación del alumno en la actividad descrita.' }}
                                            </flux:text>
                                        </div>
                                        <div class="flex flex-col sm:flex-row gap-3">
                                            <flux:button variant="primary" icon="{{ $notice->type === 'TRABAJO_EN_CASA' ? 'finger-print' : 'check' }}" class="flex-1 py-3" wire:click="signNotice('{{ $notice->id }}', '{{ $student->id }}', true)" wire:confirm="¿Está seguro de realizar esta acción?">
                                                {{ $notice->type === 'TRABAJO_EN_CASA' ? 'Firmar de Conformidad' : 'Autorizar Participación' }}
                                            </flux:button>
                                            @if($notice->type !== 'TRABAJO_EN_CASA')
                                                <flux:button variant="filled" color="red" icon="x-mark" class="flex-1 py-3" wire:click="signNotice('{{ $notice->id }}', '{{ $student->id }}', false)" wire:confirm="¿Está seguro de NO autorizar esta actividad?">
                                                    No Autorizar
                                                </flux:button>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                                        <flux:text size="sm" class="text-zinc-500 font-medium">Por favor valide la recepción de este comunicado:</flux:text>
                                        <flux:button variant="primary" icon="finger-print" class="w-full sm:w-auto px-10 shadow-lg shadow-blue-500/30" wire:click="signNotice('{{ $notice->id }}', '{{ $student->id }}')" wire:confirm="¿Desea registrar su firma de enterado?">
                                            Confirmar de Enterado
                                        </flux:button>
                                    </div>
                                @endif
                            @else
                                <div class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/10 rounded-xl border border-green-100 dark:border-green-800/30">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center text-white shadow-md">
                                            <flux:icon icon="check" variant="micro" />
                                        </div>
                                        <div>
                                            @if($notice->requires_authorization)
                                                <flux:text size="sm" class="font-bold text-green-800 dark:text-green-200">
                                                    Respuesta: {{ $signature->authorized ? 'AUTORIZADO' : 'NO AUTORIZADO' }}
                                                </flux:text>
                                            @else
                                                <flux:text size="sm" class="font-bold text-green-800 dark:text-green-200">ESTADO: FIRMADO</flux:text>
                                            @endif
                                            <flux:text size="xs" class="text-green-700/60 dark:text-green-400/60">Registrado el {{ $signature->signed_at->format('d/m/Y H:i') }}</flux:text>
                                        </div>
                                    </div>
                                    <flux:icon icon="shield-check" class="text-green-200 dark:text-green-800" size="xl" />
                                </div>
                            @endif
                        </div>
                    </div>
                    @endif
                @endforeach
            @empty
                <div class="py-20 text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-300 dark:text-zinc-600 mb-4">
                        <flux:icon icon="megaphone" size="xl" />
                    </div>
                    <flux:heading size="md" class="text-zinc-400">Sin avisos pendientes</flux:heading>
                    <flux:text class="text-zinc-500">Por el momento no hay comunicados nuevos para sus hijos.</flux:text>
                </div>
            @endforelse
            <div class="mt-6">
                {{ $notices->links() }}
            </div>
        </div>

        <!-- Desktop Table (Parent View) -->
        <div class="hidden sm:block p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700 text-zinc-500">
                        <th class="py-3 px-2 font-semibold uppercase tracking-wider text-xs">Fecha</th>
                        <th class="py-3 px-2 font-semibold uppercase tracking-wider text-xs">Tipo / Título</th>
                        <th class="py-3 px-2 font-semibold uppercase tracking-wider text-xs">Dirigido a</th>
                        <th class="py-3 px-2 font-semibold uppercase tracking-wider text-xs text-center">Estado</th>
                        <th class="py-3 px-2 font-semibold uppercase tracking-wider text-xs text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @php $noticeCount = 0; @endphp
                    @foreach($notices as $notice)
                        @foreach($myStudents as $student)
                            @if($notice->isTargeting($student))
                                @php 
                                    $noticeCount++;
                                    $signature = $notice->signatures->where('student_id', $student->id)->first();
                                @endphp
                                <tr wire:key="not-par-desk-{{ $notice->id }}-{{ $student->id }}">
                                    <td class="py-4 px-2">
                                        <div class="font-medium">{{ $notice->date->format('d/m/Y') }}</div>
                                        <div class="text-[10px] text-zinc-500">{{ $notice->date->format('H:i') }} hrs</div>
                                    </td>
                                    <td class="py-4 px-2">
                                        <div class="flex items-center gap-2 mb-1">
                                            <flux:badge size="xs" color="{{ $notice->type === 'URGENT' ? 'red' : ($notice->type === 'EVENT' ? 'blue' : ($notice->type === 'TRABAJO_EN_CASA' ? 'indigo' : 'neutral')) }}" inset="left">
                                                {{ $notice->type }}
                                            </flux:badge>
                                        </div>
                                        <div class="font-bold text-zinc-800 dark:text-zinc-200">{{ $notice->title }}</div>
                                        <div class="text-xs text-zinc-500 truncate max-w-xs italic">{{ Str::limit($notice->content, 60) }}</div>
                                    </td>
                                    <td class="py-4 px-2">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-blue-500 flex items-center justify-center text-white text-[10px] font-bold">
                                                {{ substr($student->name, 0, 1) }}
                                            </div>
                                            <span class="text-sm font-medium">{{ $student->name }} - {{ $student->grade }}{{ $student->group_name }}</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-2 text-center text-xs">
                                        @if($signature)
                                            <div class="flex flex-col items-center gap-1">
                                                @if($notice->requires_authorization)
                                                    <flux:badge size="sm" color="{{ $signature->authorized ? 'green' : 'red' }}">
                                                        {{ $signature->authorized ? 'Autorizado' : 'No Autorizado' }}
                                                    </flux:badge>
                                                @else
                                                    <flux:badge size="sm" color="green" icon="check-badge">Firmado  </flux:badge>
                                                @endif
                                                <span class="text-[9px] text-zinc-400">{{ $signature->signed_at->format('d/m/Y H:i') }}</span>
                                            </div>
                                        @else
                                            <flux:badge size="sm" color="amber" icon="clock">Pendiente</flux:badge>
                                        @endif
                                    </td>
                                    <td class="py-4 px-2 text-right">
                                        @if(!$signature)
                                            <div class="flex flex-col sm:flex-row justify-end gap-2">
                                                @if($notice->requires_authorization)
                                                    <flux:button variant="primary" size="sm" icon="check" wire:click="signNotice('{{ $notice->id }}', '{{ $student->id }}', true)" wire:confirm="¿Está seguro de autorizar esta actividad?">
                                                        Autorizar
                                                    </flux:button>
                                                    <flux:button variant="filled" color="red" size="sm" icon="x-mark" wire:click="signNotice('{{ $notice->id }}', '{{ $student->id }}', false)" wire:confirm="¿Está seguro de NO autorizar esta actividad?">
                                                        No Autorizar
                                                    </flux:button>
                                                @else
                                                    <flux:button variant="primary" size="sm" icon="finger-print" wire:click="signNotice('{{ $notice->id }}', '{{ $student->id }}', true)" wire:confirm="¿Desea registrar su firma de enterado?">
                                                        Firmar
                                                    </flux:button>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    @endforeach

                    @if($noticeCount === 0)
                        <tr>
                            <td colspan="5" class="py-12 text-center text-zinc-500 italic">No se han encontrado avisos para sus hijos.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
            <div class="mt-6">
                {{ $notices->links() }}
            </div>
        </div>
    @endif

    @if($isStaff)
        <!-- Create Modal -->
        <flux:modal wire:model.self="showCreateModal" class="md:w-160">
            <form wire:submit="saveNotice" class="space-y-6">
                <header>
                    <flux:heading size="md">{{ $editingNoticeId ? 'Editar Aviso Escolar' : 'Nuevo Aviso Escolar' }}</flux:heading>
                    <flux:text>{{ $editingNoticeId ? 'Modifique los detalles del comunicado.' : 'Cree un comunicado para la comunidad escolar.' }}</flux:text>
                </header>

                <div class="space-y-4">
                    <flux:input wire:model="title" label="Título del Aviso" placeholder="Ej: Suspensión por consejo técnico, Festival de primavera..." autofocus />
                    
                    <div class="grid grid-cols-2 gap-4">
                        <flux:select wire:model.live="type" label="Tipo de Aviso">
                            <option value="GENERAL">General</option>
                            <option value="URGENT">Urgente</option>
                            <option value="EVENT">Evento</option>
                            <option value="TRABAJO_EN_CASA">Trabajo en Casa</option>
                        </flux:select>
                        <flux:select wire:model.live="targetAudience" label="Dirigido a" :disabled="$type === 'TRABAJO_EN_CASA'">
                            <option value="ALL">Todo el plantel</option>
                            <option value="PARENTS">Solo Padres</option>
                            <option value="STUDENT">Alumno Específico</option>
                        </flux:select>
                    </div>

                    @if($targetAudience === 'STUDENT' || $type === 'TRABAJO_EN_CASA')
                        <div class="relative">
                            <flux:input 
                                wire:model.live.debounce.300ms="studentSearch" 
                                label="Buscar Alumno (Nombre)" 
                                icon="user" 
                                placeholder="Escriba al menos 3 caracteres..." 
                                x-on:focus="$wire.targetStudentId = null"
                            />
                            @if(count($studentResults) > 0)
                                <div class="absolute z-10 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg overflow-hidden">
                                    @foreach($studentResults as $student)
                                        <button type="button" wire:click="selectStudent('{{ $student->id }}')" class="w-full text-left px-4 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-700 flex flex-col">
                                            <span class="font-bold text-sm text-zinc-800 dark:text-zinc-200">{{ $student->name }}</span>
                                            <flux:text size="xs">{{ $student->grade }}{{ $student->group_name }}</flux:text>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                            @if($targetStudentId)
                                <div class="mt-2 flex items-center gap-2 text-green-600 dark:text-green-400 text-sm font-medium animate-in fade-in duration-300">
                                    <flux:icon icon="check-circle" variant="micro" />
                                    Alumno seleccionado correctamente.
                                </div>
                            @endif

                            <flux:error name="targetStudentId" />
                        </div>
                    @endif

                    @if($targetAudience === 'PARENTS')
                        <div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800/30 space-y-4">
                            <flux:text size="sm" class="font-semibold text-blue-900 dark:text-blue-200">Filtros de Audiencia (Opcional)</flux:text>
                            
                            <div class="space-y-3">
                                <div>
                                    <flux:text size="sm" class="font-medium mb-2">Por Grado</flux:text>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach(['1º', '2º', '3º'] as $grade)
                                            <label class="flex items-center gap-2 px-3 py-1.5 rounded-lg border cursor-pointer transition-colors {{ in_array($grade, $targetGrades) ? 'bg-blue-100 border-blue-300 dark:bg-blue-900/40 dark:border-blue-700' : 'bg-white border-zinc-200 dark:bg-zinc-800 dark:border-zinc-700' }}">
                                                <input type="checkbox" wire:model="targetGrades" value="{{ $grade }}" class="rounded">
                                                <span class="text-sm font-medium">{{ $grade }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div>
                                    <flux:text size="sm" class="font-medium mb-2">Por Grupo Específico</flux:text>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($availableGroups as $group)
                                            <label class="flex items-center gap-2 px-3 py-1.5 rounded-lg border cursor-pointer transition-colors {{ in_array($group->id, $targetClassGroups) ? 'bg-blue-100 border-blue-300 dark:bg-blue-900/40 dark:border-blue-700' : 'bg-white border-zinc-200 dark:bg-zinc-800 dark:border-zinc-700' }}">
                                                <input type="checkbox" wire:model="targetClassGroups" value="{{ $group->id }}" class="rounded">
                                                <span class="text-sm font-medium">{{ $group->grade }} {{ $group->section }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <flux:text size="xs" class="text-blue-700 dark:text-blue-300 italic">
                                    Si no selecciona ningún filtro, el aviso se enviará a todos los padres.
                                </flux:text>
                            </div>
                        </div>
                    @endif

                    @if($type === 'EVENT' || $type === 'TRABAJO_EN_CASA')
                        <div class="grid grid-cols-2 gap-4 animate-in fade-in slide-in-from-top-2 duration-300">
                            <flux:input type="date" wire:model="eventDate" label="{{ $type === 'TRABAJO_EN_CASA' ? 'Fecha de Inicio' : 'Fecha del Evento' }}" x-on:click="$el.showPicker()" />
                            @if($type === 'TRABAJO_EN_CASA')
                                <flux:input type="date" wire:model="endDate" label="Fecha de Término" x-on:click="$el.showPicker()" />
                            @else
                                <flux:input type="time" wire:model="eventTime" label="Hora" />
                            @endif
                        </div>
                    @endif

                    <flux:textarea wire:model="content" label="Mensaje" rows="6" placeholder="Escriba el detalle del comunicado aquí..." />

                    <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-xl border border-zinc-200 dark:border-zinc-700">
                        <flux:checkbox wire:model="requiresAuthorization" label="Requiere Autorización" description="Active esta opción si necesita que el padre de familia otorgue un permiso explícito (Sí/No) además de darse por enterado." />
                    </div>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button wire:click="$set('showCreateModal', false)">Cancelar</flux:button>
                    <flux:button variant="primary" type="submit">{{ $editingNoticeId ? 'Actualizar Aviso' : 'Publicar Aviso' }}</flux:button>
                </div>
            </form>
        </flux:modal>

        <!-- Signatures Detail Modal -->
        <flux:modal wire:model="showSignaturesModal" class="md:w-160">
            <div class="space-y-6">
                <header>
                    <flux:heading size="lg">Detalles de Firmas</flux:heading>
                    @if($viewingSignaturesNoticeId)
                        <flux:text size="sm" class="mt-1 whitespace-normal wrap-break-word">Progreso para: <span class="font-bold">{{ App\Models\Notice::find($viewingSignaturesNoticeId)?->title }}</span></flux:text>
                    @endif
                </header>

                @if(!empty($signatureStats))
                    <div class="grid grid-cols-3 gap-4">
                        <div class="p-4 rounded-xl bg-green-50 dark:bg-green-900/10 border border-green-100 dark:border-green-800/30 text-center">
                            <flux:text size="xs" class="uppercase tracking-wider font-bold text-green-700 dark:text-green-300">Firmados</flux:text>
                            <flux:heading size="xl" class="text-green-800 dark:text-green-200">{{ $signatureStats['signed'] }}</flux:heading>
                        </div>
                        <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 text-center">
                            <flux:text size="xs" class="uppercase tracking-wider font-bold text-zinc-500">Esperados</flux:text>
                            <flux:heading size="xl">{{ $signatureStats['expected'] }}</flux:heading>
                        </div>
                        <div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-800/30 text-center">
                            <flux:text size="xs" class="uppercase tracking-wider font-bold text-blue-700 dark:text-blue-300">Progreso</flux:text>
                            <flux:heading size="xl" class="text-blue-800 dark:text-blue-200">{{ $signatureStats['percentage'] }}%</flux:heading>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex border-b border-zinc-200 dark:border-zinc-700">
                            <button 
                                type="button"
                                wire:click="$set('activeTab', 'signed')"
                                class="px-4 py-2 text-sm font-medium transition-colors relative {{ $activeTab === 'signed' ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
                            >
                                Firmados ({{ count($signedList) }})
                                @if($activeTab === 'signed')
                                    <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600 dark:bg-blue-400"></div>
                                @endif
                            </button>
                            <button 
                                type="button"
                                wire:click="$set('activeTab', 'pending')"
                                class="px-4 py-2 text-sm font-medium transition-colors relative {{ $activeTab === 'pending' ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
                            >
                                Pendientes ({{ count($pendingList) }})
                                @if($activeTab === 'pending')
                                    <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600 dark:bg-blue-400"></div>
                                @endif
                            </button>
                            <button 
                                type="button"
                                wire:click="$set('activeTab', 'groups')"
                                class="px-4 py-2 text-sm font-medium transition-colors relative {{ $activeTab === 'groups' ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}"
                            >
                                Por Grupo ({{ count($groupStats) }})
                                @if($activeTab === 'groups')
                                    <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600 dark:bg-blue-400"></div>
                                @endif
                            </button>
                        </div>

                        @if($activeTab === 'signed')
                            <div class="max-h-80 overflow-y-auto space-y-2 pr-2 animate-in fade-in duration-200">
                                @if($groupFilter)
                                    <div class="flex items-center gap-2 mb-4">
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-bold border border-blue-100 dark:border-blue-800 shadow-sm">
                                            <flux:icon icon="funnel" variant="micro" class="shrink-0" />
                                            <span>Filtro: {{ $groupFilter }}</span>
                                            <button type="button" wire:click="$set('groupFilter', null)" class="ml-1 p-0.5 hover:bg-blue-200 dark:hover:bg-blue-700 rounded-md transition-colors" title="Quitar filtro">
                                                <flux:icon icon="x-mark" variant="micro" />
                                            </button>
                                        </div>
                                    </div>
                                @endif
                                @forelse(collect($signedList)->when($groupFilter, fn($c) => $c->where('student_grade_group', $groupFilter)) as $item)
                                    <div class="flex items-center justify-between p-3 rounded-lg border border-zinc-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm">
                                        <div class="whitespace-normal wrap-break-word">
                                            <flux:text font="medium">{{ $item['student_name'] }} <span class="text-sm font-normal text-zinc-500">({{ $item['student_grade_group'] }})</span></flux:text>
                                            <flux:text size="xs" class="text-zinc-500">Firmado por: {{ $item['parent_name'] }}</flux:text>
                                        </div>
                                        <div class="text-right">
                                            <flux:text size="xs" class="text-zinc-500">{{ $item['date'] }}</flux:text>
                                            @if($item['authorized'] !== null)
                                                <flux:badge size="xs" color="{{ $item['authorized'] ? 'green' : 'red' }}" class="mt-1">
                                                    {{ $item['authorized'] ? 'Autorizado' : 'No Autorizado' }}
                                                </flux:badge>
                                            @else
                                                <flux:badge size="xs" color="green" class="mt-1">
                                                    Firmado
                                                </flux:badge>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="py-12 text-center text-zinc-500 italic">
                                        Nadie ha firmado todavía.
                                    </div>
                                @endforelse
                            </div>
                        @elseif($activeTab === 'pending')
                            <div class="max-h-80 overflow-y-auto space-y-2 pr-2 animate-in fade-in duration-200">
                                @if($groupFilter)
                                    <div class="flex items-center gap-2 mb-4">
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-bold border border-blue-100 dark:border-blue-800 shadow-sm">
                                            <flux:icon icon="funnel" variant="micro" class="shrink-0" />
                                            <span>Filtro: {{ $groupFilter }}</span>
                                            <button type="button" wire:click="$set('groupFilter', null)" class="ml-1 p-0.5 hover:bg-blue-200 dark:hover:bg-blue-700 rounded-md transition-colors" title="Quitar filtro">
                                                <flux:icon icon="x-mark" variant="micro" />
                                            </button>
                                        </div>
                                    </div>
                                @endif
                                @forelse(collect($pendingList)->when($groupFilter, fn($c) => $c->where('grade_group', $groupFilter)) as $item)
                                    <div class="p-3 rounded-lg border border-zinc-100 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-sm flex items-center justify-between group/item">
                                        <div>
                                            <flux:text font="medium">{{ $item['name'] }} <span class="text-sm font-normal text-zinc-500">({{ $item['grade_group'] }})</span></flux:text>
                                            <flux:text size="xs" class="text-zinc-500">Esperando firma del tutor</flux:text>
                                        </div>
                                        <flux:button 
                                            variant="ghost" 
                                            size="sm" 
                                            icon="document-plus" 
                                            title="Asignar reporte" 
                                            href="{{ route('reports.index', ['open_create' => 1, 'student_id' => $item['id'] ?? '', 'student_name' => $item['name']]) }}"
                                            wire:navigate
                                            class="text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20"
                                        />
                                    </div>
                                @empty
                                    <div class="py-16 text-center text-green-600 dark:text-green-400 font-medium">
                                        <flux:icon icon="check-circle" class="mx-auto mb-2" />
                                        ¡Todos han firmado! 🎉
                                    </div>
                                @endforelse
                            </div>
                        @elseif($activeTab === 'groups')
                            <div class="max-h-80 overflow-y-auto animate-in fade-in duration-200">
                                <table class="w-full text-left text-sm">
                                    <thead class="sticky top-0 bg-white dark:bg-zinc-900 z-10">
                                        <tr class="border-b border-zinc-200 dark:border-zinc-700 text-zinc-500">
                                            <th class="py-2 px-1 font-semibold uppercase tracking-wider text-[10px]">Grado/Grupo</th>
                                            <th class="py-2 px-1 font-semibold uppercase tracking-wider text-[10px] text-center">Firmados</th>
                                            <th class="py-2 px-1 font-semibold uppercase tracking-wider text-[10px] text-center">Faltan</th>
                                            <th class="py-2 px-1 font-semibold uppercase tracking-wider text-[10px] text-center">Total</th>
                                            <th class="py-2 px-1 font-semibold uppercase tracking-wider text-[10px] text-right">%</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                        @foreach($groupStats as $group => $stats)
                                            @php $percentage = $stats['total'] > 0 ? round(($stats['signed'] / $stats['total']) * 100) : 0; @endphp
                                            <tr>
                                                <td class="py-3 px-1 font-bold text-zinc-700 dark:text-zinc-300">{{ $group }}</td>
                                                <td class="py-3 px-1 text-center">
                                                    <button 
                                                        type="button"
                                                        wire:click="$set('groupFilter', '{{ $group }}'); $set('activeTab', 'signed')"
                                                        class="inline-flex items-center justify-center w-6 h-6 rounded-full {{ $stats['signed'] > 0 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 hover:bg-green-200' : 'bg-zinc-100 text-zinc-400' }} text-xs font-bold transition-colors cursor-pointer"
                                                        title="Ver firmados de {{ $group }}"
                                                    >
                                                        {{ $stats['signed'] }}
                                                    </button>
                                                </td>
                                                <td class="py-3 px-1 text-center">
                                                    <button 
                                                        type="button"
                                                        wire:click="$set('groupFilter', '{{ $group }}'); $set('activeTab', 'pending')"
                                                        class="inline-flex items-center justify-center w-6 h-6 rounded-full {{ $stats['pending'] > 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 hover:bg-amber-200' : 'bg-zinc-100 text-zinc-400' }} text-xs font-bold transition-colors cursor-pointer"
                                                        title="Ver pendientes de {{ $group }}"
                                                    >
                                                        {{ $stats['pending'] }}
                                                    </button>
                                                </td>
                                                <td class="py-3 px-1 text-center font-medium text-zinc-500">{{ $stats['total'] }}</td>
                                                <td class="py-3 px-1 text-right">
                                                    <div class="flex items-center justify-end gap-2">
                                                        <div class="w-12 h-1.5 bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden hidden sm:block">
                                                            <div class="h-full bg-blue-500" style="width: {{ $percentage }}%"></div>
                                                        </div>
                                                        <span class="text-xs font-black {{ $percentage == 100 ? 'text-green-600' : 'text-blue-600' }}">{{ $percentage }}%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="flex justify-end">
                    <flux:button wire:click="$set('showSignaturesModal', false)">Cerrar</flux:button>
                </div>
            </div>
        </flux:modal>

        <!-- Delete Confirmation Modal -->
        <flux:modal name="delete-notice" wire:model="showDeleteModal" class="md:w-96">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">¿Eliminar aviso?</flux:heading>
                    <flux:text class="mt-2 text-zinc-500">Esta acción no se puede deshacer. Todos los padres dejarán de ver este comunicado inmediatamente.</flux:text>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button wire:click="$set('showDeleteModal', false)">Cancelar</flux:button>
                    <flux:button variant="danger" wire:click="deleteNotice">Eliminar</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>