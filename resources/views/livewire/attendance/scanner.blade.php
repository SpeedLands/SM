<?php

use App\Models\Student;
use App\Models\Attendance;
use App\Models\Setting;
use Livewire\Volt\Component;
use Illuminate\Support\Carbon;

new class extends Component {
    public string $curp = '';
    public ?array $lastStudent = null;
    public string $statusMessage = '';
    public string $statusColor = 'zinc';
    public string $lastEntryTime = '';
    public string $lastStatus = '';
    public array $recentScans = [];
    public bool $useCamera = false;
    public bool $isSyncing = false;
    public int $totalStudents = 0;
    public int $syncedStudents = 0;

    public function mount(): void
    {
        $this->authorize('teacher-or-admin');
        $this->totalStudents = Student::count();
    }

    public function getSyncData(): array
    {
        return Student::select('id', 'name', 'curp', 'grade', 'group_name', 'turn')
            ->get()
            ->toArray();
    }

    public function registerAttendance(string $studentId, ?string $time = null): void
    {
        $student = Student::find($studentId);
        if (!$student) {
            return;
        }

        $this->curp = $student->curp;
        $this->processScan($time);
    }

    public function processScan(?string $manualTime = null): void
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

        $today = Carbon::today()->toDateString();
        $now = $manualTime ? Carbon::parse($manualTime) : Carbon::now();
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

        $existing = Attendance::where('student_id', $student->id)
            ->where('date', $today)
            ->first();

        if ($existing) {
            $this->statusMessage = "Ya se registró asistencia hoy para: $student->name";
            $this->statusColor = 'amber';
            $this->lastStatus = 'duplicate';
            $this->lastStudent = $student->toArray();
            $this->lastEntryTime = $existing->entry_time->format('H:i:s');
            $this->curp = '';
            $this->dispatch('play-sound', ['type' => 'warning']);
            return;
        }

        try {
            $attendance = Attendance::create([
                'student_id' => $student->id,
                'date' => $today,
                'entry_time' => $now->format('H:i:s'),
                'status' => $status,
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Race condition: another request created it just now
            $existing = Attendance::where('student_id', $student->id)->where('date', $today)->first();
            $this->statusMessage = "Ya se registró asistencia hoy para: $student->name";
            $this->statusColor = 'amber';
            $this->lastStatus = 'duplicate';
            $this->lastStudent = $student->toArray();
            $this->lastEntryTime = $existing ? $existing->entry_time->format('H:i:s') : $now->format('H:i:s');
            $this->curp = '';
            $this->dispatch('play-sound', ['type' => 'warning']);
            return;
        }

        $this->lastStudent = $student->toArray();
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
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    {{-- CURP local cache & HID Support --}}
    <script src="/js/curp-cache.js"></script>
    <script src="/js/hid-scanner.js"></script>
    {{-- Header --}}
    <div class="flex items-center gap-3">
        <flux:button :href="route('attendance.index')" icon="arrow-left" variant="subtle" size="sm"
            title="Volver a asistencia" />
        <div class="flex-1">
            <flux:heading size="xl">Escáner de Asistencia</flux:heading>
            <flux:subheading>Pasa el QR o código de barras por el lector <span x-show="!localUseCamera">o usa la
                    cámara</span></flux:subheading>
        </div>
        <flux:button x-show="!localUseCamera" icon="camera" size="sm" x-on:click="toggleCamera()">Usar Cámara
        </flux:button>
        <flux:button x-show="localUseCamera" icon="computer-desktop" size="sm" x-on:click="toggleCamera()" x-cloak>Usar
            Lector</flux:button>
    </div>

    {{-- Cache & HID Status --}}
    <div class="flex flex-col gap-2 bg-zinc-50 dark:bg-zinc-900/50 p-3 rounded-2xl border border-zinc-100 dark:border-zinc-800">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-xs">
                <template x-if="cacheReady">
                    <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4"/></svg>
                        <span x-text="cacheCount + ' CURPs en caché'"></span>
                    </span>
                </template>
                <template x-if="cacheLoading">
                    <span class="text-zinc-400">Cargando caché...</span>
                </template>
            </div>
            <button type="button" x-on:click="refreshCache()" class="text-[10px] text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition uppercase font-bold tracking-wider">
                Actualizar Datos
            </button>
        </div>

        <div class="flex items-center justify-between border-t border-zinc-100 dark:border-zinc-800 pt-2">
            <div class="flex items-center gap-2 text-xs">
                <template x-if="hidConnected">
                    <span class="inline-flex items-center gap-1 text-blue-600 dark:text-blue-400">
                        <flux:icon name="cpu-chip" class="w-3 h-3" />
                        <span x-text="hidConnected + (hidConnected === 1 ? ' Escáner Conectado' : ' Escáneres Conectados')"></span>
                    </span>
                </template>
                <template x-if="!hidConnected">
                    <span class="text-zinc-400 flex items-center gap-1">
                        <flux:icon name="cpu-chip" class="w-3 h-3 opacity-50" />
                        <span>Escáner HID Desconectado</span>
                    </span>
                </template>
            </div>
            <button type="button" x-on:click="connectHID()" class="text-[10px] font-bold uppercase tracking-wider transition" :class="hidConnected ? 'text-blue-600 hover:text-blue-700' : 'text-blue-600 hover:text-blue-700'">
                <span x-text="hidConnected ? 'Conectar otro' : 'Conectar Escáner'"></span>
            </button>
        </div>
        
        {{-- Sync Status --}}
        <div x-show="pendingQueue.length > 0" class="flex items-center justify-between border-t border-zinc-100 dark:border-zinc-800 pt-2" x-cloak>
            <div class="flex items-center gap-2 text-xs">
                <span class="inline-flex items-center gap-1" x-bind:class="isSyncingQueue ? 'text-amber-600 animate-pulse' : 'text-zinc-400'">
                    <flux:icon name="arrow-path" class="w-3 h-3" x-bind:class="isSyncingQueue ? 'animate-spin' : ''" />
                    <span x-text="pendingQueue.length + (pendingQueue.length === 1 ? ' lectura pendiente' : ' lecturas pendientes')"></span>
                </span>
            </div>
            <div x-show="isSyncingQueue" class="text-[10px] font-bold uppercase tracking-wider text-amber-600">
                Sincronizando...
            </div>
        </div>
        
        <template x-if="hidConnected && hidDeviceNames">
            <div class="text-[10px] text-zinc-400 italic px-1 truncate" x-text="'Dispositivos: ' + hidDeviceNames"></div>
        </template>
    </div>

    {{-- Scan Area --}}
    <div class="relative" x-show="!localUseCamera">
        {{-- Hidden autofocus input --}}
        <input autofocus id="scanner-input" type="text" x-model="curpInput" x-on:keydown.enter.prevent="handleScan()"
            class="absolute inset-0 opacity-0 cursor-default z-10 w-full h-full" autocomplete="off" />

        @php
            $areaConfig = match ($lastStatus) {
                'success' => ['border' => 'border-green-400 dark:border-green-600', 'bg' => 'bg-green-50 dark:bg-green-950/20', 'icon' => 'check-circle', 'iconColor' => 'text-green-500'],
                'retardo' => ['border' => 'border-amber-400 dark:border-amber-600', 'bg' => 'bg-amber-50 dark:bg-amber-950/20', 'icon' => 'clock', 'iconColor' => 'text-amber-500'],
                'duplicate' => ['border' => 'border-amber-300 dark:border-amber-700', 'bg' => 'bg-amber-50/50 dark:bg-amber-950/10', 'icon' => 'information-circle', 'iconColor' => 'text-amber-400'],
                'error' => ['border' => 'border-red-400 dark:border-red-600', 'bg' => 'bg-red-50 dark:bg-red-950/20', 'icon' => 'x-circle', 'iconColor' => 'text-red-500'],
                default => ['border' => 'border-dashed border-zinc-200 dark:border-zinc-700', 'bg' => 'bg-white dark:bg-zinc-900', 'icon' => 'qr-code', 'iconColor' => 'text-zinc-300 dark:text-zinc-600'],
            };
        @endphp

        <div
            class="flex flex-col items-center justify-center p-12 {{ $areaConfig['bg'] }} {{ $areaConfig['border'] }} border-4 rounded-3xl transition-all duration-300 {{ !$lastStatus ? 'animate-pulse' : '' }}"
            :class="{
                'bg-green-50 dark:bg-green-950/20 border-green-400 dark:border-green-600': lastStatus === 'success',
                'bg-amber-50 dark:bg-amber-950/20 border-amber-400 dark:border-amber-600': lastStatus === 'retardo',
                'bg-amber-50/50 dark:bg-amber-950/10 border-amber-300 dark:border-amber-700': lastStatus === 'duplicate',
                'bg-red-50 dark:bg-red-950/20 border-red-400 dark:border-red-600': lastStatus === 'error',
                'bg-white dark:bg-zinc-900 border-dashed border-zinc-200 dark:border-zinc-700 animate-pulse': !lastStatus
            }"
        >
            <template x-if="lastStatus === 'success'"><flux:icon name="check-circle" class="w-20 h-20 mb-4 text-green-500" /></template>
            <template x-if="lastStatus === 'retardo'"><flux:icon name="clock" class="w-20 h-20 mb-4 text-amber-500" /></template>
            <template x-if="lastStatus === 'duplicate'"><flux:icon name="information-circle" class="w-20 h-20 mb-4 text-amber-400" /></template>
            <template x-if="lastStatus === 'error'"><flux:icon name="x-circle" class="w-20 h-20 mb-4 text-red-500" /></template>
            <template x-if="!lastStatus"><flux:icon name="qr-code" class="w-20 h-20 mb-4 text-zinc-300 dark:text-zinc-600" /></template>
            
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400" x-text="lastStatus ? 'Listo para siguiente lectura' : 'Esperando lectura...'">
                {{ $lastStatus ? 'Listo para siguiente lectura' : 'Esperando lectura...' }}
            </p>
        </div>
    </div>

    {{-- Camera Area --}}
    <div x-show="localUseCamera" class="space-y-4" x-cloak>
        <div class="relative max-w-sm mx-auto aspect-square overflow-hidden rounded-3xl border-4 border-zinc-200 dark:border-zinc-700 bg-black"
            wire:ignore>
            {{-- Loading State --}}
            <div x-show="isStarting"
                class="absolute inset-0 flex flex-col items-center justify-center bg-zinc-900 z-10">
                <flux:icon name="camera" class="w-12 h-12 text-zinc-700 animate-pulse mb-4" />
                <flux:text size="sm" class="text-zinc-500">Iniciando cámara...</flux:text>
            </div>

            <div id="reader" class="w-full h-full [&>video]:object-cover [&>video]:w-full [&>video]:h-full"></div>
        </div>

        <div class="text-center">
            <flux:text size="sm" class="text-zinc-500">Apunta la cámara al código QR o de barras</flux:text>
        </div>
    </div>

    {{-- Feedback Card --}}
    <div x-show="lastStudent || (statusMessage && lastStatus === 'error')" x-cloak
        class="rounded-2xl p-5 border transition-all duration-300"
        :class="{
            'bg-green-50 dark:bg-green-950/20 border-green-200 dark:border-green-800': lastStatus === 'success',
            'bg-amber-50 dark:bg-amber-950/20 border-amber-200 dark:border-amber-800': lastStatus === 'retardo' || lastStatus === 'duplicate',
            'bg-red-50 dark:bg-red-950/20 border-red-200 dark:border-red-800': lastStatus === 'error',
        }">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl flex items-center justify-center shrink-0"
                :class="{
                    'bg-green-100 dark:bg-green-900': lastStatus === 'success',
                    'bg-amber-100 dark:bg-amber-900': lastStatus === 'retardo' || lastStatus === 'duplicate',
                    'bg-red-100 dark:bg-red-900': lastStatus === 'error',
                }">
                <template x-if="lastStatus === 'success'"><flux:icon name="check-circle" class="w-7 h-7 text-green-600 dark:text-green-400" /></template>
                <template x-if="lastStatus === 'retardo'"><flux:icon name="clock" class="w-7 h-7 text-amber-600 dark:text-amber-400" /></template>
                <template x-if="lastStatus === 'duplicate'"><flux:icon name="information-circle" class="w-7 h-7 text-amber-600 dark:text-amber-400" /></template>
                <template x-if="lastStatus === 'error'"><flux:icon name="x-circle" class="w-7 h-7 text-red-600 dark:text-red-400" /></template>
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2 mb-1">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold text-white uppercase"
                        :class="{
                            'bg-green-500': lastStatus === 'success',
                            'bg-amber-500': lastStatus === 'retardo' || lastStatus === 'duplicate',
                            'bg-red-500': lastStatus === 'error',
                        }" x-text="statusMessage">{{ $statusMessage }}</span>
                    <span x-show="lastEntryTime" class="font-mono text-lg font-bold"
                        :class="{
                            'text-green-700 dark:text-green-300': lastStatus === 'success',
                            'text-amber-700 dark:text-amber-300': lastStatus === 'retardo' || lastStatus === 'duplicate',
                            'text-red-700 dark:text-red-300': lastStatus === 'error',
                        }" x-text="lastEntryTime">{{ $lastEntryTime }}</span>
                </div>

                <template x-if="lastStudent">
                    <div>
                        <p class="font-bold text-zinc-900 dark:text-white truncate uppercase" x-text="lastStudent.name"></p>
                        <div class="flex gap-2 mt-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300" x-text="lastStudent.grade + ' ' + lastStudent.group_name"></span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300" x-text="lastStudent.turn"></span>
                        </div>
                    </div>
                </template>
                <template x-if="lastStatus === 'error' && !lastStudent">
                    <p class="text-sm text-red-600 dark:text-red-400">CURP no encontrado en el sistema</p>
                </template>
            </div>
        </div>
    </div>

    {{-- Recent Scans --}}
    <div x-show="recentScans.length > 0" x-cloak>
        <flux:text variant="subtle" class="text-xs font-medium mb-2 uppercase tracking-wide">Últimos registros
        </flux:text>
        <div class="space-y-1.5">
            <template x-for="(scan, i) in recentScans" :key="i">
                <div
                    class="flex items-center justify-between px-4 py-2 rounded-xl bg-zinc-50 dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full" :class="'bg-' + scan.color + '-500'"></div>
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300 truncate max-w-48" x-text="scan.name"></span>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px]" :class="'bg-' + scan.color + '-100 text-' + scan.color + '-700 dark:bg-' + scan.color + '-900 dark:text-' + scan.color + '-300'" x-text="scan.status"></span>
                        <span class="font-mono text-xs text-zinc-400" x-text="scan.time"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Re-focus button --}}
    <div class="text-center" x-show="!localUseCamera">
        <flux:button variant="subtle" size="sm" icon="cursor-arrow-rays" title="Enfocar cursor para escanear"
            x-on:click="document.getElementById('scanner-input').focus()">
            Re-enfocar escáner
        </flux:button>
    </div>

    @script
    <script>
        Alpine.data('scannerComponent', () => ({
            localUseCamera: false,
            html5QrCode: null,
            scanCooldowns: {}, // Map of CURP -> Timestamp
            isStarting: false,
            curpInput: '',

            // Cache state
            cacheReady: false,
            cacheLoading: false,
            cacheCount: 0,
            cacheError: false,

            // UI state (Alpine-only)
            lastStudent: @entangle('lastStudent'),
            lastStatus: @entangle('lastStatus'),
            statusMessage: @entangle('statusMessage'),
            lastEntryTime: @entangle('lastEntryTime'),
            recentScans: @entangle('recentScans'),
            hidConnected: 0,
            hidDeviceNames: '',
            
            // Queue state
            pendingQueue: [],
            isSyncingQueue: false,

            async init() {
                this.setupFocus();
                await this.loadCache();
                await this.loadPendingQueue();
                
                // HID Listener
                window.addEventListener('hid-scan', (e) => {
                    this.handleScan(e.detail.curp);
                });

                // Auto-connect HID if previously allowed
                if (typeof HidScanner !== 'undefined') {
                    if (await HidScanner.autoConnect()) {
                        this.updateHidStatus();
                    }
                }

                // Start initial sync if there's data
                this.processQueue();
            },

            async loadPendingQueue() {
                if (typeof CurpCache === 'undefined') return;
                this.pendingQueue = await CurpCache.getQueue();
            },

            async loadCache() {
                if (typeof CurpCache === 'undefined') return;
                this.cacheLoading = true;
                try {
                    this.cacheCount = await CurpCache.init('{{ route("api.curps") }}');
                    this.cacheReady = true;
                } catch (e) {
                    console.warn('CURP cache init failed:', e);
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

            async connectHID() {
                if (typeof HidScanner === 'undefined') return;
                if (await HidScanner.connect()) {
                    this.updateHidStatus();
                }
            },

            updateHidStatus() {
                this.hidConnected = HidScanner.getConnectedCount();
                this.hidDeviceNames = HidScanner.getConnectedNames();
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

            async handleScan(decodedText) {
                const curp = (decodedText || this.curpInput || '').trim().toUpperCase();
                
                // CRITICAL: Clear input immediately to prevent concatenation
                this.curpInput = '';
                const input = document.getElementById('scanner-input');
                if (input) input.value = '';

                if (!curp) return;

                // Per-student cooldown: ignore same CURP if scanned within last 5 seconds
                const now = Date.now();
                if (this.scanCooldowns[curp] && (now - this.scanCooldowns[curp]) < 5000) {
                    console.log('Cooldown activo para:', curp);
                    return;
                }

                // Set cooldown immediately for this specific CURP
                this.scanCooldowns[curp] = now;

                // 1. Identification using local cache
                let studentInfo = null;
                if (this.cacheReady) {
                    studentInfo = await CurpCache.lookup(curp);
                }

                if (studentInfo) {
                    // Instant UI update for known students
                    this.lastStudent = studentInfo;
                    this.lastStatus = 'success';
                    this.statusMessage = 'Identificado (Local)';
                    this.lastEntryTime = new Date().toTimeString().slice(0, 8);
                    playLocalSound('success');
                } else {
                    // Feedback for unknown students
                    this.lastStudent = null;
                    this.lastStatus = 'error';
                    this.statusMessage = 'CURP No reconocido (Buscando...)';
                    playLocalSound('warning');
                }

                // 2. Add to IndexedDB Queue
                const queueItem = await CurpCache.addToQueue(curp, studentInfo);
                this.pendingQueue.push(queueItem);

                // 3. Trigger Sync
                this.processQueue();
            },

            async processQueue() {
                if (this.isSyncingQueue || this.pendingQueue.length === 0) return;
                this.isSyncingQueue = true;

                while (this.pendingQueue.length > 0) {
                    const item = this.pendingQueue[0];
                    console.log('Sincronizando:', item.curp);

                    try {
                        const res = await fetch('{{ route("api.attendance") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ 
                                curp: item.curp,
                                timestamp: item.timestamp
                            })
                        });

                        if (!res.ok) throw new Error('Network error');

                        const data = await res.json();
                        
                        // Update UI with server refinement
                        this.lastStatus = data.duplicate ? 'duplicate' : (data.status === 'RETARDO' ? 'retardo' : 'success');
                        this.statusMessage = data.duplicate ? 'Ya registrado' : (data.status === 'RETARDO' ? 'RETARDO Registrado' : 'ASISTENCIA Registrada');
                        this.lastEntryTime = data.entry_time;
                        
                        // Success! Remove from IDB and Local State
                        await CurpCache.removeFromQueue(item.timestamp);
                        this.pendingQueue.shift();
                        
                        // Refresh Livewire recentScans
                        $wire.$refresh();

                    } catch (e) {
                        console.warn('Sync failed for CURP:', item.curp, e);
                        // If network fails (120kbps/offline), wait 5 seconds and retry
                        await new Promise(r => setTimeout(r, 5000));
                        // Break the loop and let it be re-triggered by next scan or manual poll
                        break; 
                    }
                }

                this.isSyncingQueue = false;
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
                this.handleScan(decodedText);
            }
        }));
    </script>
    @endscript
</div>