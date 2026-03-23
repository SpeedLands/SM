<?php

use App\Models\Student;
use App\Models\Attendance;
use App\Models\ClassGroup;
use App\Models\Cycle;
use Livewire\Volt\Component;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

new class extends Component
{
    public string $date = '';
    public ?string $cycle_id = null;
    public ?string $grade = null;
    public ?string $group_id = null;

    public function mount(): void
    {
        $this->authorize('teacher-or-admin');
        $this->date = date('Y-m-d');

        $activeCycle = Cycle::where('is_active', true)->first();
        if ($activeCycle) {
            $this->cycle_id = $activeCycle->id;
        }
    }

    #[On('filters-updated')]
    public function updateFilters(array $filters): void
    {
        $this->date = $filters['date'] ?? $this->date;
        $this->cycle_id = $filters['cycle_id'] ?? $this->cycle_id;
        $this->grade = $filters['grade'] ?? $this->grade;
        $this->group_id = $filters['group_id'] ?? $this->group_id;
    }

    public function setStatus(string $studentId, string $status): void
    {
        try {
            Attendance::updateOrCreate(
                ['student_id' => $studentId, 'date' => $this->formattedDate()],
                [
                    'status' => $status,
                    'entry_time' => in_array($status, ['PRESENTE', 'RETARDO']) ? now()->format('H:i') : null,
                ]
            );
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            Attendance::where([
                'student_id' => $studentId,
                'date' => $this->formattedDate()
            ])->update([
                'status' => $status,
                'entry_time' => in_array($status, ['PRESENTE', 'RETARDO']) ? now()->format('H:i') : null,
            ]);
        }

        $this->dispatch('notify', ['message' => 'Status actualizado', 'variant' => 'success']);
    }

    public function markAllPresent(): void
    {
        foreach ($this->getStudents() as $student) {
            Attendance::updateOrCreate(
                ['student_id' => $student->id, 'date' => $this->formattedDate()],
                ['status' => 'PRESENTE', 'entry_time' => now()->format('H:i')]
            );
        }

        $this->dispatch('notify', ['message' => 'Todos marcados como presentes', 'variant' => 'success']);
    }

    private function formattedDate(): string
    {
        return Carbon::parse($this->date)->toDateString();
    }

    public function getStudents()
    {
        if (!$this->cycle_id || !$this->grade || empty($this->group_id)) {
            return collect();
        }

        $group = ClassGroup::find($this->group_id);
        
        if (!$group) {
            return collect();
        }

        return $group->students()
            ->where('student_cycle_association.cycle_id', $this->cycle_id)
            ->with(['attendances' => fn($q) => $q->where('date', $this->formattedDate())])
            ->distinct()
            ->orderBy('students.name')
            ->get();
    }

    public function render(): mixed
    {
        $studentsList = $this->getStudents();
        $total = $studentsList->count();
        $presentes = $studentsList->filter(fn($s) => $s->attendances->first()?->status === 'PRESENTE')->count();
        $faltas = $studentsList->filter(fn($s) => $s->attendances->first()?->status === 'FALTA')->count();
        $retardos = $studentsList->filter(fn($s) => $s->attendances->first()?->status === 'RETARDO')->count();
        $pendientes = $studentsList->filter(fn($s) => !$s->attendances->first())->count();

        return view('livewire.attendance.index-view', compact('studentsList', 'total', 'presentes', 'faltas', 'retardos', 'pendientes'));
    }
}; ?>

<div>
    {{-- Logic is handled in render(), UI is in livewire.attendance.index-view --}}
</div>
