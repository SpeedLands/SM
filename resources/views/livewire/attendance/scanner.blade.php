<?php

use App\Models\Student;
use App\Models\Attendance;
use App\Models\Setting;
use Livewire\Volt\Component;
use Illuminate\Support\Carbon;

new class extends Component {
    public string $curp = '';
    public ?Student $lastStudent = null;
    public string $statusMessage = '';
    public string $statusColor = 'zinc';
    public string $lastEntryTime = '';
    public string $lastStatus = '';
    public array $recentScans = [];

    public function mount(): void
    {
        $this->authorize('teacher-or-admin');
    }

    public function processScan(): void
    {
        $this->curp = trim(strtoupper($this->curp));

        if (empty($this->curp)) {
            return;
        }

        $student = Student::where('curp', $this->curp)->first();

        if (!$student) {
            $this->statusMessage = "CURP No encontrado: $this->curp";
            $this->statusColor = 'red';
            $this->lastStatus = 'error';
            $this->lastStudent = null;
            $this->curp = '';
            $this->dispatch('play-sound', ['type' => 'error']);
            return;
        }

        $today = Carbon::today()->toDateString(); // Force Y-m-d string for SQLite consistency
        $now = Carbon::now();
        $status = 'PRESENTE';
        $graceMinutes = (int) Setting::get('attendance.grace_minutes', 10);

        if ($student->turn === 'MATUTINO') {
            $entryTime = Setting::get('attendance.matutino_entry_time', '07:30');
            $threshold = Carbon::createFromFormat('H:i', $entryTime)->addMinutes($graceMinutes);
            if ($now->greaterThan($threshold)) {
                $status = 'RETARDO';
            }
        } elseif ($student->turn === 'VESPERTINO') {
            $entryTime = Setting::get('attendance.vespertino_entry_time', '13:30');
            $threshold = Carbon::createFromFormat('H:i', $entryTime)->addMinutes($graceMinutes);
            if ($now->greaterThan($threshold)) {
                $status = 'RETARDO';
            }
        }

        try {
            $attendance = Attendance::updateOrCreate(
                ['student_id' => $student->id, 'date' => $today],
                [
                    'entry_time' => $now->format('H:i:s'),
                    'status' => $status,
                ]
            );

            // If it was an update and not a create, it means it's a "duplicate" scan in terms of logic
            if (!$attendance->wasRecentlyCreated && $attendance->status !== $status) {
                // We updated the status, but for the scanner UX we might want to still show it as success or duplicate
                $this->statusMessage = "Ya se registró asistencia hoy para: $student->name";
                $this->statusColor = 'amber';
                $this->lastStatus = 'duplicate';
                $this->lastStudent = $student;
                $this->lastEntryTime = $attendance->entry_time->format('H:i:s');
                $this->curp = '';
                $this->dispatch('play-sound', ['type' => 'warning']);
                return;
            }
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Race condition handled: someone else created it, just show as duplicate
            $existing = Attendance::where('student_id', $student->id)->where('date', $today)->first();
            $this->statusMessage = "Ya se registró asistencia hoy para: $student->name";
            $this->statusColor = 'amber';
            $this->lastStatus = 'duplicate';
            $this->lastStudent = $student;
            $this->lastEntryTime = $existing ? $existing->entry_time->format('H:i:s') : $now->format('H:i:s');
            $this->curp = '';
            $this->dispatch('play-sound', ['type' => 'warning']);
            return;
        }

        $this->lastStudent = $student;
        $this->lastStatus = $status === 'RETARDO' ? 'retardo' : 'success';
        
        $this->statusMessage = $status === 'RETARDO' ? "RETARDO Registrado" : "ASISTENCIA Registrada";
        $this->statusColor = $status === 'RETARDO' ? 'amber' : 'green';
        $this->lastEntryTime = $now->format('H:i:s');
        $this->curp = '';

        // Add to recent scans (keep last 5)
        array_unshift($this->recentScans, [
            'name' => $student->name,
            'time' => $now->format('H:i'),
            'status' => $status,
            'color' => $this->statusColor,
        ]);
        $this->recentScans = array_slice($this->recentScans, 0, 5);

        $this->dispatch('play-sound', ['type' => 'success']);
    }

}; ?>

