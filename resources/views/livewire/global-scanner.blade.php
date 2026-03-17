<?php

use App\Models\Student;
use Livewire\Volt\Component;

new class extends Component {
    public string $curp = '';
    public ?Student $student = null;
    public string $statusMessage = '';
    public string $statusColor = 'zinc';
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

        $this->student = Student::where('curp', $this->curp)->first();

        if (!$this->student) {
            $this->statusMessage = "CURP No encontrado: $this->curp";
            $this->statusColor = 'red';
            $this->dispatch('play-sound', ['type' => 'error']);
            $this->curp = '';
            return;
        }

        $this->statusMessage = "Alumno encontrado";
        $this->statusColor = 'green';
        $this->dispatch('play-sound', ['type' => 'success']);
        $this->curp = '';
    }

    public function resetScanner(): void
    {
        $this->reset(['student', 'statusMessage', 'statusColor', 'curp']);
    }
}; ?>

<div class="max-w-2xl mx-auto py-8 px-4 space-y-6" x-data="globalScannerComponent()">
    {{-- html5-qrcode CDN --}}
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    {{-- CURP local cache --}}
    <script src="/js/curp-cache.js"></script>

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <flux:button :href="route('dashboard')" icon="arrow-left" variant="subtle" size="sm" title="Volver al tablero" />
        <div class="flex-1">
            <flux:heading size="xl">Escaneo Rápido de Alumno</flux:heading>
            <flux:subheading>Escanea para realizar acciones disciplinarias rápidas</flux:subheading>
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
    <div x-show="!$wire.student && !localError" class="space-y-6">
        <div class="relative" x-show="!localUseCamera">
            <input
                autofocus
                id="scanner-input"
                type="text"
                wire:model="curp"
                x-on:keydown.enter.prevent="handleScan()"
                class="absolute inset-0 opacity-0 cursor-default z-10 w-full h-full"
                autocomplete="off"
            />

            <div class="flex flex-col items-center justify-center p-12 bg-white dark:bg-zinc-900 border-4 border-dashed border-zinc-200 dark:border-zinc-700 rounded-3xl animate-pulse transition-all duration-300">
                <flux:icon name="qr-code" class="w-20 h-20 mb-4 text-zinc-300 dark:text-zinc-600" />
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Esperando lectura...</p>
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
                <flux:text size="sm" class="text-zinc-500">Apunta la cámara al código QR del alumno</flux:text>
            </div>
        </div>
    </div>

    {{-- Local cache error (no server call needed) --}}
    <div x-show="localError" x-cloak class="space-y-3">
        <div class="bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-2xl p-5 text-center space-y-3">
            <svg class="w-12 h-12 text-red-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="font-bold text-red-800 dark:text-red-200">CURP No encontrado: <span class="font-mono" x-text="localError"></span></p>
            <button type="button" x-on:click="clearLocalError()" class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-red-600 bg-red-100 dark:bg-red-900 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-800 transition">Reintentar</button>
        </div>
    </div>

    {{-- Student Result and Actions --}}
    @if($student)
        <div class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-3xl p-6 shadow-sm">
                <div class="flex items-start gap-6">
                    <div class="w-20 h-20 bg-zinc-100 dark:bg-zinc-800 rounded-2xl flex items-center justify-center shrink-0">
                        <flux:icon name="user" class="w-10 h-10 text-zinc-400" />
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-2">
                            <flux:heading size="lg" class="uppercase">{{ $student->name }}</flux:heading>
                            <flux:badge color="green" variant="solid" size="sm">ENCONTRADO</flux:badge>
                        </div>
                        <div class="flex flex-wrap gap-2 text-sm text-zinc-500">
                            <flux:badge variant="subtle" size="sm">{{ $student->grade }} {{ $student->group_name }}</flux:badge>
                            <flux:badge variant="subtle" size="sm">{{ $student->turn }}</flux:badge>
                            <span class="font-mono text-xs">{{ $student->curp }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <flux:button 
                    variant="primary" 
                    icon="document-text" 
                    class="h-24 flex-col gap-2 text-lg"
                    href="{{ route('reports.index', ['open_create' => 1, 'student_id' => $student->id, 'student_name' => $student->name]) }}"
                    wire:navigate
                >
                    Crear Reporte
                </flux:button>
                
                <flux:button 
                    variant="primary" 
                    icon="calendar-days" 
                    class="h-24 flex-col gap-2 text-lg"
                    href="{{ route('citations.index', ['open_create' => 1, 'student_id' => $student->id, 'student_name' => $student->name]) }}"
                    wire:navigate
                >
                    Crear Citatorio
                </flux:button>

                <flux:button 
                    variant="primary" 
                    icon="briefcase" 
                    class="h-24 flex-col gap-2 text-lg"
                    href="{{ route('community-services.index', ['open_create' => 1, 'student_id' => $student->id, 'student_name' => $student->name]) }}"
                    wire:navigate
                >
                    Asignar Servicio
                </flux:button>
            </div>

            <div class="text-center">
                <flux:button variant="ghost" icon="arrow-path" wire:click="resetScanner">Escanear otro alumno</flux:button>
            </div>
        </div>
    @elseif($statusMessage && $statusColor === 'red')
        <div class="bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800 rounded-2xl p-5 text-center space-y-3">
            <flux:icon name="x-circle" class="w-12 h-12 text-red-500 mx-auto" />
            <p class="font-bold text-red-800 dark:text-red-200">{{ $statusMessage }}</p>
            <flux:button size="sm" variant="subtle" color="red" wire:click="resetScanner">Reintentar</flux:button>
        </div>
    @endif

    {{-- Re-focus button --}}
    <div class="text-center" x-show="!localUseCamera && !$wire.student">
        <flux:button variant="subtle" size="sm" icon="cursor-arrow-rays"
            x-on:click="document.getElementById('scanner-input').focus()">
            Re-enfocar escáner
        </flux:button>
    </div>

    @script
    <script>
        Alpine.data('globalScannerComponent', () => ({
            localUseCamera: false,
            html5QrCode: null,
            scanCooldown: false,
            isStarting: false,

            // CURP cache state
            cacheReady: false,
            cacheLoading: false,
            cacheCount: 0,
            cacheError: false,
            localError: null,

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

            clearLocalError() {
                this.localError = null;
                this.$nextTick(() => {
                    document.getElementById('scanner-input')?.focus();
                });
            },

            async handleScan(decodedText) {
                const curp = (decodedText || $wire.curp || '').trim().toUpperCase();
                if (!curp) return;
                if (this.scanCooldown) return;

                this.scanCooldown = true;
                this.localError = null;

                // Pre-validate with local cache
                if (this.cacheReady) {
                    try {
                        const cached = await CurpCache.lookup(curp);
                        if (!cached) {
                            this.localError = curp;
                            playLocalSound('error');
                            $wire.set('curp', '');
                            setTimeout(() => { this.scanCooldown = false; }, 3000);
                            return;
                        }
                    } catch (e) {
                        console.warn('Cache lookup error:', e);
                    }
                }

                // Send to server
                $wire.set('curp', curp);
                $wire.processScan();

                setTimeout(() => { this.scanCooldown = false; }, 3000);
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
                    
                    if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                        alert("Has denegado el permiso de la cámara. Por favor, actívalo en la configuración de tu navegador para poder escanear.");
                    } else {
                        alert("No se pudo acceder a la cámara. Verifica que no esté siendo usada por otra aplicación.");
                    }
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
