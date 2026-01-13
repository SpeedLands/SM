<?php

use App\Models\Student;
use App\Models\Report;
use App\Models\Citation;
use App\Models\Notice;
use App\Models\Cycle;
use App\Models\CommunityService;
use Livewire\Volt\Component;

new class extends Component {
    public function with(): array
    {
        $user = auth()->user();
        $activeCycle = Cycle::where('is_active', true)->first();
        
        if ($user->isAdmin() || $user->isTeacher()) {
            return $this->getAdminStats($activeCycle);
        } elseif ($user->isParent()) {
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
            'totalStudents' => Student::count(),
            'totalReports' => $activeCycle ? Report::where('cycle_id', $activeCycle->id)->count() : 0,
            'activeCitations' => $activeCycle ? Citation::where('cycle_id', $activeCycle->id)->where('status', 'PENDING')->count() : 0,
            'activeNotices' => Notice::count(),
        ];

        $recentReports = Report::with('student')->latest('date')->limit(5)->get();
        $upcomingCitations = Citation::with(['student', 'teacher'])->where('status', 'PENDING')->orderBy('citation_date')->limit(5)->get();

        return array_merge($stats, [
            'recentReports' => $recentReports,
            'upcomingCitations' => $upcomingCitations,
            'activeCycle' => $activeCycle,
            'isAdmin' => true,
        ]);
    }

    protected function getParentStats(?Cycle $activeCycle): array
    {
        $user = auth()->user();
        $myStudents = $user->students()->with(['reports' => function($q) use ($activeCycle) {
            if ($activeCycle) $q->where('cycle_id', $activeCycle->id);
        }, 'communityServices'])->get();

        $studentIds = $myStudents->pluck('id')->toArray();
        
        $citations = Citation::whereIn('student_id', $studentIds)
            ->where('status', 'PENDING')
            ->orderBy('citation_date')
            ->get();

        $notices = Notice::where(function($q) {
                $q->where('target_audience', 'ALL')
                  ->orWhere('target_audience', 'PARENTS');
            })
            ->latest('date')
            ->limit(5)
            ->get();

        return [
            'myStudents' => $myStudents,
            'citations' => $citations,
            'notices' => $notices,
            'activeCycle' => $activeCycle,
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

    @if(isset($isAdmin) && $isAdmin)
        <!-- Admin/Teacher Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm flex items-center gap-4">
                <div class="size-12 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                    <flux:icon icon="user-group" variant="solid" />
                </div>
                <div>
                    <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Alumnos</div>
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
                        <div class="flex items-center gap-4 p-3 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors border border-transparent hover:border-zinc-100 dark:hover:border-zinc-800">
                            <div class="size-10 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center shrink-0">
                                <flux:icon icon="document" size="sm" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-bold truncate uppercase">{{ $report->student->name }}</div>
                                <div class="text-xs text-zinc-500 truncate">{{ $report->subject }}</div>
                            </div>
                            <div class="text-xs font-medium text-zinc-400">{{ $report->date ? $report->date->diffForHumans() : '' }}</div>
                        </div>
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
                        <div class="flex items-center gap-4 p-3 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors border border-transparent hover:border-zinc-100 dark:hover:border-zinc-800">
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
                        </div>
                    @empty
                        <div class="text-center py-8 text-zinc-500 italic text-sm">No hay citatorios pendientes.</div>
                    @endforelse
                </div>
            </div>
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
                        <div class="p-6 rounded-[2rem] bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm relative overflow-hidden group">
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
                <div class="p-6 rounded-[2rem] bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm">
                    <flux:heading size="lg" class="mb-6">Citatorios Próximos</flux:heading>
                    <div class="space-y-4">
                        @forelse($citations as $citation)
                            <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/20 flex gap-4">
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
                            </div>
                        @empty
                            <div class="text-center py-6 text-zinc-500 italic text-sm border border-dashed border-zinc-200 dark:border-zinc-800 rounded-2xl">
                                No tienes citatorios pendientes.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Recent Notices -->
                <div class="p-6 rounded-[2rem] bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm">
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
