<?php

use App\Models\CommunityService;
use App\Models\Citation;
use App\Models\Notice;
use App\Models\ExamSchedule;
use App\Models\Report;
use App\Models\Cycle;
use Livewire\Volt\Component;
use Carbon\Carbon;

new class extends Component {
    public int $currentYear;
    public int $currentMonth;
    public ?string $selectedDate = null;
    public bool $showOffcanvas = false;

    // Data for selected day
    public array $dayServices = [];
    public array $dayNotices = [];
    public array $dayCitations = [];
    public array $dayExams = [];
    public array $dayReports = [];

    // Dates with events (for badges)
    public array $datesWithEvents = [];

    public function mount(): void
    {
        $this->authorize('teacher-or-admin');
        $this->currentYear = now()->year;
        $this->currentMonth = now()->month;
        $this->loadDatesWithEvents();
    }

    public function previousMonth(): void
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentYear = $date->year;
        $this->currentMonth = $date->month;
        $this->loadDatesWithEvents();
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentYear = $date->year;
        $this->currentMonth = $date->month;
        $this->loadDatesWithEvents();
    }

    public function goToToday(): void
    {
        $this->currentYear = now()->year;
        $this->currentMonth = now()->month;
        $this->loadDatesWithEvents();
    }

    public function loadDatesWithEvents(): void
    {
        $activeCycle = Cycle::where('is_active', true)->first();
        if (!$activeCycle) {
            $this->datesWithEvents = [];
            return;
        }

        $startOfMonth = Carbon::create($this->currentYear, $this->currentMonth, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $dates = [];

        // Community Services
        $services = CommunityService::where('cycle_id', $activeCycle->id)
            ->whereBetween('scheduled_date', [$startOfMonth, $endOfMonth])
            ->get();
        foreach ($services as $s) {
            $dates[$s->scheduled_date->format('Y-m-d')] = true;
        }

        // Citations
        $citations = Citation::where('cycle_id', $activeCycle->id)
            ->whereBetween('citation_date', [$startOfMonth, $endOfMonth])
            ->get();
        foreach ($citations as $c) {
            $dates[$c->citation_date->format('Y-m-d')] = true;
        }

        // Notices with event_date
        $notices = Notice::where('cycle_id', $activeCycle->id)
            ->whereNotNull('event_date')
            ->whereBetween('event_date', [$startOfMonth, $endOfMonth])
            ->get();
        foreach ($notices as $n) {
            $dates[$n->event_date->format('Y-m-d')] = true;
        }

        // Exams
        $exams = ExamSchedule::where('cycle_id', $activeCycle->id)
            ->whereBetween('exam_date', [$startOfMonth, $endOfMonth])
            ->get();
        foreach ($exams as $e) {
            $dates[$e->exam_date->format('Y-m-d')] = true;
        }

        // Reports
        $reports = Report::where('cycle_id', $activeCycle->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();
        foreach ($reports as $r) {
            $dates[$r->date->format('Y-m-d')] = true;
        }

        $this->datesWithEvents = $dates;
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->loadDayActivities($date);
        $this->showOffcanvas = true;
    }

    public function closeOffcanvas(): void
    {
        $this->showOffcanvas = false;
        $this->selectedDate = null;
    }

    protected function loadDayActivities(string $date): void
    {
        $activeCycle = Cycle::where('is_active', true)->first();
        if (!$activeCycle) {
            $this->dayServices = [];
            $this->dayNotices = [];
            $this->dayCitations = [];
            $this->dayExams = [];
            return;
        }

        $targetDate = Carbon::parse($date);

        // Community Services
        $this->dayServices = CommunityService::with('student')
            ->where('cycle_id', $activeCycle->id)
            ->whereDate('scheduled_date', $targetDate)
            ->get()
            ->toArray();

        // Citations
        $this->dayCitations = Citation::with('student', 'teacher')
            ->where('cycle_id', $activeCycle->id)
            ->whereDate('citation_date', $targetDate)
            ->get()
            ->toArray();

        // Notices with event_date
        $this->dayNotices = Notice::with('author')
            ->where('cycle_id', $activeCycle->id)
            ->whereDate('event_date', $targetDate)
            ->get()
            ->toArray();

        // Exams
        $this->dayExams = ExamSchedule::where('cycle_id', $activeCycle->id)
            ->whereDate('exam_date', $targetDate)
            ->get()
            ->toArray();

        // Reports
        $this->dayReports = Report::with('student', 'teacher', 'infraction')
            ->where('cycle_id', $activeCycle->id)
            ->whereDate('date', $targetDate)
            ->get()
            ->toArray();
    }

    public function getCalendarDays(): array
    {
        $firstOfMonth = Carbon::create($this->currentYear, $this->currentMonth, 1);
        $daysInMonth = $firstOfMonth->daysInMonth;
        $startingDayOfWeek = $firstOfMonth->dayOfWeek; // 0 = Sunday

        $days = [];

        // Add empty cells for days before the 1st
        for ($i = 0; $i < $startingDayOfWeek; $i++) {
            $days[] = null;
        }

        // Add each day of the month
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $days[] = $day;
        }

        return $days;
    }

    public function with(): array
    {
        return [
            'calendarDays' => $this->getCalendarDays(),
            'monthName' => Carbon::create($this->currentYear, $this->currentMonth, 1)->translatedFormat('F Y'),
        ];
    }
}; ?>

