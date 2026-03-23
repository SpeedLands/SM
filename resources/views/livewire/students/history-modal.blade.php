<?php

use App\Models\Student;
use App\Models\Cycle;
use App\Models\Report;
use App\Models\CommunityService;
use App\Models\Citation;
use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component {
    public bool $show = false;
    public string $studentName = '';
    public string $studentId = '';
    public bool $onlyActiveCycle = true;
    public array $items = [];

    #[On('view-history')]
    public function open(string $id): void
    {
        $student = Student::findOrFail($id);
        $this->studentName = $student->name;
        $this->studentId = $id;
        $this->onlyActiveCycle = true;
        $this->loadItems();
        $this->show = true;
    }

    public function updatedOnlyActiveCycle(): void
    {
        $this->loadItems();
    }

    protected function loadItems(): void
    {
        if (!$this->studentId) return;

        $activeCycle = Cycle::where('is_active', true)->first();
        $filterCycle = $this->onlyActiveCycle;
        $items = collect();

        // Reports
        $reports = Report::with('teacher', 'infraction')
            ->where('student_id', $this->studentId)
            ->when($filterCycle && $activeCycle, fn($q) => $q->where('cycle_id', $activeCycle->id))
            ->get();

        foreach ($reports as $r) {
            $items->push([
                'type' => 'report',
                'date' => $r->date ? $r->date->format('Y-m-d') : null,
                'date_display' => $r->date ? $r->date->isoFormat('D [de] MMMM, YYYY') : 'Sin fecha',
                'title' => $r->subject,
                'description' => $r->description,
                'extra' => $r->teacher?->name ?? '',
                'status' => $r->status,
            ]);
        }

        // Community Services
        $services = CommunityService::with('assignedBy')
            ->where('student_id', $this->studentId)
            ->when($filterCycle && $activeCycle, fn($q) => $q->where('cycle_id', $activeCycle->id))
            ->get();

        foreach ($services as $s) {
            $items->push([
                'type' => 'service',
                'date' => $s->scheduled_date ? $s->scheduled_date->format('Y-m-d') : null,
                'date_display' => $s->scheduled_date ? $s->scheduled_date->isoFormat('D [de] MMMM, YYYY') : 'Sin fecha',
                'title' => $s->activity,
                'description' => $s->description,
                'extra' => $s->assignedBy?->name ?? '',
                'status' => $s->status,
            ]);
        }

        // Citations
        $citations = Citation::with('teacher')
            ->where('student_id', $this->studentId)
            ->when($filterCycle && $activeCycle, fn($q) => $q->where('cycle_id', $activeCycle->id))
            ->get();

        foreach ($citations as $c) {
            $items->push([
                'type' => 'citation',
                'date' => $c->citation_date ? $c->citation_date->format('Y-m-d') : null,
                'date_display' => $c->citation_date ? $c->citation_date->isoFormat('D [de] MMMM, YYYY') : 'Sin fecha',
                'title' => $c->reason,
                'description' => '',
                'extra' => $c->teacher?->name ?? '',
                'status' => $c->status,
            ]);
        }

        $this->items = $items->sortByDesc('date')->values()->toArray();
    }
    public function render(): mixed
    {
        return view('livewire.students.history-modal');
    }
}; ?>