<div class="max-w-2xl mx-auto py-8 px-4 space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-3">
        <flux:button :href="route('attendance.index')" icon="arrow-left" variant="subtle" size="sm" title="Volver a asistencia" />
        <div>
            <flux:heading size="xl">Escáner de Asistencia</flux:heading>
            <flux:subheading>Pasa el QR o código de barras por el lector</flux:subheading>
        </div>
    </div>

    {{-- Scan Area --}}
    <div class="relative">
        {{-- Hidden autofocus input --}}
        <input
            autofocus
            id="scanner-input"
            type="text"
            wire:model="curp"
            wire:keydown.enter="processScan"
            class="absolute inset-0 opacity-0 cursor-default z-10 w-full h-full"
            autocomplete="off"
        />

        @php
            $areaConfig = match($lastStatus) {
                'success'   => ['border' => 'border-green-400 dark:border-green-600', 'bg' => 'bg-green-50 dark:bg-green-950/20', 'icon' => 'check-circle', 'iconColor' => 'text-green-500'],
                'retardo'   => ['border' => 'border-amber-400 dark:border-amber-600', 'bg' => 'bg-amber-50 dark:bg-amber-950/20', 'icon' => 'clock', 'iconColor' => 'text-amber-500'],
                'duplicate' => ['border' => 'border-amber-300 dark:border-amber-700', 'bg' => 'bg-amber-50/50 dark:bg-amber-950/10', 'icon' => 'information-circle', 'iconColor' => 'text-amber-400'],
                'error'     => ['border' => 'border-red-400 dark:border-red-600', 'bg' => 'bg-red-50 dark:bg-red-950/20', 'icon' => 'x-circle', 'iconColor' => 'text-red-500'],
                default     => ['border' => 'border-dashed border-zinc-200 dark:border-zinc-700', 'bg' => 'bg-white dark:bg-zinc-900', 'icon' => 'qr-code', 'iconColor' => 'text-zinc-300 dark:text-zinc-600'],
            };
        @endphp

        <div class="flex flex-col items-center justify-center p-12 {{ $areaConfig['bg'] }} {{ $areaConfig['border'] }} border-4 rounded-3xl transition-all duration-300 {{ !$lastStatus ? 'animate-pulse' : '' }}">
            <flux:icon
                :name="$areaConfig['icon']"
                class="w-20 h-20 mb-4 {{ $areaConfig['iconColor'] }} transition-all duration-300"
            />
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                {{ $lastStatus ? 'Listo para siguiente lectura' : 'Esperando lectura...' }}
            </p>
        </div>
    </div>

    {{-- Feedback Card --}}
    @if($lastStudent || ($statusMessage && $statusColor === 'red'))
        <div class="bg-{{ $statusColor }}-50 dark:bg-{{ $statusColor }}-950/20 border border-{{ $statusColor }}-200 dark:border-{{ $statusColor }}-800 rounded-2xl p-5">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-{{ $statusColor }}-100 dark:bg-{{ $statusColor }}-900 rounded-xl flex items-center justify-center shrink-0">
                    @if($statusColor === 'red')
                        <flux:icon.x-circle class="w-7 h-7 text-{{ $statusColor }}-600 dark:text-{{ $statusColor }}-400" />
                    @elseif($statusColor === 'amber')
                        <flux:icon.clock class="w-7 h-7 text-{{ $statusColor }}-600 dark:text-{{ $statusColor }}-400" />
                    @else
                        <flux:icon.check-circle class="w-7 h-7 text-{{ $statusColor }}-600 dark:text-{{ $statusColor }}-400" />
                    @endif
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <flux:badge color="{{ $statusColor }}" variant="solid" size="sm" class="uppercase font-bold">
                            {{ $statusMessage }}
                        </flux:badge>
                        @if($lastEntryTime)
                            <span class="font-mono text-lg font-bold text-{{ $statusColor }}-700 dark:text-{{ $statusColor }}-300">
                                {{ $lastEntryTime }}
                            </span>
                        @endif
                    </div>

                    @if($lastStudent)
                        <p class="font-bold text-zinc-900 dark:text-white truncate uppercase">{{ $lastStudent->name }}</p>
                        <div class="flex gap-2 mt-1">
                            <flux:badge variant="subtle" size="sm">{{ $lastStudent->grade }} {{ $lastStudent->group_name }}</flux:badge>
                            <flux:badge variant="subtle" size="sm">{{ $lastStudent->turn }}</flux:badge>
                        </div>
                    @else
                        <p class="text-sm text-red-600 dark:text-red-400">CURP no encontrado en el sistema</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Recent Scans --}}
    @if(count($recentScans) > 0)
        <div>
            <flux:text variant="subtle" class="text-xs font-medium mb-2 uppercase tracking-wide">Últimos registros</flux:text>
            <div class="space-y-1.5">
                @foreach($recentScans as $scan)
                    <div class="flex items-center justify-between px-4 py-2 rounded-xl bg-zinc-50 dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 rounded-full bg-{{ $scan['color'] }}-500"></div>
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300 truncate max-w-48">{{ $scan['name'] }}</span>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <flux:badge color="{{ $scan['color'] }}" size="sm" variant="subtle">{{ $scan['status'] }}</flux:badge>
                            <span class="font-mono text-xs text-zinc-400">{{ $scan['time'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Re-focus button --}}
    <div class="text-center">
        <flux:button variant="subtle" size="sm" icon="cursor-arrow-rays"
            title="Enfocar cursor para escanear"
            x-on:click="document.getElementById('scanner-input').focus()">
            Re-enfocar escáner
        </flux:button>
    </div>

    {{-- Script --}}
    <script>
        document.addEventListener('livewire:initialized', () => {
            const input = document.getElementById('scanner-input');

            document.addEventListener('click', () => input?.focus());
            document.addEventListener('keydown', (e) => {
                if (e.target !== input) input?.focus();
            });

            Livewire.on('play-sound', (params) => {
                const type = params[0].type;
                const sequences = {
                    'success': [[660, 0.15], [880, 0.15]],
                    'error':   [[440, 0.2], [220, 0.3]],
                    'warning': [[520, 0.15], [520, 0.15]],
                };

                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                sequences[type].forEach(([freq, dur], i) => {
                    setTimeout(() => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(freq, ctx.currentTime);
                        gain.gain.setValueAtTime(0.12, ctx.currentTime);
                        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + dur);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start();
                        osc.stop(ctx.currentTime + dur);
                    }, i * 200);
                });
            });
        });
    </script>
</div>
