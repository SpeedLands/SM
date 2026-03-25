<?php

use App\Models\Student;
use App\Models\Report;
use App\Models\Citation;
use App\Models\Notice;
use App\Models\Cycle;
use App\Models\Attendance;
use App\Models\CommunityService;
use Livewire\Volt\Component;

new class extends Component {
    public string $infractionGrade = '';
    public string $infractionGroup = '';

    public function updatedInfractionGrade(): void
    {
        $this->infractionGroup = '';
    }

    public function with(): array
    {
        $user = auth()->user();
        $activeCycle = Cycle::where('is_active', true)->first();
        
        if ($user->isViewParent() && ! $user->isViewStaff()) {
            return $this->getParentStats($activeCycle);
        }

        if ($user->isViewStaff()) {
            return $this->getAdminStats($activeCycle);
        }

        if ($user->isParent()) {
            return $this->getParentStats($activeCycle);
        }

        return [
            'role' => $user->role,
            'activeCycle' => $activeCycle,
        ];
    }

    protected function getAdminStats(?Cycle $activeCycle): array
    {
        $stats = [
            'totalStudents' => $activeCycle 
                ? Student::whereHas('cycleAssociations', fn($q) => $q->where('cycle_id', $activeCycle->id))->count() 
                : 0,
            'totalReports' => $activeCycle ? Report::where('cycle_id', $activeCycle->id)->count() : 0,
            'activeCitations' => $activeCycle ? Citation::where('cycle_id', $activeCycle->id)->where('status', 'PENDING')->count() : 0,
            'activeNotices' => $activeCycle ? Notice::where('cycle_id', $activeCycle->id)->count() : 0,
        ];

        $recentReports = Report::with('student')
            ->when($activeCycle, fn($q) => $q->where('cycle_id', $activeCycle->id))
            ->latest('date')
            ->limit(5)
            ->get();
            
        $upcomingCitations = Citation::with(['student', 'teacher'])
            ->when($activeCycle, fn($q) => $q->where('cycle_id', $activeCycle->id))
            ->where('status', 'PENDING')
            ->orderBy('citation_date')
            ->limit(5)
            ->get();

        // Reports by classroom (grade + group)
        $reportsByClassroom = $activeCycle
            ? Report::where('cycle_id', $activeCycle->id)
                ->join('students', 'reports.student_id', '=', 'students.id')
                ->selectRaw("students.grade, students.group_name, COUNT(*) as total")
                ->groupBy('students.grade', 'students.group_name')
                ->orderByDesc('total')
                ->get()
                ->map(fn($r) => [
                    'grade' => $r->grade,
                    'group' => $r->group_name,
                    'total' => $r->total,
                ])
                ->values()
                ->toArray()
            : [];

        // Reports by infraction type (top infractions) — filterable by grade/group
        $reportsByInfraction = $activeCycle
            ? Report::where('cycle_id', $activeCycle->id)
                ->join('infractions', 'reports.infraction_id', '=', 'infractions.id')
                ->join('students', 'reports.student_id', '=', 'students.id')
                ->when($this->infractionGrade, fn($q) => $q->where('students.grade', $this->infractionGrade))
                ->when($this->infractionGroup, fn($q) => $q->where('students.group_name', $this->infractionGroup))
                ->selectRaw('infractions.description, infractions.severity, COUNT(*) as total')
                ->groupBy('infractions.id', 'infractions.description', 'infractions.severity')
                ->orderByDesc('total')
                ->get()
                ->map(fn($r) => [
                    'description' => $r->description,
                    'severity' => $r->severity,
                    'total' => $r->total,
                ])
                ->values()
                ->toArray()
            : [];

        // Available grades and groups for the infraction filter
        $availableGrades = $activeCycle
            ? Student::whereHas('cycleAssociations', fn($q) => $q->where('cycle_id', $activeCycle->id))
                ->distinct()->orderBy('grade')->pluck('grade')->toArray()
            : [];
        $availableGroups = $activeCycle
            ? Student::whereHas('cycleAssociations', fn($q) => $q->where('cycle_id', $activeCycle->id))
                ->when($this->infractionGrade, fn($q) => $q->where('grade', $this->infractionGrade))
                ->distinct()->orderBy('group_name')->pluck('group_name')->toArray()
            : [];

        // Absences by classroom (grade + group) within active cycle dates
        $attendanceByClassroom = $activeCycle
            ? Attendance::join('students', 'attendances.student_id', '=', 'students.id')
                ->whereBetween('attendances.date', [$activeCycle->start_date, $activeCycle->end_date])
                ->where('attendances.status', 'FALTA')
                ->selectRaw("students.grade, students.group_name, COUNT(*) as total")
                ->groupBy('students.grade', 'students.group_name')
                ->orderByDesc('total')
                ->get()
                ->map(fn($r) => [
                    'grade' => $r->grade,
                    'group' => $r->group_name,
                    'total' => $r->total,
                ])
                ->values()
                ->toArray()
            : [];

        return array_merge($stats, [
            'recentReports' => $recentReports,
            'upcomingCitations' => $upcomingCitations,
            'reportsByClassroom' => $reportsByClassroom,
            'reportsByInfraction' => $reportsByInfraction,
            'availableGrades' => $availableGrades,
            'availableGroups' => $availableGroups,
            'attendanceByClassroom' => $attendanceByClassroom,
            'activeCycle' => $activeCycle,
            'isAdmin' => true,
        ]);
    }

    protected function getParentStats(?Cycle $activeCycle): array
    {
        $user = auth()->user();
        $myStudents = $user->students()->with(['reports' => function($q) use ($activeCycle) {
            if ($activeCycle) $q->where('cycle_id', $activeCycle->id);
        }, 'communityServices' => function($q) use ($activeCycle) {
             if ($activeCycle) $q->where('cycle_id', $activeCycle->id);
        }])->get();

        $studentIds = $myStudents->pluck('id')->toArray();
        
        $citations = Citation::whereIn('student_id', $studentIds)
            ->when($activeCycle, fn($q) => $q->where('cycle_id', $activeCycle->id))
            ->where('status', 'PENDING')
            ->orderBy('citation_date')
            ->get();

        $notices = Notice::where(function($q) {
                $q->where('target_audience', 'ALL')
                  ->orWhere('target_audience', 'PARENTS');
            })
            ->when($activeCycle, fn($q) => $q->where('cycle_id', $activeCycle->id))
            ->latest('date')
            ->limit(5)
            ->get();

        return [
            'myStudents' => $myStudents,
            'citations' => $citations,
            'notices' => $notices,
            'activeCycle' => $activeCycle,
            'totalPending' => $user->getPendingNotificationsCount(),
            'isParent' => true,
        ];
    }
}; ?>

