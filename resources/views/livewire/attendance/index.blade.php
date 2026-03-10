<?php

use App\Models\Student;
use App\Models\Attendance;
use App\Models\ClassGroup;
use App\Models\Cycle;
use Livewire\Volt\Component;

new class extends Component {
    public string $date = '';
    public ?string $cycle_id = null;
    public ?string $grade = null;
    public ?string $group_id = null;

    public function mount(): void
    {
        $this->authorize('teacher-or-admin');
        $this->date = date('Y-m-d');

        $activeCycle = Cycle::where('is_active', true)->first();
        if ($activeCycle) {
            $this->cycle_id = $activeCycle->id;
        }
    }

    public function getCyclesProperty()
    {
        return Cycle::orderBy('start_date', 'desc')->get();
    }

    public function getGroupsProperty()
    {
        if (!$this->cycle_id || !$this->grade) {
            return collect();
        }

        return ClassGroup::where('cycle_id', $this->cycle_id)
            ->where('grade', $this->grade)
            ->get();
    }

    public function setStatus(string $studentId, string $status): void
    {
        try {
            Attendance::updateOrCreate(
                ['student_id' => $studentId, 'date' => Carbon\Carbon::parse($this->date)->toDateString()],
                [
                    'status' => $status,
                    'entry_time' => in_array($status, ['PRESENTE', 'RETARDO']) ? now()->format('H:i') : null,
                ]
            );

            $this->dispatch('notify', ['message' => 'Asistencia actualizada', 'variant' => 'success']);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Already exists, just update it if needed or ignore
            Attendance::where([
                'student_id' => $studentId, 
                'date' => Carbon\Carbon::parse($this->date)->toDateString()
            ])->update([
                'status' => $status,
                'entry_time' => in_array($status, ['PRESENTE', 'RETARDO']) ? now()->format('H:i') : null,
            ]);
            $this->dispatch('notify', ['message' => 'Asistencia actualizada', 'variant' => 'success']);
        }
    }

    public function markAllPresent(): void
    {
        foreach ($this->students() as $student) {
            try {
                Attendance::updateOrCreate(
                    ['student_id' => $student->id, 'date' => Carbon\Carbon::parse($this->date)->toDateString()],
                    ['status' => 'PRESENTE', 'entry_time' => now()->format('H:i')]
                );
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                // Ignore or update silently
                Attendance::where([
                    'student_id' => $student->id, 
                    'date' => Carbon\Carbon::parse($this->date)->toDateString()
                ])->update(['status' => 'PRESENTE', 'entry_time' => now()->format('H:i')]);
            }
        }

        $this->dispatch('notify', ['message' => 'Todos marcados como presentes', 'variant' => 'success']);
    }

    public function students()
    {
        if (!$this->cycle_id || !$this->grade || empty($this->group_id)) {
            return collect();
        }

        return Student::query()
            ->join('student_cycle_association', 'students.id', '=', 'student_cycle_association.student_id')
            ->where('student_cycle_association.cycle_id', $this->cycle_id)
            ->where('student_cycle_association.class_group_id', (string) $this->group_id)
            ->with(['attendances' => fn($q) => $q->where('date', $this->date)])
            ->select('students.*')
            ->distinct()
            ->orderBy('students.name')
            ->get();
    }

    public function updated($property): void
    {
        if (in_array($property, ['cycle_id', 'grade'])) {
            $this->group_id = null;
        }
    }
}; ?>

