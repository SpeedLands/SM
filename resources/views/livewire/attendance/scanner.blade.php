<?php

use App\Models\Setting;
use Livewire\Volt\Component;

new class extends Component {
    public bool $useCamera = false;

    public function mount(): void
    {
        $this->authorize('teacher-or-admin');
    }

    public function getSettings(): array
    {
        return [
            'matutino_entry_time' => Setting::get('attendance.matutino_entry_time', '07:30'),
            'vespertino_entry_time' => Setting::get('attendance.vespertino_entry_time', '13:30'),
            'grace_minutes' => (int) Setting::get('attendance.grace_minutes', 10),
        ];
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

    {{-- Cache status + queue indicator --}}
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
            <template x-if="pendingCount > 0">
                <span class="inline-flex items-center gap-1 ml-2 text-blue-500">
                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span x-text="pendingCount + ' pendiente(s)'"></span>
                </span>
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
            x-model="curpInput"
            x-on:keydown.enter.prevent="handleScan()"
            class="absolute inset-0 opacity-0 cursor-default z-10 w-full h-full"
            autocomplete="off"
        />

        <div class="flex flex-col items-center justify-center p-12 border-4 rounded-3xl transition-all duration-300"
            :class="{
                'border-green-400 dark:border-green-600 bg-green-50 dark:bg-green-950/20': lastStatus === 'success',
                'border-amber-400 dark:border-amber-600 bg-amber-50 dark:bg-amber-950/20': lastStatus === 'retardo' || lastStatus === 'duplicate',
                'border-red-400 dark:border-red-600 bg-red-50 dark:bg-red-950/20': lastStatus === 'error',
                'border-dashed border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 animate-pulse': !lastStatus,
            }">
            <template x-if="lastStatus === 'success'">
                <flux:icon name="check-circle" class="w-20 h-20 mb-4 text-green-500 transition-all duration-300" />
            </template>
            <template x-if="lastStatus === 'retardo' || lastStatus === 'duplicate'">
                <flux:icon name="clock" class="w-20 h-20 mb-4 text-amber-500 transition-all duration-300" />
            </template>
            <template x-if="lastStatus === 'error'">
                <flux:icon name="x-circle" class="w-20 h-20 mb-4 text-red-500 transition-all duration-300" />
            </template>
            <template x-if="!lastStatus">
                <flux:icon name="qr-code" class="w-20 h-20 mb-4 text-zinc-300 dark:text-zinc-600 transition-all duration-300" />
            </template>
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400" x-text="lastStatus ? 'Listo para siguiente lectura' : 'Esperando lectura...'"></p>
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

    {{-- Feedback Card (fully Alpine-driven) --}}
    <div x-show="lastStudent" x-cloak class="rounded-2xl p-5 transition-all duration-300"
        :class="{
            'bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-800': lastStatus === 'success',
            'bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800': lastStatus === 'retardo' || lastStatus === 'duplicate',
            'bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800': lastStatus === 'error',
        }">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl flex items-center justify-center shrink-0"
                :class="{
                    'bg-green-100 dark:bg-green-900': lastStatus === 'success',
                    'bg-amber-100 dark:bg-amber-900': lastStatus === 'retardo' || lastStatus === 'duplicate',
                    'bg-red-100 dark:bg-red-900': lastStatus === 'error',
                }">
                <template x-if="lastStatus === 'success'">
                    <svg class="w-7 h-7 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </template>
                <template x-if="lastStatus === 'retardo' || lastStatus === 'duplicate'">
                    <svg class="w-7 h-7 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </template>
            </div>

            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2 mb-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold text-white uppercase"
                        :class="{
                            'bg-green-500': lastStatus === 'success',
                            'bg-amber-500': lastStatus === 'retardo' || lastStatus === 'duplicate',
                        }"
                        x-text="statusMessage"></span>
                    <span x-show="lastEntryTime" class="font-mono text-lg font-bold"
                        :class="{
                            'text-green-700 dark:text-green-300': lastStatus === 'success',
                            'text-amber-700 dark:text-amber-300': lastStatus === 'retardo' || lastStatus === 'duplicate',
                        }"
                        x-text="lastEntryTime"></span>
                </div>

                <p class="font-bold text-zinc-900 dark:text-white truncate uppercase" x-text="lastStudent?.name"></p>
                <div class="flex gap-2 mt-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300" x-text="lastStudent?.grade + ' ' + lastStudent?.group_name"></span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300" x-text="lastStudent?.turn"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Local cache error --}}
    <div x-show="lastStatus === 'error' && !lastStudent" x-cloak class="bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-2xl p-5">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-red-100 dark:bg-red-900 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-500 text-white uppercase">CURP No encontrado</span>
                <p class="text-sm text-red-600 dark:text-red-400 mt-1 font-mono" x-text="statusMessage"></p>
            </div>
        </div>
    </div>

    {{-- Recent Scans --}}
    <div x-show="recentScans.length > 0" x-cloak>
        <p class="text-xs font-medium mb-2 uppercase tracking-wide text-zinc-400">Últimos registros</p>
        <div class="space-y-1.5">
            <template x-for="(scan, i) in recentScans" :key="i">
                <div class="flex items-center justify-between px-4 py-2 rounded-xl bg-zinc-50 dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full" :class="'bg-' + scan.color + '-500'"></div>
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300 truncate max-w-48" x-text="scan.name"></span>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs" :class="'bg-' + scan.color + '-100 text-' + scan.color + '-700 dark:bg-' + scan.color + '-900 dark:text-' + scan.color + '-300'" x-text="scan.status"></span>
                        <span class="font-mono text-xs text-zinc-400" x-text="scan.time"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>

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
            scanCooldown: false,
            isStarting: false,
            curpInput: '',

            // CURP cache state
            cacheReady: false,
            cacheLoading: false,
            cacheCount: 0,
            cacheError: false,

            // Attendance settings (loaded from server once)
            settings: null,

            // UI state (fully Alpine-driven, no Livewire round trips)
            lastStudent: null,
            lastStatus: null,
            statusMessage: '',
            lastEntryTime: '',
            recentScans: [],

            // Background queue
            pendingCount: 0,

            async init() {
                this.setupFocus();
                await this.loadCache();
                this.settings = await $wire.getSettings();

                // Clean up cache when navigating away
                document.addEventListener('livewire:navigating', () => {
                    CurpCache.destroy().catch(() => {});
                });
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
             * Determine attendance status locally using cached settings.
             */
            determineStatus(turn) {
                if (!this.settings) return 'PRESENTE';
                const now = new Date();
                const nowMinutes = now.getHours() * 60 + now.getMinutes();

                let entryTime = null;
                if (turn === 'MATUTINO') {
                    entryTime = this.settings.matutino_entry_time;
                } else if (turn === 'VESPERTINO') {
                    entryTime = this.settings.vespertino_entry_time;
                }

                if (entryTime) {
                    const [h, m] = entryTime.split(':').map(Number);
                    const threshold = h * 60 + m + this.settings.grace_minutes;
                    if (nowMinutes > threshold) return 'RETARDO';
                }
                return 'PRESENTE';
            },

            /**
             * Handle a scan — fully client-side UI + fire-and-forget server call.
             */
            async handleScan(decodedText) {
                const curp = (decodedText || this.curpInput || '').trim().toUpperCase();
                if (!curp) return;
                if (this.scanCooldown) return;

                this.scanCooldown = true;
                this.curpInput = '';

                // Pre-validate with local cache
                if (this.cacheReady) {
                    try {
                        const cached = await CurpCache.lookup(curp);
                        if (!cached) {
                            // CURP not found — instant error, no server call
                            this.lastStudent = null;
                            this.lastStatus = 'error';
                            this.statusMessage = curp;
                            this.lastEntryTime = '';
                            playLocalSound('error');
                            setTimeout(() => { this.scanCooldown = false; }, 1500);
                            return;
                        }

                        // Determine status locally
                        const status = this.determineStatus(cached.turn);
                        const now = new Date();
                        const timeStr = now.toTimeString().slice(0, 8);

                        // Update UI instantly
                        this.lastStudent = cached;
                        this.lastStatus = status === 'RETARDO' ? 'retardo' : 'success';
                        this.statusMessage = status === 'RETARDO' ? 'RETARDO Registrado' : 'ASISTENCIA Registrada';
                        this.lastEntryTime = timeStr;

                        // Add to recent scans
                        this.recentScans.unshift({
                            name: cached.name,
                            time: timeStr.slice(0, 5),
                            status: status,
                            color: status === 'RETARDO' ? 'amber' : 'green',
                        });
                        if (this.recentScans.length > 5) this.recentScans.pop();

                        playLocalSound(status === 'RETARDO' ? 'warning' : 'success');

                        // Fire-and-forget: send to server in background
                        this.sendAttendanceBackground(curp);

                        // Release cooldown quickly for next scan
                        setTimeout(() => { this.scanCooldown = false; }, 800);
                        return;
                    } catch (e) {
                        console.warn('Cache lookup error, falling back to server:', e);
                    }
                }

                // Fallback: no cache — send directly and wait for response
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                    const res = await fetch('{{ route("api.attendance") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ curp: curp }),
                    });
                    const data = await res.json();
                    if (data.error) {
                        this.lastStudent = null;
                        this.lastStatus = 'error';
                        this.statusMessage = curp;
                        this.lastEntryTime = '';
                        playLocalSound('error');
                    } else {
                        this.lastStudent = { name: data.student_name };
                        this.lastStatus = data.status === 'RETARDO' ? 'retardo' : 'success';
                        this.statusMessage = data.duplicate ? 'Ya registrado' : (data.status === 'RETARDO' ? 'RETARDO Registrado' : 'ASISTENCIA Registrada');
                        this.lastEntryTime = data.entry_time || '';
                        playLocalSound(data.status === 'RETARDO' ? 'warning' : 'success');
                    }
                } catch (err) {
                    console.error('Attendance fetch failed:', err);
                    this.lastStatus = 'error';
                    this.statusMessage = 'Error de conexión';
                    playLocalSound('error');
                }
                setTimeout(() => { this.scanCooldown = false; }, 1500);
            },

            /**
             * Send attendance to server without blocking the scanner.
             */
            sendAttendanceBackground(curp) {
                this.pendingCount++;
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                fetch('{{ route("api.attendance") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ curp: curp }),
                })
                .then(res => res.json())
                .then(data => {
                    if (data.duplicate) {
                        // Update the last scan in recent list to show duplicate
                        const existing = this.recentScans.find(s => s.curp === curp);
                        // Server confirmed duplicate — UI already showed success, just log
                        console.debug('Attendance saved (duplicate):', curp);
                    } else {
                        console.debug('Attendance saved:', curp, data.status);
                    }
                })
                .catch(err => {
                    console.error('Background attendance failed for:', curp, err);
                })
                .finally(() => {
                    this.pendingCount--;
                });
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
    </script>
    @endscript
</div>
