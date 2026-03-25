<?php

use App\Models\Attendance;
use App\Models\ClassGroup;
use App\Models\Cycle;
use App\Models\Student;
use Livewire\Volt\Component;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public ?string $cycle_id = null;
    public ?string $grade = null;
    public ?string $group_id = null;
    public string $start_date = '';
    public string $end_date = '';

    public function mount(): void
    {
        $this->authorize('teacher-or-admin');
        
        $activeCycle = Cycle::where('is_active', true)->first();
        if ($activeCycle) {
            $this->cycle_id = $activeCycle->id;
        }

        $this->start_date = Carbon::now()->startOfMonth()->toDateString();
        $this->end_date = Carbon::now()->toDateString();
    }

    public function getStatsProperty(): array
    {
        if (!$this->group_id || !$this->start_date || !$this->end_date) {
            return [];
        }

        $group = ClassGroup::find($this->group_id);
        if (!$group) return [];

        $studentIds = $group->students()
            ->where('student_cycle_association.cycle_id', $this->cycle_id)
            ->pluck('students.id');

        $attendances = Attendance::whereIn('student_id', $studentIds)
            ->whereBetween('date', [$this->start_date, $this->end_date])
            ->get();

        $stats = [
            'total_absences' => $attendances->where('status', 'FALTA')->count(),
            'justified' => $attendances->where('status', 'JUSTIFICADO')->count(),
            'delays' => $attendances->where('status', 'RETARDO')->count(),
            'work_at_home' => $attendances->where('status', 'TRABAJO_EN_CASA')->count(),
        ];

        $stats['total_unjustified'] = $stats['total_absences']; // In this system FALTA = Unjustified

        return $stats;
    }

    public function getStudentStatsProperty(): Collection
    {
        if (!$this->group_id || !$this->start_date || !$this->end_date) {
            return collect();
        }

        $group = ClassGroup::find($this->group_id);
        if (!$group) return collect();

        $students = $group->students()
            ->where('student_cycle_association.cycle_id', $this->cycle_id)
            ->orderBy('name')
            ->get();

        $studentIds = $students->pluck('id');

        $attendanceData = Attendance::whereIn('student_id', $studentIds)
            ->whereBetween('date', [$this->start_date, $this->end_date])
            ->select('student_id', 'status', DB::raw('count(*) as count'))
            ->groupBy('student_id', 'status')
            ->get()
            ->groupBy('student_id');

        return $students->map(function ($student) use ($attendanceData) {
            $data = $attendanceData->get($student->id) ?? collect();
            
            return (object) [
                'id' => $student->id,
                'name' => $student->name,
                'curp' => $student->curp,
                'faltas' => $data->where('status', 'FALTA')->first()?->count ?? 0,
                'justificadas' => $data->where('status', 'JUSTIFICADO')->first()?->count ?? 0,
                'retardos' => $data->where('status', 'RETARDO')->first()?->count ?? 0,
                'trabajo_casa' => $data->where('status', 'TRABAJO_EN_CASA')->first()?->count ?? 0,
            ];
        });
    }

    public function with(): array
    {
        return [
            'cycles' => Cycle::orderBy('start_date', 'desc')->get(),
            'groups' => $this->grade ? ClassGroup::where('cycle_id', $this->cycle_id)->where('grade', $this->grade)->get() : collect(),
        ];
    }
}; ?>

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl">Estadísticas por Grupo</flux:heading>
            <flux:subheading>Resumen de asistencia en un rango de tiempo</flux:subheading>
        </div>
        <flux:button icon="arrow-left" variant="ghost" :href="route('attendance.index')">Volver</flux:button>
    </div>

    {{-- Filters --}}
    <div class="p-4 rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900 shadow-sm transition-all">
        <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            <flux:select label="Ciclo" wire:model.live="cycle_id" size="sm">
                @foreach($cycles as $cycle)
                    <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
                @endforeach
            </flux:select>

            <flux:select label="Grado" wire:model.live="grade" size="sm">
                <option value="">Seleccionar...</option>
                <option value="1º">1º Secundaria</option>
                <option value="2º">2º Secundaria</option>
                <option value="3º">3º Secundaria</option>
            </flux:select>

            <flux:select label="Grupo" wire:model.live="group_id" :disabled="!$grade" size="sm">
                <option value="">Seleccionar...</option>
                @foreach($groups as $group)
                    <option value="{{ $group->id }}">{{ $group->section }}</option>
                @endforeach
            </flux:select>

            <flux:input type="date" label="Desde" wire:model.live="start_date" size="sm" />
            <flux:input type="date" label="Hasta" wire:model.live="end_date" size="sm" />
        </div>
    </div>

    @if($this->group_id)
        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            {{-- Faltas Injustificadas --}}
            <div class="bg-red-500/10 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-2xl p-4 flex flex-col gap-1">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-6 h-6 bg-red-500 rounded flex items-center justify-center">
                        <flux:icon.x-mark class="w-3.5 h-3.5 text-white" />
                    </div>
                    <span class="text-xs text-red-700 dark:text-red-400 font-medium whitespace-nowrap">Faltas Injust.</span>
                </div>
                <p class="text-3xl font-bold text-red-600 dark:text-red-500">{{ $this->stats['total_absences'] }}</p>
            </div>

            {{-- Trabajo en Casa --}}
            <div class="bg-blue-500/10 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 rounded-2xl p-4 flex flex-col gap-1">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-6 h-6 bg-blue-600 rounded flex items-center justify-center">
                        <flux:icon.home class="w-3.5 h-3.5 text-white" />
                    </div>
                    <span class="text-xs text-blue-700 dark:text-blue-400 font-medium whitespace-nowrap">Trabajo en Casa</span>
                </div>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-500">{{ $this->stats['work_at_home'] }}</p>
            </div>

            {{-- Faltas Justificadas --}}
            <div class="bg-sky-500/10 dark:bg-sky-950/30 border border-sky-200 dark:border-sky-800 rounded-2xl p-4 flex flex-col gap-1">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-6 h-6 bg-sky-500 rounded flex items-center justify-center shadow-sm">
                        <flux:icon.document-text class="w-3.5 h-3.5 text-white" />
                    </div>
                    <span class="text-xs text-sky-800 dark:text-sky-300 font-bold whitespace-nowrap">Faltas Justif.</span>
                </div>
                <p class="text-3xl font-bold text-sky-600 dark:text-sky-400">{{ $this->stats['justified'] }}</p>
            </div>

            {{-- Retardos --}}
            <div class="bg-amber-500/10 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-2xl p-4 flex flex-col gap-1">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-6 h-6 bg-amber-500 rounded flex items-center justify-center">
                        <flux:icon.clock class="w-3.5 h-3.5 text-white" />
                    </div>
                    <span class="text-xs text-amber-700 dark:text-amber-400 font-medium whitespace-nowrap">Retardos</span>
                </div>
                <p class="text-3xl font-bold text-amber-600 dark:text-amber-500">{{ $this->stats['delays'] }}</p>
            </div>
        </div>

        {{-- Students Table --}}
        <div class="rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900/60 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800 text-[10px] uppercase tracking-widest text-zinc-500 dark:text-zinc-400 font-bold">
                            <th class="py-4 px-6">Alumno</th>
                            <th class="py-4 px-4 text-center">
                                <span class="hidden sm:inline">Injustificadas</span>
                                <span class="inline sm:hidden">F</span>
                            </th>
                            <th class="py-4 px-4 text-center">
                                <span class="hidden sm:inline">Justificadas</span>
                                <span class="inline sm:hidden">J</span>
                            </th>
                            <th class="py-4 px-4 text-center">
                                <span class="hidden sm:inline">Retardos</span>
                                <span class="inline sm:hidden">R</span>
                            </th>
                            <th class="py-4 px-4 text-center">
                                <span class="hidden sm:inline">Trabajo Casa</span>
                                <span class="inline sm:hidden">TC</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-50 dark:divide-zinc-800/50">
                        @foreach($this->studentStats as $student)
                            <tr wire:key="student-stat-{{ $student->id }}" class="hover:bg-zinc-50/50 dark:hover:bg-white/5 transition-colors">
                                <td class="py-4 px-6">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-zinc-900 dark:text-white uppercase">{{ $student->name }}</span>
                                        <span class="text-[10px] text-zinc-400 font-mono leading-none">{{ $student->curp }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <span class="inline-flex items-center justify-center min-w-8 px-2 py-1 rounded-lg {{ $student->faltas > 0 ? 'bg-red-500 text-white font-bold' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-400' }}">
                                        {{ $student->faltas }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <span class="inline-flex items-center justify-center min-w-8 px-2 py-1 rounded-lg {{ $student->justificadas > 0 ? 'bg-sky-500 text-white font-bold' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-400' }}">
                                        {{ $student->justificadas }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <span class="inline-flex items-center justify-center min-w-8 px-2 py-1 rounded-lg {{ $student->retardos > 0 ? 'bg-amber-500 text-white font-bold' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-400' }}">
                                        {{ $student->retardos }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <span class="inline-flex items-center justify-center min-w-8 px-2 py-1 rounded-lg {{ $student->trabajo_casa > 0 ? 'bg-blue-600 text-white font-bold' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-400' }}">
                                        {{ $student->trabajo_casa }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="py-24 text-center rounded-3xl border-2 border-dashed border-zinc-200 dark:border-zinc-800 flex flex-col items-center justify-center space-y-4">
            <div class="w-20 h-20 bg-zinc-50 dark:bg-zinc-800/50 rounded-full flex items-center justify-center mb-2">
                <flux:icon.chart-bar class="w-10 h-10 text-zinc-300 dark:text-zinc-700" />
            </div>
            <flux:heading size="lg">Resumen de Estadísticas</flux:heading>
            <flux:subheading class="max-w-xs mx-auto text-balance">Selecciona un grado y grupo para generar el reporte de asistencia.</flux:subheading>
        </div>
    @endif
</div>