<div class="space-y-6" x-data="{ showFilters: false }">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl">Asistencia</flux:heading>
            <flux:subheading>Control diario de asistencia por grupo</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            @if($group_id)
                <flux:button wire:click="markAllPresent" icon="check-circle" variant="filled" size="sm"
                    wire:confirm="¿Marcar a todos como presentes?"
                    wire:loading.attr="disabled"
                    title="Marcar todos como presentes"
                    class="hidden sm:inline-flex">
                    <span wire:loading.remove wire:target="markAllPresent">Todos presentes</span>
                    <span wire:loading wire:target="markAllPresent">Guardando...</span>
                </flux:button>
            @endif
            <flux:button :href="route('attendance.scanner')" icon="qr-code" variant="primary" size="sm" title="Escanear código QR" class="w-full sm:w-auto">
                Escáner
            </flux:button>
        </div>
    </div>

    {{-- Filtros Rápidos (Pills for mobile style) --}}
    <div class="flex flex-wrap gap-2 sm:hidden pb-2 overflow-x-auto no-scrollbar">
        <flux:badge variant="solid" color="zinc" class="shrink-0">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</flux:badge>
        @if($grade) <flux:badge variant="solid" color="zinc" class="shrink-0">{{ $grade }} Secundaria</flux:badge> @endif
        @if($group_id)
            @php $g = $this->groups->firstWhere('id', $group_id); @endphp
            @if($g) <flux:badge variant="solid" color="zinc" class="shrink-0">Sección {{ $g->section }}</flux:badge> @endif
        @endif
        <flux:button variant="ghost" size="xs" icon="funnel" class="ml-auto" title="Mostrar/ocultar filtros" x-on:click="showFilters = !showFilters" />
    </div>

    {{-- Filtros Desk / Mobile Panel --}}
    <div x-show="showFilters" class="sm:block! p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm transition-all mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:field>
                <flux:label>Fecha</flux:label>
                <flux:input type="date" wire:model.live="date" />
            </flux:field>

            <flux:field>
                <flux:label>Ciclo</flux:label>
                <flux:select wire:model.live="cycle_id">
                    @foreach($this->cycles as $cycle)
                        <option value="{{ $cycle->id }}">{{ $cycle->name }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Grado</flux:label>
                <flux:select wire:model.live="grade">
                    <option value="">Selecciona Grado</option>
                    <option value="1º">1º Secundaria</option>
                    <option value="2º">2º Secundaria</option>
                    <option value="3º">3º Secundaria</option>
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>Grupo / Sección</flux:label>
                <flux:select wire:model.live="group_id">
                    <option value="">Selecciona Grupo</option>
                    @foreach($this->groups as $group)
                        <option value="{{ $group->id }}">Sección {{ $group->section }}</option>
                    @endforeach
                </flux:select>
            </flux:field>
        </div>
    </div>

    @if($group_id)
        @php
            $studentsList = $this->students();
            $total       = $studentsList->count();
            $presentes   = $studentsList->filter(fn($s) => $s->attendances->first()?->status === 'PRESENTE')->count();
            $faltas      = $studentsList->filter(fn($s) => $s->attendances->first()?->status === 'FALTA')->count();
            $retardos    = $studentsList->filter(fn($s) => $s->attendances->first()?->status === 'RETARDO')->count();
            $pendientes  = $studentsList->filter(fn($s) => !$s->attendances->first())->count();
        @endphp

        {{-- Stats (Cards horizontal scroll on mobile) --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-green-500/10 dark:bg-green-950/30 border border-green-200 dark:border-green-800 rounded-2xl p-4 flex flex-col gap-1 transition-all">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-6 h-6 bg-green-500 rounded flex items-center justify-center">
                        <flux:icon.check class="w-3.5 h-3.5 text-white" />
                    </div>
                    <span class="text-xs text-green-700 dark:text-green-400 font-medium">Presentes</span>
                </div>
                <p class="text-3xl font-bold text-green-600 dark:text-green-500">{{ $presentes }}</p>
            </div>
            <div class="bg-red-500/10 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-2xl p-4 flex flex-col gap-1">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-6 h-6 bg-red-500 rounded flex items-center justify-center">
                        <flux:icon.x-mark class="w-3.5 h-3.5 text-white" />
                    </div>
                    <span class="text-xs text-red-700 dark:text-red-400 font-medium">Faltas</span>
                </div>
                <p class="text-3xl font-bold text-red-600 dark:text-red-500">{{ $faltas }}</p>
            </div>
            <div class="bg-amber-500/10 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-2xl p-4 flex flex-col gap-1">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-6 h-6 bg-amber-500 rounded flex items-center justify-center">
                        <flux:icon.clock class="w-3.5 h-3.5 text-white" />
                    </div>
                    <span class="text-xs text-amber-700 dark:text-amber-400 font-medium">Retardos</span>
                </div>
                <p class="text-3xl font-bold text-amber-600 dark:text-amber-500">{{ $retardos }}</p>
            </div>
            <div class="bg-zinc-500/10 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-4 flex flex-col gap-1">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-6 h-6 bg-zinc-400 rounded flex items-center justify-center">
                        <flux:icon.ellipsis-horizontal class="w-3.5 h-3.5 text-white" />
                    </div>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">Pendientes</span>
                </div>
                <p class="text-3xl font-bold text-zinc-600 dark:text-zinc-300">{{ $pendientes }}</p>
            </div>
        </div>

        {{-- Mobile: Card Layout --}}
        <div class="sm:hidden space-y-4">
            @foreach($studentsList as $i => $student)
                @php $attendance = $student->attendances->first(); @endphp
                <div wire:key="mobile-student-{{ $student->id }}" class="p-4 rounded-3xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900/60 shadow-sm space-y-4">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center font-bold text-zinc-500 text-sm">
                                {{ $i + 1 }}
                            </div>
                            <div>
                                <h3 class="font-bold text-zinc-900 dark:text-white uppercase leading-tight">{{ $student->name }}</h3>
                                @if($attendance)
                                    <flux:badge size="xs" inset="top" 
                                        color="{{ match($attendance->status) {
                                            'PRESENTE'        => 'green',
                                            'FALTA'           => 'red',
                                            'RETARDO'         => 'amber',
                                            'JUSTIFICADO'     => 'sky',
                                            'TRABAJO_EN_CASA' => 'blue',
                                            default           => 'zinc'
                                        } }}">
                                        {{ $attendance->status }}
                                    </flux:badge>
                                @endif
                            </div>
                        </div>
                        <flux:dropdown>
                            <flux:button variant="ghost" icon="ellipsis-vertical" size="sm" />
                            <flux:navmenu>
                                <flux:navmenu.item icon="clock">Ver Historial</flux:navmenu.item>
                            </flux:navmenu>
                        </flux:dropdown>
                    </div>

                    <div class="flex justify-between items-center bg-zinc-50 dark:bg-zinc-800/20 p-2 rounded-2xl gap-1">
                        {{-- Presente (Verde) --}}
                        <button wire:click="setStatus('{{ $student->id }}', 'PRESENTE')"
                            title="Presente"
                            class="flex-1 h-12 rounded-xl flex items-center justify-center transition-all
                            {{ $attendance?->status === 'PRESENTE' ? 'bg-green-500 text-white shadow-lg shadow-green-500/30' : 'text-zinc-400 dark:text-zinc-600 hover:bg-zinc-200' }}">
                            <flux:icon.check variant="solid" class="w-5 h-5" />
                        </button>
                        
                        {{-- Falta (Rojo) --}}
                        <button wire:click="setStatus('{{ $student->id }}', 'FALTA')"
                            title="Ausente"
                            class="flex-1 h-12 rounded-xl flex items-center justify-center transition-all
                            {{ $attendance?->status === 'FALTA' ? 'bg-red-500 text-white shadow-lg shadow-red-500/30' : 'text-zinc-400 dark:text-zinc-600 hover:bg-zinc-200' }}">
                            <flux:icon.x-mark variant="solid" class="w-5 h-5" />
                        </button>

                        {{-- Retardo (Ámbar) --}}
                        <button wire:click="setStatus('{{ $student->id }}', 'RETARDO')"
                            title="Retardo"
                            class="flex-1 h-12 rounded-xl flex items-center justify-center transition-all
                            {{ $attendance?->status === 'RETARDO' ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/30' : 'text-zinc-400 dark:text-zinc-600 hover:bg-zinc-200' }}">
                            <flux:icon.clock variant="solid" class="w-5 h-5" />
                        </button>

                        {{-- Justificado (Azul Bajito) --}}
                        <button wire:click="setStatus('{{ $student->id }}', 'JUSTIFICADO')"
                            title="Justificado"
                            class="flex-1 h-12 rounded-xl flex items-center justify-center transition-all
                            {{ $attendance?->status === 'JUSTIFICADO' ? 'bg-sky-400 text-white shadow-lg shadow-sky-400/30' : 'text-zinc-400 dark:text-zinc-600 hover:bg-zinc-200' }}">
                            <flux:icon.document-text variant="solid" class="w-5 h-5" />
                        </button>

                        {{-- Trabajo en Casa (Azul Fuerte) --}}
                        <button wire:click="setStatus('{{ $student->id }}', 'TRABAJO_EN_CASA')"
                            title="Permiso / Trabajo en casa"
                            class="flex-1 h-12 rounded-xl flex items-center justify-center transition-all
                            {{ $attendance?->status === 'TRABAJO_EN_CASA' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'text-zinc-400 dark:text-zinc-600 hover:bg-zinc-200' }}">
                            <flux:icon.home variant="solid" class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Desktop: Table Layout --}}
        <div class="hidden sm:block rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900/60 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800 text-[10px] uppercase tracking-widest text-zinc-500 dark:text-zinc-400 font-bold">
                            <th class="py-4 px-6 w-16">#</th>
                            <th class="py-4 px-2">Alumno</th>
                            <th class="py-4 px-4 text-center">Hora de Entrada</th>
                            <th class="py-4 px-6 text-right w-80">Asistencia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-50 dark:divide-zinc-800/50">
                        @foreach($studentsList as $i => $student)
                            @php $attendance = $student->attendances->first(); @endphp
                            <tr wire:key="desktop-student-{{ $student->id }}" class="hover:bg-zinc-50/50 dark:hover:bg-white/5 transition-colors group">
                                <td class="py-4 px-6 text-zinc-400 font-mono text-xs">{{ $i + 1 }}</td>
                                <td class="py-4 px-2">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-zinc-900 dark:text-white uppercase">{{ $student->name }}</span>
                                        <span class="text-[10px] text-zinc-400 font-mono leading-none">{{ $student->curp }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-zinc-100 dark:bg-zinc-800 text-xs font-mono
                                        {{ $attendance?->entry_time ? 'text-zinc-800 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700' : 'text-zinc-400 dark:text-zinc-600' }}">
                                        <flux:icon.clock size="xs" />
                                        {{ $attendance?->entry_time ? $attendance->entry_time->format('H:i') . ' hrs' : '--:--' }}
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center justify-end gap-1.5">
                                        {{-- Presente (Verde) --}}
                                        <button wire:click="setStatus('{{ $student->id }}', 'PRESENTE')"
                                            title="Presente"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center transition-all
                                            {{ $attendance?->status === 'PRESENTE' ? 'bg-green-500 text-white shadow-lg shadow-green-500/40 scale-110' : 'bg-transparent text-zinc-400 dark:text-zinc-600 hover:text-green-500 hover:bg-green-500/10' }}">
                                            <flux:icon.check class="w-4 h-4" />
                                        </button>
                                        
                                        {{-- Falta (Rojo) --}}
                                        <button wire:click="setStatus('{{ $student->id }}', 'FALTA')"
                                            title="Ausente"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center transition-all
                                            {{ $attendance?->status === 'FALTA' ? 'bg-red-500 text-white shadow-lg shadow-red-500/40 scale-110' : 'bg-transparent text-zinc-400 dark:text-zinc-600 hover:text-red-500 hover:bg-red-500/10' }}">
                                            <flux:icon.x-mark class="w-4 h-4" />
                                        </button>

                                        {{-- Retardo (Ámbar) --}}
                                        <button wire:click="setStatus('{{ $student->id }}', 'RETARDO')"
                                            title="Retardo"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center transition-all
                                            {{ $attendance?->status === 'RETARDO' ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/40 scale-110' : 'bg-transparent text-zinc-400 dark:text-zinc-600 hover:text-amber-500 hover:bg-amber-500/10' }}">
                                            <flux:icon.clock class="w-4 h-4" />
                                        </button>

                                        {{-- Justificado (Azul Bajito) --}}
                                        <button wire:click="setStatus('{{ $student->id }}', 'JUSTIFICADO')"
                                            title="Justificado"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center transition-all
                                            {{ $attendance?->status === 'JUSTIFICADO' ? 'bg-sky-400 text-white shadow-lg shadow-sky-400/40 scale-110' : 'bg-transparent text-zinc-400 dark:text-zinc-600 hover:text-sky-400 hover:bg-sky-400/10' }}">
                                            <flux:icon.document-text class="w-4 h-4" />
                                        </button>

                                        {{-- Trabajo en Casa (Azul Fuerte) --}}
                                        <button wire:click="setStatus('{{ $student->id }}', 'TRABAJO_EN_CASA')"
                                            title="Permiso / Trabajo en casa"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center transition-all
                                            {{ $attendance?->status === 'TRABAJO_EN_CASA' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/40 scale-110' : 'bg-transparent text-zinc-400 dark:text-zinc-600 hover:text-blue-600 hover:bg-blue-600/10' }}">
                                            <flux:icon.home class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Progress bar --}}
            @if($total > 0)
                <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-800/20 border-t border-zinc-100 dark:border-zinc-800 flex items-center gap-4">
                    <span class="text-xs font-bold text-zinc-500 uppercase tracking-tighter">{{ $total - $pendientes }} / {{ $total }} REGISTRADOS</span>
                    <div class="flex-1 h-2 bg-zinc-200 dark:bg-zinc-800 rounded-full overflow-hidden shadow-inner">
                        <div class="h-full bg-blue-500 rounded-full transition-all duration-700 ease-out shadow-sm"
                            style="width: {{ round((($total - $pendientes) / $total) * 100) }}%">
                        </div>
                    </div>
                    <span class="text-sm font-black text-blue-500">{{ round((($total - $pendientes) / $total) * 100) }}%</span>
                </div>
            @endif
        </div>

    @else
        <div class="py-24 text-center rounded-3xl border-2 border-dashed border-zinc-200 dark:border-zinc-800 flex flex-col items-center justify-center space-y-4">
            <div class="w-20 h-20 bg-zinc-50 dark:bg-zinc-800/50 rounded-full flex items-center justify-center mb-2">
                <flux:icon.user-group class="w-10 h-10 text-zinc-300 dark:text-zinc-700" />
            </div>
            <flux:heading size="lg">Panel de Asistencia</flux:heading>
            <flux:subheading class="max-w-xs mx-auto text-balance">Selecciona un grado y grupo en los filtros para comenzar a pasar lista.</flux:subheading>
            <flux:button variant="ghost" icon="funnel" x-on:click="$refs.filterPanel.classList.remove('hidden')" class="sm:hidden">Abrir Filtros</flux:button>
        </div>
    @endif
</div>
