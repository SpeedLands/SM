<?php

use App\Models\Setting;
use Livewire\Volt\Component;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

new class extends Component
{
    public string $curp = '';
    public ?array $lastStudent = null;
    public string $statusMessage = '';
    public string $statusColor = 'zinc';
    public string $lastEntryTime = '';
    public string $lastStatus = '';
    public array $recentScans = [];
    public bool $useCamera = false;
    public int $totalStudents = 0;

    public function mount(): void
    {
        $this->authorize('teacher-or-admin');
        $this->totalStudents = Student::count();
    }

    public function processScan(?string $manualTime = null): void
    {
        $this->curp = trim(strtoupper($this->curp));
        if (empty($this->curp)) return;

        $student = Student::where('curp', $this->curp)->first();
        if (!$student) {
            $this->setError("CURP No encontrado: $this->curp");
            return;
        }

        $today = Carbon::today()->toDateString();
        $now = $manualTime ? Carbon::parse($manualTime) : Carbon::now();
        
        // Check for existing attendance for today
        $attendance = Attendance::where('student_id', $student->id)
            ->whereDate('date', $today)
            ->first();

        if ($attendance) {
            $this->setDuplicate($student, $attendance);
            return;
        }

        $status = $this->calculateStatus($student, $now);

        // Create attendance record
        $attendance = Attendance::create([
            'student_id' => $student->id,
            'date'       => $today,
            'entry_time' => $now->format('H:i:s'),
            'status'     => $status,
        ]);
        $this->setSuccess($student, $status, $now);
    }

    private function calculateStatus(Student $student, Carbon $now): string
    {
        $graceMinutes = (int) Setting::get('attendance.grace_minutes', 10);
        $entryTimeKey = $student->turn === 'VESPERTINO' ? 'attendance.vespertino_entry_time' : 'attendance.matutino_entry_time';
        $defaultTime = $student->turn === 'VESPERTINO' ? '13:30' : '07:30';
        
        $entryTime = Setting::get($entryTimeKey, $defaultTime);
        $threshold = Carbon::createFromFormat('H:i', $entryTime)->addMinutes($graceMinutes);
        
        return $now->greaterThan($threshold) ? 'RETARDO' : 'PRESENTE';
    }

    private function setSuccess(Student $student, string $status, Carbon $now): void
    {
        $this->lastStudent = $student->toArray();
        $this->lastStatus = $status === 'RETARDO' ? 'retardo' : 'success';
        $this->statusMessage = $status === 'RETARDO' ? "RETARDO Registrado" : "ASISTENCIA Registrada";
        $this->statusColor = $status === 'RETARDO' ? 'amber' : 'green';
        $this->lastEntryTime = $now->format('H:i:s');
        $this->curp = '';
        $this->addToRecent($student, $status);
        $this->dispatch('play-sound', ['type' => 'success']);
    }

    private function setDuplicate(Student $student, Attendance $attendance): void
    {
        $this->statusMessage = "Ya se registró asistencia hoy";
        $this->statusColor = 'amber';
        $this->lastStatus = 'duplicate';
        $this->lastStudent = $student->toArray();
        $this->lastEntryTime = $attendance->entry_time->format('H:i:s');
        $this->curp = '';
        $this->dispatch('play-sound', ['type' => 'warning']);
    }

    private function setError(string $message): void
    {
        $this->statusMessage = "No encontrado";
        $this->statusColor = 'red';
        $this->lastStatus = 'error';
        $this->lastStudent = null;
        $this->curp = '';
        $this->dispatch('play-sound', ['type' => 'error']);
    }

    private function addToRecent(Student $student, string $status): void
    {
        array_unshift($this->recentScans, [
            'name' => $student->name,
            'time' => now()->format('H:i'),
            'status' => $status,
            'color' => $this->statusColor,
        ]);
        $this->recentScans = array_slice($this->recentScans, 0, 5);
    }
}; ?>

