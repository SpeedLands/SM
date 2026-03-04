<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\ClassGroup;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function __construct(
        private readonly string $groupId,
        private readonly int $month,
        private readonly int $year,
    ) {}

    public function collection(): Collection
    {
        $group = ClassGroup::findOrFail($this->groupId);
        $students = $group->students()->orderBy('name')->get();

        $startDate = Carbon::createFromDate($this->year, $this->month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get all attendances for these students in this month
        $attendances = Attendance::whereIn('student_id', $students->pluck('id'))
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->groupBy('student_id');

        // Identify "working days" (days with at least one record in the group)
        $workingDays = Attendance::whereIn('student_id', $students->pluck('id'))
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select('date')
            ->distinct()
            ->orderBy('date')
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'));

        $rows = new Collection;

        $statusSymbols = [
            'PRESENTE' => '.',
            'FALTA' => '/',
            'JUSTIFICADO' => '|',
            'RETARDO' => '+',
            'TRABAJO_EN_CASA' => 'TC',
        ];

        foreach ($students as $student) {
            $studentAttendances = $attendances->get($student->id, new Collection)->keyBy(function ($item) {
                return $item->date->format('Y-m-d');
            });

            $row = [
                'name' => $student->name,
            ];

            foreach ($workingDays as $day) {
                $attendance = $studentAttendances->get($day);
                $row[$day] = $attendance ? ($statusSymbols[$attendance->status] ?? $attendance->status) : '';
            }

            $rows->push($row);
        }

        return $rows;
    }

    public function headings(): array
    {
        $group = ClassGroup::findOrFail($this->groupId);
        $startDate = Carbon::createFromDate($this->year, $this->month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $students = $group->students()->pluck('students.id');

        $workingDays = Attendance::whereIn('student_id', $students)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select('date')
            ->distinct()
            ->orderBy('date')
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->format('d/m'));

        return array_merge(['Nombre'], $workingDays->toArray());
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        $group = ClassGroup::findOrFail($this->groupId);
        $monthName = Carbon::createFromDate($this->year, $this->month, 1)->translatedFormat('F');

        return "Asistencia {$group->grade}{$group->section} - ".ucfirst($monthName);
    }
}
