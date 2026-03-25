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
        if (empty($this->curp)) {
            return;
        }

        $student = Student::where('curp', $this->curp)->first();
        if (!$student) {
            $this->setError("CURP No encontrado: $this->curp");
            return;
        }

        $today = Carbon::today()->toDateString();
        $now = $manualTime ? Carbon::parse($manualTime) : Carbon::now();

        $attendance = Attendance::where('student_id', $student->id)
            ->whereDate('date', $today)
            ->first();

        if ($attendance) {
            $this->setDuplicate($student, $attendance);
            return;
        }

        $status = $this->calculateStatus($student, $now);

        Attendance::create([
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
            'name'   => $student->name,
            'time'   => now()->format('H:i'),
            'status' => $status,
            'color'  => $this->statusColor,
        ]);
        $this->recentScans = array_slice($this->recentScans, 0, 5);
    }
}; ?>

<div class="max-w-2xl mx-auto py-6 px-4 space-y-4"
    x-data="scannerComponent({
        apiCurpsUrl: '{{ route('api.curps') }}',
        apiAttendanceUrl: '{{ route('api.attendance') }}',
        csrfToken: '{{ csrf_token() }}',
        lastStudent: null,
        lastStatus: '',
        statusMessage: '',
        lastEntryTime: '',
        recentScans: []
    })">

    {{-- Focus Warning Banner (subtle error-style at top) --}}
    <div x-show="!windowHasFocus && !localUseCamera"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="fixed top-0 left-0 right-0 z-50 bg-red-600 dark:bg-red-700 text-white px-4 py-3 shadow-lg"
        style="display: none;">
        <div class="max-w-2xl mx-auto flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <flux:icon name="exclamation-triangle" class="w-5 h-5 shrink-0 animate-pulse" />
                <div>
                    <p class="text-sm font-bold">Escáner pausado — la ventana no tiene el foco</p>
                    <p class="text-xs opacity-80">Los escaneos no se registrarán hasta que vuelvas a esta ventana.</p>
                </div>
            </div>
            <button x-on:click="window.focus(); document.getElementById('scanner-input').focus();"
                class="px-4 py-1.5 bg-white/20 hover:bg-white/30 text-white text-xs font-bold rounded-lg transition shrink-0 uppercase tracking-wider">
                Reactivar
            </button>
        </div>
    </div>

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

    {{-- Device Status (compact inline) --}}
    <div class="flex items-center gap-2 flex-wrap">
        {{-- HID --}}
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
            x-bind:class="hidConnected
                ? 'bg-blue-50 border-blue-200 text-blue-700 dark:bg-blue-950/30 dark:border-blue-800 dark:text-blue-400'
                : 'bg-zinc-50 border-zinc-200 text-zinc-400 dark:bg-zinc-900 dark:border-zinc-800'">
            <flux:icon name="cpu-chip" class="w-3.5! h-3.5!" />
            <span x-text="hidConnected ? hidConnected + ' HID' : 'Sin HID'"></span>
            <button x-show="!hidConnected" x-on:click="connectHID()" class="ml-0.5 underline underline-offset-2 opacity-60 hover:opacity-100">Conectar</button>
        </div>
        {{-- Cache --}}
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border transition-colors"
            x-bind:class="cacheReady
                ? 'bg-green-50 border-green-200 text-green-700 dark:bg-green-950/30 dark:border-green-800 dark:text-green-400'
                : 'bg-zinc-50 border-zinc-200 text-zinc-400 dark:bg-zinc-900 dark:border-zinc-800'">
            <flux:icon name="circle-stack" class="w-3.5! h-3.5!" />
            <span x-text="cacheReady ? cacheCount + ' CURPs' : (cacheLoading ? 'Cargando...' : 'Sin datos')"></span>
            <button x-show="cacheReady" x-on:click="refreshCache('{{ route('api.curps') }}')" class="ml-0.5 underline underline-offset-2 opacity-60 hover:opacity-100">Sync</button>
        </div>
        {{-- Pending Queue --}}
        <div x-show="pendingQueue.length > 0" x-cloak
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border bg-amber-50 border-amber-200 text-amber-700 dark:bg-amber-950/30 dark:border-amber-800 dark:text-amber-400">
            <flux:icon name="arrow-path" class="w-3.5! h-3.5! animate-spin" />
            <span x-text="pendingQueue.length + ' pendientes'"></span>
        </div>
    </div>

    {{-- Scan Area (physical scanner) --}}
    <div class="relative group" x-show="!localUseCamera">
        <input autofocus id="scanner-input" type="text" x-model="curpInput" x-on:keydown.enter.prevent="handleScan()"
            class="absolute inset-0 opacity-0 cursor-default z-10 w-full h-full" autocomplete="off" />

        <div class="flex flex-col items-center justify-center p-12 border-4 border-dashed rounded-[2.5rem] transition-all duration-500"
            x-bind:class="{
                'bg-green-50 dark:bg-green-950/20 border-green-400 dark:border-green-600 scale-[1.01] shadow-xl': lastStatus === 'success',
                'bg-amber-50 dark:bg-amber-950/20 border-amber-400 dark:border-amber-600 scale-[1.01] shadow-xl': lastStatus === 'retardo' || lastStatus === 'duplicate',
                'bg-red-50 dark:bg-red-950/20 border-red-400 dark:border-red-600 scale-[1.01] shadow-xl': lastStatus === 'error',
                'bg-white dark:bg-zinc-900/40 border-zinc-200 dark:border-zinc-800': !lastStatus
            }">

            <div class="relative">
                <template x-if="lastStatus === 'success'"><flux:icon name="check-circle" variant="solid" class="w-20 h-20 text-green-500" /></template>
                <template x-if="lastStatus === 'retardo'"><flux:icon name="clock" variant="solid" class="w-20 h-20 text-amber-500" /></template>
                <template x-if="lastStatus === 'duplicate'"><flux:icon name="information-circle" variant="solid" class="w-20 h-20 text-amber-400" /></template>
                <template x-if="lastStatus === 'error'"><flux:icon name="x-circle" variant="solid" class="w-20 h-20 text-red-500" /></template>
                <template x-if="!lastStatus"><flux:icon name="qr-code" variant="solid" class="w-20 h-20 text-zinc-200 dark:text-zinc-800" /></template>

                {{-- Scanning animation --}}
                <div x-show="!lastStatus" class="absolute top-0 left-0 w-full h-0.5 bg-blue-500 shadow-[0_0_15px_rgba(59,130,246,0.8)] animate-scan"></div>
            </div>

            <p class="mt-4 text-sm font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-[0.2em]" x-text="lastStatus ? 'Siguiente lectura' : 'Escanea ahora'"></p>
        </div>
    </div>

    {{-- Camera Area --}}
    <div x-show="localUseCamera" x-cloak class="flex justify-center">
        <div class="relative w-80 h-80 overflow-hidden rounded-3xl border-4 border-white dark:border-zinc-800 shadow-2xl bg-black"
            wire:ignore>
            <div x-show="isStarting" class="absolute inset-0 flex flex-col items-center justify-center bg-zinc-900 z-10">
                <flux:icon name="camera" class="w-16 h-16 text-zinc-700 animate-pulse mb-4" />
                <flux:text size="sm" class="text-zinc-500 font-bold uppercase tracking-widest">Iniciando...</flux:text>
            </div>
            <div id="reader" class="w-full h-full [&>video]:w-full [&>video]:h-full [&>video]:object-cover"></div>
        </div>
    </div>

    {{-- Feedback + Recent Scans --}}
    <div class="space-y-4">
        <livewire:attendance.scanner-feedback
            :lastStudent="$lastStudent"
            :statusMessage="$statusMessage"
            :lastStatus="$lastStatus"
            :lastEntryTime="$lastEntryTime"
        />
        <livewire:attendance.recent-scans :scans="$recentScans" />
    </div>

    {{-- Re-focus trigger --}}
    <div class="text-center" x-show="!localUseCamera">
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