<div class="space-y-6" x-data="{ showFilters: false }">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl">Asistencia</flux:heading>
            <flux:subheading>Control diario de asistencia por grupo</flux:subheading>
        </div>
        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            @if($group_id)
                <flux:button wire:click="markAllPresent" icon="check-circle" variant="filled" size="sm"
                    wire:confirm="¿Marcar a todos como presentes?"
                    wire:loading.attr="disabled"
                    title="Marcar todos como presentes"
                    class="flex-1 sm:flex-none whitespace-nowrap">
                    <span wire:loading.remove wire:target="markAllPresent">Todos presentes</span>
                    <span wire:loading wire:target="markAllPresent">Guardando...</span>
                </flux:button>
            @endif
            <flux:button :href="route('attendance.group-stats')" icon="chart-bar" variant="ghost" size="sm" title="Ver estadísticas por grupo" class="flex-1 sm:flex-none">
                Estadísticas
            </flux:button>
            <flux:button :href="route('attendance.scanner')" icon="qr-code" variant="primary" size="sm" title="Escanear código QR" class="w-full sm:w-auto">
                Escáner
            </flux:button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="sm:hidden flex flex-wrap gap-2 pb-2 overflow-x-auto no-scrollbar">
        <flux:badge variant="solid" color="zinc" class="shrink-0">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</flux:badge>
        @if($grade) <flux:badge variant="solid" color="zinc" class="shrink-0">{{ $grade }} Secundaria</flux:badge> @endif
        <flux:button variant="ghost" size="xs" icon="funnel" class="ml-auto" title="Mostrar/ocultar filtros" x-on:click="showFilters = !showFilters" />
    </div>

    <div x-show="showFilters" class="sm:block!">
        <livewire:attendance.attendance-filters 
            :date="$date" 
            :cycle_id="$cycle_id" 
            :grade="$grade" 
            :group_id="$group_id" 
        />
    </div>

    @if($group_id)
        {{-- Stats --}}
        <livewire:attendance.attendance-stats 
            :presentes="$presentes" 
            :faltas="$faltas" 
            :retardos="$retardos" 
            :pendientes="$pendientes" 
        />

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
                    </div>

                    <div class="flex justify-between items-center bg-zinc-50 dark:bg-zinc-800/20 p-2 rounded-2xl gap-1">
                        @foreach([
                            'PRESENTE' => 'bg-green-500', 
                            'FALTA' => 'bg-red-500', 
                            'RETARDO' => 'bg-amber-500', 
                            'JUSTIFICADO' => 'bg-sky-500', 
                            'TRABAJO_EN_CASA' => 'bg-blue-600'
                        ] as $status => $bgColor)
                            <button wire:click="setStatus('{{ $student->id }}', '{{ $status }}')"
                                class="flex-1 h-12 rounded-xl flex items-center justify-center transition-all
                                {{ $attendance?->status === $status ? $bgColor . ' text-white shadow-lg' : 'text-zinc-400 dark:text-zinc-600 hover:bg-zinc-200 dark:hover:bg-zinc-800' }}">
                                <flux:icon :name="match($status) {
                                    'PRESENTE' => 'check',
                                    'FALTA' => 'x-mark',
                                    'RETARDO' => 'clock',
                                    'JUSTIFICADO' => 'document-text',
                                    'TRABAJO_EN_CASA' => 'home'
                                }" variant="solid" class="w-5 h-5" />
                            </button>
                        @endforeach
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
                                        @foreach([
                                            'PRESENTE' => 'bg-green-500 hover:bg-green-600', 
                                            'FALTA' => 'bg-red-500 hover:bg-red-600', 
                                            'RETARDO' => 'bg-amber-500 hover:bg-amber-600', 
                                            'JUSTIFICADO' => 'bg-sky-500 hover:bg-sky-600', 
                                            'TRABAJO_EN_CASA' => 'bg-blue-600 hover:bg-blue-700'
                                        ] as $st => $colorClasses)
                                            <button wire:click="setStatus('{{ $student->id }}', '{{ $st }}')"
                                                class="w-8 h-8 rounded-lg flex items-center justify-center transition-all
                                                {{ $attendance?->status === $st ? $colorClasses . ' text-white shadow-lg scale-110' : 'bg-transparent text-zinc-400 dark:text-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">
                                                <flux:icon :name="match($st) {
                                                    'PRESENTE' => 'check',
                                                    'FALTA' => 'x-mark',
                                                    'RETARDO' => 'clock',
                                                    'JUSTIFICADO' => 'document-text',
                                                    'TRABAJO_EN_CASA' => 'home'
                                                }" class="w-4 h-4" />
                                            </button>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

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
        </div>
    @endif
</div>
