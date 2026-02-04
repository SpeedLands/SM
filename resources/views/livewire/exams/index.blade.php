<?php

use App\Models\ExamSchedule;
use App\Models\ClassGroup;
use App\Models\Cycle;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $periodFilter = '';
    public string $gradeFilter = '';
    public string $groupFilter = '';

    // Create Modal
    public bool $showCreateModal = false;
    public string $grade = '1';
    public string $groupName = 'A';
    public string $period = '1';
    public string $subject = '';
    public string $examDate = '';
    
    protected array $daysOfWeek = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
    ];

    public function mount(): void
    {
        $this->examDate = now()->addDays(7)->format('Y-m-d');
        
        $activeCycle = Cycle::where('is_active', true)->first();
        if ($activeCycle) {
            $firstGroup = ClassGroup::where('cycle_id', $activeCycle->id)->first();
            if ($firstGroup) {
                $this->grade = $firstGroup->grade;
                $this->groupName = $firstGroup->section;
                
                // For parents, we might want to default the filters to their child's group
                if (auth()->user()->isParent()) {
                    $student = auth()->user()->students()->first();
                    if ($student) {
                        $this->gradeFilter = $student->grade;
                        $this->groupFilter = $student->group_name;
                    }
                }
            }
        }
    }

    public function saveExam(): void
    {
        if (!auth()->user()->isViewStaff()) abort(403);
        $this->validate([
            'grade' => 'required',
            'groupName' => 'required',
            'period' => 'required|in:1,2,3',
            'subject' => 'required|string|max:100',
            'examDate' => 'required|date',
        ]);

        $activeCycle = Cycle::where('is_active', true)->first();
        if (!$activeCycle) {
            $this->dispatch('notify', ['message' => 'No hay un ciclo activo.', 'variant' => 'danger']);
            return;
        }

        $date = \Carbon\Carbon::parse($this->examDate);
        $dayNum = $date->dayOfWeekIso; // 1 (Mon) to 7 (Sun)
        
        if ($dayNum > 5) {
            $this->dispatch('notify', ['message' => 'Los exámenes deben ser en días hábiles (Lunes a Viernes).', 'variant' => 'danger']);
            return;
        }

        ExamSchedule::create([
            'cycle_id' => $activeCycle->id,
            'grade' => $this->grade,
            'group_name' => $this->groupName,
            'period' => $this->period,
            'subject' => $this->subject,
            'exam_date' => $this->examDate,
            'day_of_week' => $this->daysOfWeek[$dayNum],
        ]);

        $this->showCreateModal = false;
        $this->reset(['subject']);
        $this->dispatch('notify', ['message' => 'Examen programado correctamente.']);
    }

    public function deleteExam(string $id): void
    {
        if (!auth()->user()->isViewStaff()) abort(403);
        ExamSchedule::findOrFail($id)->delete();
        $this->dispatch('notify', ['message' => 'Examen eliminado.']);
    }

    public function with(): array
    {
        $activeCycle = Cycle::where('is_active', true)->first();
        $isStaff = auth()->user()->isViewStaff();
        
        $query = ExamSchedule::query()
            ->when(auth()->user()->isParent(), function ($q) {
                $studentIds = auth()->user()->students->pluck('id');
                $students = auth()->user()->students;
                $grades = $students->pluck('grade')->unique();
                $groups = $students->pluck('group_name')->unique();
                
                $q->whereIn('grade', $grades)
                  ->whereIn('group_name', $groups);
            })
            ->when($activeCycle, fn($q) => $q->where('cycle_id', $activeCycle->id))
            ->when($this->periodFilter, fn($q) => $q->where('period', $this->periodFilter))
            ->when($this->gradeFilter, fn($q) => $q->where('grade', $this->gradeFilter))
            ->when($this->groupFilter, fn($q) => $q->where('group_name', $this->groupFilter));

        $exams = $query->orderBy('exam_date', 'asc')->get();

        // Get unique grades and groups for filters
        $availableGroups = $activeCycle ? ClassGroup::where('cycle_id', $activeCycle->id)->get() : collect();

        $isWeekend = false;
        if ($this->examDate) {
            $date = \Carbon\Carbon::parse($this->examDate);
            $isWeekend = $date->dayOfWeekIso > 5;
        }

        return [
            'exams' => $exams,
            'isStaff' => $isStaff,
            'availableGroups' => $availableGroups,
            'activeCycle' => $activeCycle,
            'isWeekend' => $isWeekend,
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <flux:heading size="lg" level="1">Calendario de Exámenes</flux:heading>
            <flux:text class="text-zinc-500">Programación de evaluaciones por trimestre.</flux:text>
        </div>
        @if($isStaff)
            <flux:button variant="primary" icon="plus" wire:click="$set('showCreateModal', true)">Programar Examen</flux:button>
        @endif
    </div>

    <!-- Filters -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <flux:select wire:model.live="periodFilter" placeholder="Todos los trimestres">
            <option value="">Todos los trimestres</option>
            <option value="1">1º Trimestre</option>
            <option value="2">2º Trimestre</option>
            <option value="3">3º Trimestre</option>
        </flux:select>

        <flux:select wire:model.live="gradeFilter" placeholder="Grado (Todos)">
            <option value="">Todos los grados</option>
            @foreach($availableGroups->pluck('grade')->unique() as $grade)
                <option value="{{ $grade }}">{{ $grade }}º Grado</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="groupFilter" placeholder="Grupo (Todos)">
            <option value="">Todos los grupos</option>
            @foreach($availableGroups->pluck('section')->unique() as $section)
                <option value="{{ $section }}">Grupo "{{ $section }}"</option>
            @endforeach
        </flux:select>
    </div>

    @if($exams->isEmpty())
        <div class="py-20 text-center border border-dashed rounded-3xl border-zinc-300 dark:border-zinc-700">
            <flux:icon icon="academic-cap" class="mx-auto text-zinc-300 mb-4" size="xl" />
            <flux:heading size="md" class="text-zinc-300">No hay exámenes programados</flux:heading>
            <flux:text>Seleccione otros filtros o contacte a la administración.</flux:text>
        </div>
    @else
        <!-- Exam Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($exams->groupBy(fn($e) => $e->exam_date->format('Y-m-d')) as $date => $dayExams)
                <div class="space-y-3">
                    <div class="flex items-center gap-2 px-1">
                        <flux:badge color="blue" size="sm" inset="left">{{ \Carbon\Carbon::parse($date)->isoFormat('dddd') }}</flux:badge>
                        <flux:text size="sm" class="font-bold">{{ \Carbon\Carbon::parse($date)->isoFormat('D [de] MMMM') }}</flux:text>
                    </div>

                    @foreach($dayExams as $exam)
                        <div wire:key="exam-{{ $exam->id }}" class="p-4 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm relative group transition-all hover:border-blue-300 hover:shadow-md">
                            <div class="flex justify-between items-start mb-2">
                                <flux:badge size="xs" color="purple" variant="outline">{{ $exam->period }}º Trimestre</flux:badge>
                                @if($isStaff)
                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                        <flux:button variant="ghost" size="sm" icon="trash" color="red" wire:click="deleteExam('{{ $exam->id }}')" />
                                    </div>
                                @endif
                            </div>

                            <flux:heading level="4" size="md" class="truncate" title="{{ $exam->subject }}">{{ $exam->subject }}</flux:heading>
                            
                            <div class="mt-3 flex items-center justify-between">
                                <div class="flex items-center gap-1.5">
                                    <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                                    <flux:text size="sm" class="font-medium">{{ $exam->grade }}º"{{ $exam->group_name }}"</flux:text>
                                </div>
                                <flux:text size="xs" class="text-zinc-500">{{ $exam->exam_date->format('H:i') == '00:00' ? '' : $exam->exam_date->format('H:i').' hrs' }}</flux:text>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif

    <!-- Create Modal -->
    <flux:modal wire:model.self="showCreateModal" class="md:w-160">
        <form wire:submit="saveExam" class="space-y-6">
            <header>
                <flux:heading size="md">Programar Examen</flux:heading>
                <flux:text>Ingrese los detalles de la evaluación académica.</flux:text>
            </header>

            <div class="space-y-4">
                @if($isWeekend)
                    <flux:callout variant="danger" icon="exclamation-triangle">
                        La fecha seleccionada es fin de semana. Los exámenes deben programarse de Lunes a Viernes.
                    </flux:callout>
                @endif
                
                <flux:input wire:model.live="subject" label="Nombre de la Materia" placeholder="Ej: Matemáticas I, Historia, Geografía..." />

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="grade" label="Grado">
                        @foreach($availableGroups->pluck('grade')->unique() as $g)
                            <option value="{{ $g }}">{{ $g }}º Grado</option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="groupName" label="Grupo">
                        @foreach($availableGroups->pluck('section')->unique() as $s)
                            <option value="{{ $s }}">Sección "{{ $s }}"</option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="period" label="Trimestre">
                        <option value="1">1º Trimestre</option>
                        <option value="2">2º Trimestre</option>
                        <option value="3">3º Trimestre</option>
                    </flux:select>
                    <flux:input type="date" wire:model.live="examDate" label="Fecha del Examen" />
                </div>
                
                <flux:text size="xs" class="text-zinc-500 italic">El día de la semana se calculará automáticamente.</flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button wire:click="$set('showCreateModal', false)">Cancelar</flux:button>
                <flux:button variant="primary" type="submit">Guardar Examen</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