<div class="max-w-2xl mx-auto py-8 px-4 space-y-6" 
    x-data="scannerComponent({
        apiCurpsUrl: '{{ route('api.curps') }}',
        apiAttendanceUrl: '{{ route('api.attendance') }}',
        csrfToken: '{{ csrf_token() }}',
        lastStudent: @entangle('lastStudent'),
        lastStatus: @entangle('lastStatus'),
        statusMessage: @entangle('statusMessage'),
        lastEntryTime: @entangle('lastEntryTime'),
        recentScans: @entangle('recentScans')
    })">
    
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script src="/js/curp-cache.js?v={{ filemtime(public_path('js/curp-cache.js')) }}"></script>
    <script src="/js/hid-scanner.js?v={{ filemtime(public_path('js/hid-scanner.js')) }}"></script>
    <script src="/js/attendance-scanner.js?v={{ filemtime(public_path('js/attendance-scanner.js')) }}"></script>

    {{-- Sound Helper --}}
    <script>
        function playLocalSound(type) {
            const sounds = {
                success: 'https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3',
                warning: 'https://assets.mixkit.co/active_storage/sfx/2857/2857-preview.mp3',
                error: 'https://assets.mixkit.co/active_storage/sfx/2858/2858-preview.mp3'
            };
            new Audio(sounds[type]).play().catch(() => {});
        }
        window.addEventListener('play-sound', (e) => playLocalSound(e.detail[0].type));
    </script>

    {{-- Header --}}
    <div class="flex items-center gap-4 bg-white dark:bg-zinc-900 p-4 rounded-3xl border border-zinc-100 dark:border-zinc-800 shadow-sm">
        <flux:button :href="route('attendance.index')" icon="arrow-left" variant="ghost" size="sm" />
        <div class="flex-1">
            <flux:heading size="lg">Escáner</flux:heading>
            <flux:subheading size="xs" class="uppercase tracking-widest font-bold opacity-50">Registro de Asistencia</flux:subheading>
        </div>
        <flux:button x-show="!localUseCamera" icon="camera" size="sm" variant="subtle" x-on:click="toggleCamera()">Cámara</flux:button>
        <flux:button x-show="localUseCamera" icon="computer-desktop" size="sm" variant="subtle" x-on:click="toggleCamera()" x-cloak>Lector</flux:button>
    </div>

    {{-- Device Status --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="bg-zinc-50 dark:bg-zinc-900/50 p-4 rounded-2xl border border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-white dark:bg-zinc-800 shadow-sm">
                    <flux:icon name="cpu-chip" x-bind:class="hidConnected ? 'text-blue-500' : 'text-zinc-300'" size="sm" />
                </div>
                <div>
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-tighter">Lectores HID</p>
                    <p class="text-xs font-bold" x-bind:class="hidConnected ? 'text-blue-600' : 'text-zinc-500'" x-text="hidConnected ? hidConnected + ' Activos' : 'Desconectado'"></p>
                </div>
            </div>
            <flux:button size="xs" variant="ghost" x-on:click="connectHID()">Conectar</flux:button>
        </div>

        <div class="bg-zinc-50 dark:bg-zinc-900/50 p-4 rounded-2xl border border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-white dark:bg-zinc-800 shadow-sm">
                    <flux:icon name="circle-stack" x-bind:class="cacheReady ? 'text-green-500' : 'text-zinc-300'" size="sm" />
                </div>
                <div>
                    <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-tighter">Base Local</p>
                    <p class="text-xs font-bold" x-bind:class="cacheReady ? 'text-green-600' : 'text-zinc-500'" x-text="cacheReady ? cacheCount + ' CURPs' : 'Sin Datos'"></p>
                </div>
            </div>
            <flux:button size="xs" variant="ghost" x-on:click="refreshCache('{{ route('api.curps') }}')">Sincronizar</flux:button>
        </div>
    </div>

    {{-- Sync Queue Status --}}
    <div x-show="pendingQueue.length > 0" x-cloak class="bg-amber-500/10 border border-amber-200 dark:border-amber-900/50 p-3 rounded-2xl flex items-center justify-between">
        <div class="flex items-center gap-2">
            <flux:icon name="arrow-path" class="w-4 h-4 text-amber-600 animate-spin" />
            <span class="text-xs font-bold text-amber-700 dark:text-amber-400" x-text="pendingQueue.length + ' lecturas pendientes de sincronizar'"></span>
        </div>
        <span class="text-[10px] font-black uppercase tracking-widest text-amber-600">Sincronizando...</span>
    </div>

    {{-- Scan Area --}}
    <div class="relative group" x-show="!localUseCamera">
        <input autofocus id="scanner-input" type="text" x-model="curpInput" x-on:keydown.enter.prevent="handleScan()"
            class="absolute inset-0 opacity-0 cursor-default z-10 w-full h-full" autocomplete="off" />

        <div class="flex flex-col items-center justify-center p-16 border-4 border-dashed rounded-[3rem] transition-all duration-500"
            :class="{
                'bg-green-50 dark:bg-green-950/20 border-green-400 dark:border-green-600 scale-[1.02] shadow-xl': lastStatus === 'success',
                'bg-amber-50 dark:bg-amber-950/20 border-amber-400 dark:border-amber-600 scale-[1.02] shadow-xl': lastStatus === 'retardo' || lastStatus === 'duplicate',
                'bg-red-50 dark:bg-red-950/20 border-red-400 dark:border-red-600 scale-[1.02] shadow-xl': lastStatus === 'error',
                'bg-white dark:bg-zinc-900/40 border-zinc-200 dark:border-zinc-800 animate-pulse': !lastStatus
            }">
            
            <div class="relative">
                <template x-if="lastStatus === 'success'"><flux:icon name="check-circle" variant="solid" class="w-24 h-24 text-green-500" /></template>
                <template x-if="lastStatus === 'retardo'"><flux:icon name="clock" variant="solid" class="w-24 h-24 text-amber-500" /></template>
                <template x-if="lastStatus === 'duplicate'"><flux:icon name="information-circle" variant="solid" class="w-24 h-24 text-amber-400" /></template>
                <template x-if="lastStatus === 'error'"><flux:icon name="x-circle" variant="solid" class="w-24 h-24 text-red-500" /></template>
                <template x-if="!lastStatus"><flux:icon name="qr-code" variant="solid" class="w-24 h-24 text-zinc-200 dark:text-zinc-800" /></template>
                
                {{-- Scanning animation lines --}}
                <div x-show="!lastStatus" class="absolute top-0 left-0 w-full h-0.5 bg-blue-500 shadow-[0_0_15px_rgba(59,130,246,0.8)] animate-scan"></div>
            </div>
            
            <p class="mt-6 text-sm font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-[0.2em]" x-text="lastStatus ? 'Siguiente lectura' : 'Escanea ahora'">
                {{ $lastStatus ? 'Siguiente lectura' : 'Escanea ahora' }}
            </p>
        </div>
    </div>

    {{-- Camera Area --}}
    <div x-show="localUseCamera" class="space-y-4" x-cloak>
        <div class="relative max-w-sm mx-auto aspect-square overflow-hidden rounded-[2.5rem] border-8 border-white dark:border-zinc-800 shadow-2xl bg-black"
            wire:ignore>
            <div x-show="isStarting" class="absolute inset-0 flex flex-col items-center justify-center bg-zinc-900 z-10">
                <flux:icon name="camera" class="w-16 h-16 text-zinc-700 animate-pulse mb-4" />
                <flux:text size="sm" class="text-zinc-500 font-bold uppercase tracking-widest">Iniciando...</flux:text>
            </div>
            <div id="reader" class="w-full h-full [&>video]:object-cover"></div>
        </div>
    </div>

    {{-- Components --}}
    <div class="space-y-6">
        <livewire:attendance.scanner-feedback 
            :lastStudent="$lastStudent" 
            :statusMessage="$statusMessage" 
            :lastStatus="$lastStatus" 
            :lastEntryTime="$lastEntryTime" 
        />
        <livewire:attendance.recent-scans :scans="$recentScans" />
    </div>

    {{-- Re-focus trigger --}}
    <div class="text-center pt-4" x-show="!localUseCamera">
        <button x-on:click="document.getElementById('scanner-input').focus()" 
            class="text-[10px] font-black text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 uppercase tracking-[0.3em] transition-all py-2 px-4 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800">
            Re-enfocar cursor
        </button>
    </div>

    <style>
        @keyframes scan {
            0% { top: 0; }
            50% { top: 100%; }
            100% { top: 0; }
        }
        .animate-scan {
            animation: scan 3s infinite linear;
        }
    </style>
</div>