<div class="space-y-8 pb-10">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">¡Bienvenido, {{ auth()->user()->name }}!</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400 text-lg">
                {{ $activeCycle ? "Ciclo Escolar Activo: {$activeCycle->name}" : 'No hay un ciclo escolar activo actualmente.' }}
            </flux:text>
        </div>
    </div>

    @if(auth()->user()->isViewStaff())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('reports.index', ['open_create' => true]) }}" class="group p-4 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 hover:border-blue-500/50 hover:bg-blue-50/10 transition-all active:scale-[0.98] flex items-center gap-4">
                <div class="size-10 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400">
                    <flux:icon icon="plus" size="sm" variant="solid" />
                </div>
                <div class="text-xs font-bold uppercase tracking-tight">Nuevo Reporte</div>
            </a>
        </div>
    @endif

    @if(auth()->user()->isParent() && ($totalPending ?? 0) > 0)
        <flux:callout variant="danger" heading="Trámites Pendientes de Atención" icon="exclamation-triangle">
            Tienes {{ $totalPending }} documento(s) o aviso(s) que requieren tu firma o revisión.
            <x-slot name="actions">
                <flux:button size="sm" variant="primary" href="{{ route('reports.index') }}" icon="pencil-square">Revisar Pendientes</flux:button>
            </x-slot>
        </flux:callout>
    @endif

    @if(isset($isAdmin) && $isAdmin)
        <!-- Admin/Teacher Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm flex items-center gap-4">
                <div class="size-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                    <flux:icon icon="user-group" variant="solid" />
                </div>
                <div>
                    <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Alumnos (Ciclo)</div>
                    <div class="text-2xl font-bold">{{ $totalStudents }}</div>
                </div>
            </div>

            <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm flex items-center gap-4">
                <div class="size-12 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-600 dark:text-red-400">
                    <flux:icon icon="document-text" variant="solid" />
                </div>
                <div>
                    <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Reportes (Ciclo)</div>
                    <div class="text-2xl font-bold">{{ $totalReports }}</div>
                </div>
            </div>

            <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm flex items-center gap-4">
                <div class="size-12 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                    <flux:icon icon="calendar-days" variant="solid" />
                </div>
                <div>
                    <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Citatorios Pendientes</div>
                    <div class="text-2xl font-bold">{{ $activeCitations }}</div>
                </div>
            </div>

            <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm flex items-center gap-4">
                <div class="size-12 rounded-xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400">
                    <flux:icon icon="megaphone" variant="solid" />
                </div>
                <div>
                    <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Avisos Activos</div>
                    <div class="text-2xl font-bold">{{ $activeNotices }}</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Recent Reports -->
            <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <flux:heading size="lg">Reportes Recientes</flux:heading>
                    <flux:button variant="ghost" size="sm" icon="arrow-right" href="{{ route('reports.index') }}">Ver todos</flux:button>
                </div>
                <div class="space-y-4">
                    @forelse($recentReports as $report)
                        <a href="{{ route('reports.index', ['search' => $report->student->name]) }}" class="flex items-center gap-4 p-3 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors border border-transparent hover:border-zinc-100 dark:hover:border-zinc-800">
                            <div class="size-10 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center shrink-0">
                                <flux:icon icon="document" size="sm" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-bold truncate uppercase">{{ $report->student->name }}</div>
                                <div class="text-xs text-zinc-500 truncate">{{ $report->subject }}</div>
                            </div>
                            <div class="text-xs font-medium text-zinc-400">{{ $report->date ? $report->date->diffForHumans() : '' }}</div>
                        </a>
                    @empty
                        <div class="text-center py-8 text-zinc-500 italic text-sm">No hay reportes recientes registrados.</div>
                    @endforelse
                </div>
            </div>

            <!-- Upcoming Citations -->
            <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <flux:heading size="lg">Próximos Citatorios</flux:heading>
                    <flux:button variant="ghost" size="sm" icon="arrow-right" href="{{ route('citations.index') }}">Ver todos</flux:button>
                </div>
                <div class="space-y-4">
                    @forelse($upcomingCitations as $citation)
                        <a href="{{ route('citations.index', ['search' => $citation->student->name]) }}" class="flex items-center gap-4 p-3 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors border border-transparent hover:border-zinc-100 dark:hover:border-zinc-800">
                            <div class="size-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center shrink-0 text-amber-600">
                                <flux:icon icon="calendar" size="sm" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-bold truncate uppercase">{{ $citation->student->name }}</div>
                                <div class="text-xs text-zinc-500 truncate">{{ $citation->reason }}</div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-sm font-bold text-zinc-900 dark:text-white">{{ $citation->citation_date->format('d/m/Y') }}</div>
                                <div class="text-[10px] text-zinc-500">{{ $citation->citation_date->format('H:i') }}</div>
                            </div>
                        </a>
                    @empty
                        <div class="text-center py-8 text-zinc-500 italic text-sm">No hay citatorios pendientes.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Reports by Classroom -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm" x-data="{ showAll: false }">
                <div class="flex items-center justify-between mb-6">
                    <flux:heading size="lg">Reportes por Salón</flux:heading>
                    <flux:badge size="sm" color="zinc">Ciclo Activo</flux:badge>
                </div>

                @if(count($reportsByClassroom) === 0)
                    <div class="text-center py-8 text-zinc-500 italic text-sm">No hay reportes registrados en el ciclo activo.</div>
                @else
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700 text-xs uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                <th class="py-2 px-2 font-semibold">#</th>
                                <th class="py-2 px-2 font-semibold">Grado</th>
                                <th class="py-2 px-2 font-semibold">Grupo</th>
                                <th class="py-2 px-2 text-right font-semibold">Reportes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($reportsByClassroom as $index => $classroom)
                                <tr
                                    x-show="showAll || {{ $index }} < 5"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors"
                                >
                                    <td class="py-3 px-2 text-zinc-400 font-mono text-xs">{{ $index + 1 }}</td>
                                    <td class="py-3 px-2">
                                        <flux:badge size="sm" color="blue">{{ $classroom['grade'] }}</flux:badge>
                                    </td>
                                    <td class="py-3 px-2">
                                        <flux:badge size="sm" color="neutral">{{ $classroom['group'] }}</flux:badge>
                                    </td>
                                    <td class="py-3 px-2 text-right">
                                        <span class="inline-flex items-center gap-1.5 font-bold {{ $index === 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-white' }}">
                                            @if($index === 0)
                                                <div class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></div>
                                            @endif
                                            {{ $classroom['total'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if(count($reportsByClassroom) > 5)
                        <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800 text-center">
                            <flux:button variant="ghost" size="sm" x-on:click="showAll = !showAll">
                                <span x-show="!showAll">Mostrar todos ({{ count($reportsByClassroom) }})</span>
                                <span x-show="showAll" x-cloak>Mostrar solo Top 5</span>
                            </flux:button>
                        </div>
                    @endif
                @endif
            </div>

            <!-- Absences by Classroom -->
            <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm" x-data="{ showAllAtt: false }">
                <div class="flex items-center justify-between mb-6">
                    <flux:heading size="lg">Inasistencias por Salón</flux:heading>
                    <flux:badge size="sm" color="zinc">Ciclo Activo</flux:badge>
                </div>

                @if(count($attendanceByClassroom) === 0)
                    <div class="text-center py-8 text-zinc-500 italic text-sm">No hay inasistencias registradas en el ciclo activo.</div>
                @else
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700 text-xs uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                <th class="py-2 px-2 font-semibold">#</th>
                                <th class="py-2 px-2 font-semibold">Grado</th>
                                <th class="py-2 px-2 font-semibold">Grupo</th>
                                <th class="py-2 px-2 text-right font-semibold">Faltas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($attendanceByClassroom as $index => $classroom)
                                <tr
                                    x-show="showAllAtt || {{ $index }} < 5"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors"
                                >
                                    <td class="py-3 px-2 text-zinc-400 font-mono text-xs">{{ $index + 1 }}</td>
                                    <td class="py-3 px-2">
                                        <flux:badge size="sm" color="blue">{{ $classroom['grade'] }}</flux:badge>
                                    </td>
                                    <td class="py-3 px-2">
                                        <flux:badge size="sm" color="neutral">{{ $classroom['group'] }}</flux:badge>
                                    </td>
                                    <td class="py-3 px-2 text-right">
                                        <span class="inline-flex items-center gap-1.5 font-bold {{ $index === 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-white' }}">
                                            @if($index === 0)
                                                <div class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></div>
                                            @endif
                                            {{ $classroom['total'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if(count($attendanceByClassroom) > 5)
                        <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800 text-center">
                            <flux:button variant="ghost" size="sm" x-on:click="showAllAtt = !showAllAtt">
                                <span x-show="!showAllAtt">Mostrar todos ({{ count($attendanceByClassroom) }})</span>
                                <span x-show="showAllAtt" x-cloak>Mostrar solo Top 5</span>
                            </flux:button>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <!-- Reports by Infraction Type -->
        <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm" x-data="{ showAllInf: false }">
            <div class="flex items-center justify-between mb-4">
                <flux:heading size="lg">Tipos de Reporte más Frecuentes</flux:heading>
                <flux:badge size="sm" color="zinc">Ciclo Activo</flux:badge>
            </div>

            {{-- Grade & Group Filters --}}
            <div class="flex flex-wrap items-center gap-3 mb-6">
                <div class="flex-1 min-w-30 max-w-45">
                    <select wire:model.live="infractionGrade" class="w-full text-sm rounded-lg border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos los grados</option>
                        @foreach($availableGrades as $grade)
                            <option value="{{ $grade }}">{{ $grade }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 min-w-30 max-w-45">
                    <select wire:model.live="infractionGroup" class="w-full text-sm rounded-lg border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos los grupos</option>
                        @foreach($availableGroups as $group)
                            <option value="{{ $group }}">{{ $group }}</option>
                        @endforeach
                    </select>
                </div>
                @if($infractionGrade || $infractionGroup)
                    <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="$set('infractionGrade', ''); $set('infractionGroup', '')">
                        Limpiar
                    </flux:button>
                @endif
            </div>

            @if(count($reportsByInfraction) === 0)
                <div class="text-center py-8 text-zinc-500 italic text-sm">No hay reportes registrados{{ $infractionGrade || $infractionGroup ? ' con los filtros seleccionados' : ' en el ciclo activo' }}.</div>
            @else
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700 text-xs uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            <th class="py-2 px-2 font-semibold">#</th>
                            <th class="py-2 px-2 font-semibold">Infracción</th>
                            <th class="py-2 px-2 font-semibold">Severidad</th>
                            <th class="py-2 px-2 text-right font-semibold">Reportes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach($reportsByInfraction as $index => $infraction)
                            <tr
                                x-show="showAllInf || {{ $index }} < 5"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors"
                            >
                                <td class="py-3 px-2 text-zinc-400 font-mono text-xs">{{ $index + 1 }}</td>
                                <td class="py-3 px-2">
                                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $infraction['description'] }}</span>
                                </td>
                                <td class="py-3 px-2">
                                    <flux:badge size="sm" :color="$infraction['severity'] === 'GRAVE' ? 'red' : 'zinc'">{{ $infraction['severity'] }}</flux:badge>
                                </td>
                                <td class="py-3 px-2 text-right">
                                    <span class="inline-flex items-center gap-1.5 font-bold {{ $index === 0 ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-white' }}">
                                        @if($index === 0)
                                            <div class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></div>
                                        @endif
                                        {{ $infraction['total'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if(count($reportsByInfraction) > 5)
                    <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-800 text-center">
                        <flux:button variant="ghost" size="sm" x-on:click="showAllInf = !showAllInf">
                            <span x-show="!showAllInf">Mostrar todos ({{ count($reportsByInfraction) }})</span>
                            <span x-show="showAllInf" x-cloak>Mostrar solo Top 5</span>
                        </flux:button>
                    </div>
                @endif
            @endif
        </div>
    @endif

    @if(isset($isParent) && $isParent)
        <!-- Parent Dashboard -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Students Column -->
            <div class="lg:col-span-2 space-y-6">
                <flux:heading size="lg">Mis Hijos / Alumnos Vinculados</flux:heading>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @forelse($myStudents as $student)
                        <div class="p-6 rounded-4xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm relative overflow-hidden group">
                            <!-- Background Accent -->
                            <div class="absolute top-0 right-0 size-24 bg-blue-500/5 dark:bg-blue-500/10 rounded-bl-full -mr-8 -mt-8"></div>
                            
                            <div class="flex items-start gap-4 mb-6">
                                <div class="size-14 rounded-2xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0">
                                    <flux:icon icon="user" size="lg" variant="solid" />
                                </div>
                                <div class="min-w-0">
                                    <div class="text-lg font-extrabold text-zinc-900 dark:text-white uppercase truncate">{{ $student->name }}</div>
                                    <div class="flex gap-2 mt-1">
                                        <flux:badge size="sm" color="blue" variant="outline">{{ $student->grade }}</flux:badge>
                                        <flux:badge size="sm" color="neutral" variant="outline">{{ $student->group_name }}</flux:badge>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-800">
                                    <div class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-1">Reportes</div>
                                    <div class="text-xl font-bold flex items-center gap-2">
                                        {{ $student->reports->count() }}
                                        @if($student->reports->count() > 0)
                                            <flux:icon icon="exclamation-circle" size="sm" class="text-red-500" />
                                        @else
                                            <flux:icon icon="check-circle" size="sm" class="text-emerald-500" />
                                        @endif
                                    </div>
                                </div>
                                <div class="p-4 rounded-2xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-100 dark:border-zinc-800">
                                    <div class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest mb-1">Servicio</div>
                                    <div class="text-xl font-bold flex items-center gap-2">
                                        {{ $student->communityServices->where('status', 'COMPLETED')->count() }}
                                        <flux:icon icon="briefcase" size="sm" class="text-blue-500" />
                                    </div>
                                </div>
                            </div>

                            @php
                                $studentPending = auth()->user()->getPendingNotificationsCount($student->id);
                            @endphp

                            @if($studentPending > 0)
                                <div class="mt-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-800/20 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="size-2 rounded-full bg-red-500 animate-pulse"></div>
                                        <span class="text-xs font-bold text-red-700 dark:text-red-400">{{ $studentPending }} Trámite(s) Pendiente(s)</span>
                                    </div>
                                    <flux:button size="xs" variant="ghost" icon="chevron-right" href="{{ route('reports.index') }}" />
                                </div>
                            @endif

                            <div class="mt-6">
                                <flux:button variant="primary" block href="{{ route('students.index') }}" icon:trailing="arrow-right">Ver Detalles</flux:button>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full p-12 text-center rounded-[2.5rem] bg-zinc-100 dark:bg-zinc-900/50 border-2 border-dashed border-zinc-300 dark:border-zinc-700">
                            <flux:icon icon="user-plus" size="xl" class="mx-auto mb-4 text-zinc-400" />
                            <flux:heading size="lg" class="mb-2">No tienes alumnos vinculados</flux:heading>
                            <flux:text>Contacta a la administración para vincularte con la cuenta de tus hijos.</flux:text>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Side Column: Citations and Notices -->
            <div class="space-y-8">
                <!-- Upcoming Citations -->
                <div class="p-6 rounded-4xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm">
                    <flux:heading size="lg" class="mb-6">Citatorios Próximos</flux:heading>
                    <div class="space-y-4">
                        @forelse($citations as $citation)
                            <a href="{{ route('citations.index', ['search' => $citation->student->name]) }}" class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/20 flex gap-4 hover:bg-amber-100 dark:hover:bg-amber-900/20 transition-colors">
                                <div class="shrink-0">
                                    <div class="size-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600">
                                        <flux:icon icon="calendar" size="sm" />
                                    </div>
                                </div>
                                <div>
                                    <div class="text-sm font-bold uppercase">{{ $citation->student->name }}</div>
                                    <div class="text-xs text-amber-800 dark:text-amber-300 mt-1">{{ $citation->reason }}</div>
                                    <div class="text-xs font-bold mt-2 text-zinc-900 dark:text-white">
                                        {{ $citation->citation_date->format('d/m/Y') }} a las {{ $citation->citation_date->format('H:i') }}
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="text-center py-6 text-zinc-500 italic text-sm border border-dashed border-zinc-200 dark:border-zinc-800 rounded-2xl">
                                No tienes citatorios pendientes.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Recent Notices -->
                <div class="p-6 rounded-4xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm">
                    <flux:heading size="lg" class="mb-6">Avisos Generales</flux:heading>
                    <div class="space-y-4">
                        @forelse($notices as $notice)
                            <div class="group cursor-pointer">
                                <div class="text-sm font-bold text-zinc-900 dark:text-white group-hover:text-blue-600 transition-colors">{{ $notice->title }}</div>
                                <div class="text-xs text-zinc-500 mt-1 line-clamp-2">{{ $notice->content }}</div>
                                <div class="text-[10px] text-zinc-400 mt-2 uppercase font-bold tracking-wider">{{ $notice->date ? $notice->date->diffForHumans() : '' }}</div>
                                @if(!$loop->last)
                                    <flux:separator class="mt-4" />
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-6 text-zinc-500 italic text-sm">No hay avisos recientes.</div>
                        @endforelse
                        
                        <div class="mt-4">
                            <flux:button variant="ghost" size="sm" block href="{{ route('notices.index') }}">Ver todos los avisos</flux:button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>