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
    public bool $useCamera = false;

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

<div class="max-w-2xl mx-auto py-8 px-4 space-y-6" x-data="scannerComponent()">
    {{-- html5-qrcode CDN --}}
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    {{-- CURP local cache --}}
    <script src="/js/curp-cache.js"></script>
    {{-- Header --}}
    <div class="flex items-center gap-3">
        <flux:button :href="route('attendance.index')" icon="arrow-left" variant="subtle" size="sm" title="Volver a asistencia" />
        <div class="flex-1">
            <flux:heading size="xl">Escáner de Asistencia</flux:heading>
            <flux:subheading>Pasa el QR o código de barras por el lector <span x-show="!localUseCamera">o usa la cámara</span></flux:subheading>
        </div>
        <flux:button x-show="!localUseCamera" icon="camera" size="sm" x-on:click="toggleCamera()">Usar Cámara</flux:button>
        <flux:button x-show="localUseCamera" icon="computer-desktop" size="sm" x-on:click="toggleCamera()" x-cloak>Usar Lector</flux:button>
    </div>

    {{-- Cache status --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 text-xs">
            <template x-if="cacheReady">
                <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4"/></svg>
                    <span x-text="cacheCount + ' CURPs en caché'"></span>
                </span>
            </template>
            <template x-if="cacheLoading">
                <span class="text-zinc-400">Cargando caché de CURPs...</span>
            </template>
            <template x-if="!cacheReady && !cacheLoading && cacheError">
                <span class="text-amber-500">Caché no disponible — modo servidor</span>
            </template>
        </div>
        <button type="button" x-show="cacheReady" x-on:click="refreshCache()" class="text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition" title="Actualizar caché">
            ↻ Actualizar
        </button>
    </div>

    {{-- Scan Area --}}
    <div class="relative" x-show="!localUseCamera">
        {{-- Hidden autofocus input --}}
        <input
            autofocus
            id="scanner-input"
            type="text"
            wire:model="curp"
            x-on:keydown.enter.prevent="handleScan()"
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

    {{-- Camera Area --}}
    <div x-show="localUseCamera" class="space-y-4" x-cloak>
        <div class="relative max-w-sm mx-auto aspect-square overflow-hidden rounded-3xl border-4 border-zinc-200 dark:border-zinc-700 bg-black" wire:ignore>
            {{-- Loading State --}}
            <div x-show="isStarting" class="absolute inset-0 flex flex-col items-center justify-center bg-zinc-900 z-10">
                <flux:icon name="camera" class="w-12 h-12 text-zinc-700 animate-pulse mb-4" />
                <flux:text size="sm" class="text-zinc-500">Iniciando cámara...</flux:text>
            </div>

            <div id="reader" class="w-full h-full [&>video]:object-cover [&>video]:w-full [&>video]:h-full"></div>
        </div>
        
        <div class="text-center">
            <flux:text size="sm" class="text-zinc-500">Apunta la cámara al código QR o de barras</flux:text>
        </div>
    </div>

    {{-- Local cache error (no server call needed) --}}
    <div x-show="localError" x-cloak class="bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-2xl p-5">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-red-100 dark:bg-red-900 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-500 text-white uppercase">CURP No encontrado</span>
                <p class="text-sm text-red-600 dark:text-red-400 mt-1 font-mono" x-text="localError"></p>
            </div>
        </div>
    </div>

    {{-- Instant preview from cache while server processes attendance --}}
    <div x-show="previewStudent && isProcessing" x-cloak class="bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800 rounded-2xl p-5">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-7 h-7 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-zinc-900 dark:text-white truncate uppercase" x-text="previewStudent?.name"></p>
                <div class="flex gap-2 mt-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300" x-text="previewStudent?.grade + ' ' + previewStudent?.group_name"></span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300" x-text="previewStudent?.turn"></span>
                </div>
                <p class="text-sm text-blue-600 dark:text-blue-400 mt-1">Registrando asistencia...</p>
            </div>
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
    <div class="text-center" x-show="!localUseCamera">
        <flux:button variant="subtle" size="sm" icon="cursor-arrow-rays"
            title="Enfocar cursor para escanear"
            x-on:click="document.getElementById('scanner-input').focus()">
            Re-enfocar escáner
        </flux:button>
    </div>

    @script
    <script>
        Alpine.data('scannerComponent', () => ({
            localUseCamera: false,
            html5QrCode: null,
            lastScan: null,
            scanCooldown: false,
            isStarting: false,

            // CURP cache state
            cacheReady: false,
            cacheLoading: false,
            cacheCount: 0,
            cacheError: false,
            localError: null,
            previewStudent: null,
            isProcessing: false,

            async init() {
                this.setupFocus();
                await this.loadCache();
            },

            async loadCache() {
                if (typeof CurpCache === 'undefined') return;
                this.cacheLoading = true;
                try {
                    this.cacheCount = await CurpCache.init('{{ route("api.curps") }}');
                    this.cacheReady = true;
                } catch (e) {
                    console.warn('CURP cache init failed, using server mode:', e);
                    this.cacheError = true;
                }
                this.cacheLoading = false;
            },

            async refreshCache() {
                if (typeof CurpCache === 'undefined') return;
                this.cacheLoading = true;
                try {
                    this.cacheCount = await CurpCache.refresh('{{ route("api.curps") }}');
                    this.cacheReady = true;
                    this.cacheError = false;
                } catch (e) {
                    console.warn('CURP cache refresh failed:', e);
                }
                this.cacheLoading = false;
            },

            /**
             * Handle a scan from keyboard input or camera.
             * Pre-validates via local cache before hitting the server.
             */
            async handleScan(decodedText) {
                const curp = (decodedText || $wire.curp || '').trim().toUpperCase();
                if (!curp) return;
                if (this.scanCooldown) return;

                this.scanCooldown = true;
                this.localError = null;
                this.previewStudent = null;
                this.isProcessing = false;

                // Pre-validate with local cache
                if (this.cacheReady) {
                    try {
                        const cached = await CurpCache.lookup(curp);
                        if (!cached) {
                            // CURP not found locally — instant error, skip server
                            this.localError = curp;
                            playLocalSound('error');
                            $wire.set('curp', '');
                            setTimeout(() => {
                                this.scanCooldown = false;
                                this.localError = null;
                            }, 3000);
                            return;
                        }
                        // Found — show instant preview
                        this.previewStudent = cached;
                        this.isProcessing = true;
                    } catch (e) {
                        // Cache error — fall back to server
                        console.warn('Cache lookup error:', e);
                    }
                }

                // Send to server for attendance recording
                $wire.set('curp', curp);
                $wire.processScan().then(() => {
                    this.previewStudent = null;
                    this.isProcessing = false;
                });

                setTimeout(() => {
                    this.scanCooldown = false;
                }, 3000);
            },

            setupFocus() {
                const input = document.getElementById('scanner-input');
                document.addEventListener('click', () => {
                    if (!this.localUseCamera) input?.focus();
                });
                document.addEventListener('keydown', (e) => {
                    if (this.localUseCamera) return;
                    if (e.target !== input) input?.focus();
                });
            },

            toggleCamera() {
                this.localUseCamera = !this.localUseCamera;
                if (this.localUseCamera) {
                    this.isStarting = true;
                    this.startCamera();
                } else {
                    this.stopCamera();
                }
            },

            async startCamera() {
                this.html5QrCode = new Html5Qrcode("reader");
                const config = { fps: 10, qrbox: { width: 250, height: 250 } };
                
                try {
                    await this.html5QrCode.start(
                        { facingMode: "environment" }, 
                        config, 
                        (decodedText) => this.onScanSuccess(decodedText)
                    );
                    this.isStarting = false;
                } catch (err) {
                    console.error("Error starting camera", err);
                    this.localUseCamera = false;
                    this.isStarting = false;
                    alert("No se pudo acceder a la cámara. Verifica los permisos.");
                }
            },

            async stopCamera() {
                if (this.html5QrCode) {
                    try {
                        await this.html5QrCode.stop();
                        this.html5QrCode = null;
                    } catch (err) {
                        console.error("Error stopping camera", err);
                    }
                }
            },

            onScanSuccess(decodedText) {
                if (this.scanCooldown) return;
                this.handleScan(decodedText);
            }
        }));

        Livewire.on('play-sound', (params) => {
            const type = params[0].type;
            const sequences = {
                'success': [[660, 0.15], [880, 0.15]],
                'error':   [[440, 0.2], [220, 0.3]],
                'warning': [[520, 0.15], [520, 0.15]],
            };

            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            if (ctx.state === 'suspended') ctx.resume();

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
    </script>
    @endscript
</div>