<flux:modal wire:model="show" class="w-full max-w-3xl">
    <div class="space-y-6">
        <header>
            <flux:heading size="lg">Historial del Alumno</flux:heading>
            <flux:text class="uppercase font-bold">{{ $studentName }}</flux:text>
        </header>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex flex-wrap gap-3">
                <div class="flex items-center gap-1.5">
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                    <span class="text-xs text-zinc-600 dark:text-zinc-400">Reportes</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                    <span class="text-xs text-zinc-600 dark:text-zinc-400">Servicios Comunitarios</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                    <span class="text-xs text-zinc-600 dark:text-zinc-400">Citatorios</span>
                </div>
            </div>
            <flux:switch wire:model.live="onlyActiveCycle" label="Solo ciclo activo" />
        </div>

        @if(count($items) === 0)
            <div class="py-12 text-center border border-dashed rounded-2xl border-zinc-300 dark:border-zinc-700">
                <flux:icon icon="check-circle" class="mx-auto text-emerald-400 mb-3" size="xl" />
                <flux:heading size="md" class="text-zinc-400">Sin historial</flux:heading>
                <flux:text size="sm" class="text-zinc-500">Este alumno no tiene reportes, servicios comunitarios ni citatorios registrados en el ciclo activo.</flux:text>
            </div>
        @else
            <div class="max-h-[60vh] overflow-y-auto pr-1 space-y-6">
                @php
                    $grouped = collect($items)->groupBy('date');
                @endphp

                @foreach($grouped as $date => $dateItems)
                    <div class="space-y-3">
                        <!-- Date Header -->
                        <div class="flex items-center gap-2 px-1 sticky top-0 bg-white dark:bg-zinc-800 py-1 z-10">
                            <flux:badge color="zinc" size="sm" inset="left">
                                {{ $date ? \Carbon\Carbon::parse($date)->isoFormat('dddd') : 'N/A' }}
                            </flux:badge>
                            <flux:text size="sm" class="font-bold">
                                {{ $dateItems->first()['date_display'] }}
                            </flux:text>
                        </div>

                        @foreach($dateItems as $item)
                            @if($item['type'] === 'report')
                                <div class="p-4 rounded-xl border border-red-200 dark:border-red-900/50 bg-red-50 dark:bg-red-900/20">
                                    <div class="flex items-center gap-2 mb-2">
                                        <flux:icon icon="document-text" class="text-red-600" />
                                        <flux:badge size="xs" color="red" variant="outline">Reporte</flux:badge>
                                    </div>
                                    <div class="font-bold text-red-900 dark:text-red-100">{{ $item['title'] }}</div>
                                    @if($item['description'])
                                        <div class="text-xs text-red-600 dark:text-red-400 mt-1 line-clamp-2 italic">"{{ $item['description'] }}"</div>
                                    @endif
                                    @if($item['extra'])
                                        <div class="text-xs text-red-500 dark:text-red-400 mt-2">
                                            Reportado por: {{ $item['extra'] }}
                                        </div>
                                    @endif
                                    <div class="mt-2">
                                        <flux:badge size="sm" color="{{ $item['status'] === 'SIGNED' ? 'green' : 'amber' }}">
                                            {{ $item['status'] === 'SIGNED' ? 'Firmado' : 'Pendiente firma' }}
                                        </flux:badge>
                                    </div>
                                </div>
                            @elseif($item['type'] === 'service')
                                <div class="p-4 rounded-xl border border-green-200 dark:border-green-900/50 bg-green-50 dark:bg-green-900/20">
                                    <div class="flex items-center gap-2 mb-2">
                                        <flux:icon icon="briefcase" class="text-green-600" />
                                        <flux:badge size="xs" color="green" variant="outline">Servicio Comunitario</flux:badge>
                                    </div>
                                    <div class="font-bold text-green-900 dark:text-green-100">{{ $item['title'] }}</div>
                                    @if($item['description'])
                                        <div class="text-xs text-green-600 dark:text-green-400 mt-2 italic">{{ $item['description'] }}</div>
                                    @endif
                                    @if($item['extra'])
                                        <div class="text-xs text-green-500 dark:text-green-400 mt-2">
                                            Asignado por: {{ $item['extra'] }}
                                        </div>
                                    @endif
                                    <div class="mt-2">
                                        <flux:badge size="sm" color="{{ $item['status'] === 'COMPLETED' ? 'green' : ($item['status'] === 'PENDING' ? 'amber' : 'red') }}">
                                            {{ $item['status'] === 'COMPLETED' ? 'Completado' : ($item['status'] === 'PENDING' ? 'Pendiente' : 'Incumplido') }}
                                        </flux:badge>
                                    </div>
                                </div>
                            @elseif($item['type'] === 'citation')
                                <div class="p-4 rounded-xl border border-amber-200 dark:border-amber-900/50 bg-amber-50 dark:bg-amber-900/20">
                                    <div class="flex items-center gap-2 mb-2">
                                        <flux:icon icon="calendar-days" class="text-amber-600" />
                                        <flux:badge size="xs" color="amber" variant="outline">Citatorio</flux:badge>
                                    </div>
                                    <div class="font-bold text-amber-900 dark:text-amber-100">{{ $item['title'] }}</div>
                                    @if($item['extra'])
                                        <div class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                                            Solicitado por: {{ $item['extra'] }}
                                        </div>
                                    @endif
                                    <div class="mt-2">
                                        <flux:badge size="sm" color="{{ $item['status'] === 'ATTENDED' ? 'green' : ($item['status'] === 'PENDING' ? 'amber' : 'red') }}">
                                            {{ $item['status'] === 'ATTENDED' ? 'Asistió' : ($item['status'] === 'PENDING' ? 'Agendado' : 'No asistió') }}
                                        </flux:badge>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif

        <div class="flex justify-end pt-2">
            <flux:button wire:click="$set('show', false)">Cerrar</flux:button>
        </div>
    </div>
</flux:modal>