<div class="space-y-6 text-zinc-900 dark:text-white pb-10">
    <style>
        @keyframes pulse-badge {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.8; }
        }
        .badge-pulse {
            animation: pulse-badge 1.5s ease-in-out infinite;
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 4px;
        }
        .calendar-day-cell {
            aspect-ratio: 1 / 1;
            width: 100%;
        }
    </style>

    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <flux:heading size="xl" level="1">Calendario General</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">Vista general de actividades escolares por fecha.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" icon="chevron-left" wire:click="previousMonth" />
            <flux:button variant="subtle" wire:click="goToToday">Hoy</flux:button>
            <flux:button variant="ghost" icon="chevron-right" wire:click="nextMonth" />
        </div>
    </div>

    <!-- Calendar Grid -->
    <div class="p-4 md:p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm overflow-hidden">
        <!-- Month Header -->
        <div class="text-center mb-6">
            <flux:heading size="lg" class="capitalize">{{ $monthName }}</flux:heading>
        </div>

        <!-- Day Names Header -->
        <div class="calendar-grid mb-2">
            @foreach(['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'] as $dayName)
                <div class="text-center text-xs md:text-sm font-bold text-zinc-500 dark:text-zinc-400 py-2">
                    {{ $dayName }}
                </div>
            @endforeach
        </div>

        <!-- Calendar Days -->
        <div class="calendar-grid">
            @foreach($calendarDays as $day)
                @if($day === null)
                    <div class="calendar-day-cell"></div>
                @else
                    @php
                        $dateStr = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                        $isToday = $dateStr === now()->format('Y-m-d');
                        $hasEvents = isset($datesWithEvents[$dateStr]);
                    @endphp
                    <button
                        wire:click="selectDate('{{ $dateStr }}')"
                        class="calendar-day-cell relative flex flex-col items-center justify-center rounded-lg border transition-all
                            {{ $isToday 
                                ? 'bg-blue-600 text-white border-blue-600 font-bold shadow-sm' 
                                : 'border-zinc-100 dark:border-zinc-800 bg-zinc-50/30 dark:bg-zinc-800/20 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-600' 
                            }}"
                    >
                        <span class="text-sm md:text-base lg:text-lg">{{ $day }}</span>
                        
                        @if($hasEvents)
                            <span class="absolute bottom-1 md:bottom-2 w-1.5 h-1.5 md:w-2 md:h-2 rounded-full badge-pulse
                                {{ $isToday ? 'bg-white' : 'bg-zinc-900 dark:bg-white' }}">
                            </span>
                        @endif
                    </button>
                @endif
            @endforeach
        </div>

        <!-- Legend -->
        <div class="mt-6 pt-4 border-t border-zinc-200 dark:border-zinc-700 flex flex-wrap items-center justify-center gap-4 text-xs md:text-sm text-zinc-500 dark:text-zinc-400">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-blue-600"></span>
                <span>Hoy</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-zinc-900 dark:bg-white badge-pulse"></span>
                <span>Con actividades</span>
            </div>
        </div>
    </div>

    <!-- Offcanvas -->
    <div
        x-data="{ open: @entangle('showOffcanvas') }"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50" @click="$wire.closeOffcanvas()"></div>

        <!-- Panel -->
        <div
            class="absolute top-0 right-0 h-full w-full sm:w-96 md:w-md bg-white dark:bg-zinc-900 shadow-xl overflow-y-auto"
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
        >
            <!-- Header -->
            <div class="sticky top-0 z-10 bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700 p-4 flex items-center justify-between">
                <div>
                    <flux:heading size="lg">Actividades del Día</flux:heading>
                    @if($selectedDate)
                        <flux:text class="text-zinc-500">{{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d F Y') }}</flux:text>
                    @endif
                </div>
                <flux:button variant="ghost" icon="x-mark" wire:click="closeOffcanvas" />
            </div>

            <!-- Content -->
            <div class="p-4 space-y-6">
                @php
                    $totalActivities = count($dayServices) + count($dayNotices) + count($dayCitations) + count($dayExams) + count($dayReports);
                @endphp

                @if($totalActivities === 0)
                    <div class="py-12 text-center">
                        <flux:icon icon="calendar" class="mx-auto text-zinc-300 dark:text-zinc-600 mb-4" variant="outline" />
                        <flux:text class="text-zinc-500">No hay actividades programadas para este día.</flux:text>
                    </div>
                @else
                    <!-- Community Services -->
                    @if(count($dayServices) > 0)
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <flux:icon icon="briefcase" class="text-green-600" />
                                <flux:heading size="sm">Servicios Comunitarios</flux:heading>
                                <flux:badge size="sm" color="green">{{ count($dayServices) }}</flux:badge>
                            </div>
                            @foreach($dayServices as $service)
                                <div class="p-4 rounded-xl border border-green-200 dark:border-green-900/50 bg-green-50 dark:bg-green-900/20">
                                    <div class="font-bold text-green-900 dark:text-green-100">{{ $service['activity'] }}</div>
                                    <div class="text-sm text-green-700 dark:text-green-300 mt-1">
                                        <span class="font-medium">Alumno:</span> {{ $service['student']['name'] ?? 'N/A' }}
                                    </div>
                                    @if(!empty($service['description']))
                                        <div class="text-xs text-green-600 dark:text-green-400 mt-2 italic">{{ $service['description'] }}</div>
                                    @endif
                                    <div class="mt-2">
                                        @if($service['status'] === 'PENDING')
                                            <flux:badge size="sm" color="amber">Pendiente</flux:badge>
                                        @elseif($service['status'] === 'COMPLETED')
                                            <flux:badge size="sm" color="green">Completado</flux:badge>
                                        @else
                                            <flux:badge size="sm" color="red">Incumplido</flux:badge>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Notices (Events) -->
                    @if(count($dayNotices) > 0)
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <flux:icon icon="megaphone" class="text-blue-600" />
                                <flux:heading size="sm">Eventos / Avisos</flux:heading>
                                <flux:badge size="sm" color="blue">{{ count($dayNotices) }}</flux:badge>
                            </div>
                            @foreach($dayNotices as $notice)
                                <div class="p-4 rounded-xl border border-blue-200 dark:border-blue-900/50 bg-blue-50 dark:bg-blue-900/20">
                                    <div class="font-bold text-blue-900 dark:text-blue-100">{{ $notice['title'] }}</div>
                                    <div class="text-sm text-blue-700 dark:text-blue-300 mt-1 line-clamp-2">{{ $notice['content'] }}</div>
                                    @if(!empty($notice['event_time']))
                                        <div class="text-xs text-blue-600 dark:text-blue-400 mt-2">
                                            <flux:icon icon="clock" class="inline w-3 h-3" /> {{ $notice['event_time'] }}
                                        </div>
                                    @endif
                                    <div class="mt-2">
                                        <flux:badge size="sm" color="neutral" variant="outline">{{ ucfirst(strtolower($notice['type'] ?? 'Aviso')) }}</flux:badge>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Citations -->
                    @if(count($dayCitations) > 0)
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <flux:icon icon="calendar-days" class="text-amber-600" />
                                <flux:heading size="sm">Citatorios</flux:heading>
                                <flux:badge size="sm" color="amber">{{ count($dayCitations) }}</flux:badge>
                            </div>
                            @foreach($dayCitations as $citation)
                                <div class="p-4 rounded-xl border border-amber-200 dark:border-amber-900/50 bg-amber-50 dark:bg-amber-900/20">
                                    <div class="font-bold text-amber-900 dark:text-amber-100">{{ $citation['student']['name'] ?? 'N/A' }}</div>
                                    <div class="text-sm text-amber-700 dark:text-amber-300 mt-1">{{ $citation['reason'] }}</div>
                                    <div class="text-xs text-amber-600 dark:text-amber-400 mt-2">
                                        <flux:icon icon="clock" class="inline w-3 h-3" /> 
                                        {{ \Carbon\Carbon::parse($citation['citation_date'])->format('H:i') }} hrs
                                    </div>
                                    <div class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                                        Solicitado por: {{ $citation['teacher']['name'] ?? 'N/A' }}
                                    </div>
                                    <div class="mt-2">
                                        @if($citation['status'] === 'PENDING')
                                            <flux:badge size="sm" color="amber">Agendado</flux:badge>
                                        @elseif($citation['status'] === 'ATTENDED')
                                            <flux:badge size="sm" color="green">Asistió</flux:badge>
                                        @else
                                            <flux:badge size="sm" color="red">No asistió</flux:badge>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Exams -->
                    @if(count($dayExams) > 0)
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <flux:icon icon="academic-cap" class="text-purple-600" />
                                <flux:heading size="sm">Exámenes</flux:heading>
                                <flux:badge size="sm" color="purple">{{ count($dayExams) }}</flux:badge>
                            </div>
                            @foreach($dayExams as $exam)
                                <div class="p-4 rounded-xl border border-purple-200 dark:border-purple-900/50 bg-purple-50 dark:bg-purple-900/20">
                                    <div class="font-bold text-purple-900 dark:text-purple-100">{{ $exam['subject'] }}</div>
                                    <div class="text-sm text-purple-700 dark:text-purple-300 mt-1">
                                        <span class="font-medium">Grado:</span> {{ $exam['grade'] }} {{ $exam['group_name'] }}
                                    </div>
                                    <div class="text-xs text-purple-600 dark:text-purple-400 mt-1">
                                        <span class="font-medium">Periodo:</span> {{ $exam['period'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Reports -->
                    @if(count($dayReports) > 0)
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <flux:icon icon="document-text" class="text-red-600" />
                                <flux:heading size="sm">Reportes</flux:heading>
                                <flux:badge size="sm" color="red">{{ count($dayReports) }}</flux:badge>
                            </div>
                            @foreach($dayReports as $report)
                                <div class="p-4 rounded-xl border border-red-200 dark:border-red-900/50 bg-red-50 dark:bg-red-900/20">
                                    <div class="font-bold text-red-900 dark:text-red-100">{{ $report['student']['name'] ?? 'N/A' }}</div>
                                    <div class="text-sm text-red-700 dark:text-red-300 mt-1">
                                        <span class="font-medium">Infracción:</span> {{ $report['infraction']['name'] ?? 'N/A' }}
                                    </div>
                                    @if(!empty($report['subject']))
                                        <div class="text-xs text-red-600 dark:text-red-400 mt-1">
                                            <span class="font-medium">Materia:</span> {{ $report['subject'] }}
                                        </div>
                                    @endif
                                    <div class="text-xs text-red-600 dark:text-red-400 mt-1 line-clamp-2 italic">{{ $report['description'] }}</div>
                                    <div class="text-xs text-red-500 dark:text-red-400 mt-2">
                                        Reportado por: {{ $report['teacher']['name'] ?? 'N/A' }}
                                    </div>
                                    <div class="mt-2">
                                        @if($report['status'] === 'PENDING_SIGNATURE')
                                            <flux:badge size="sm" color="amber">Pendiente firma</flux:badge>
                                        @elseif($report['status'] === 'SIGNED')
                                            <flux:badge size="sm" color="green">Firmado</flux:badge>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>