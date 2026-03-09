<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\ClassGroup;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceExport implements FromView, WithStyles
{
    public function __construct(
        private readonly string $groupId,
        private readonly int $month,
        private readonly int $year,
    ) {}

    public function view(): View
    {
        $group = ClassGroup::with('cycle')->findOrFail($this->groupId);
        $students = $group->students()->orderBy('name')->get();

        $startDate = Carbon::createFromDate($this->year, $this->month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $daysInMonth = [];
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            if ($currentDate->isWeekday()) {
                $daysInMonth[] = $currentDate->copy();
            }
            $currentDate->addDay();
        }

        // Get all attendances for these students in this month
        $attendancesQuery = Attendance::whereIn('student_id', $students->pluck('id'))
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $attendances = [];
        foreach ($attendancesQuery as $att) {
            $attendances[$att->student_id][$att->date->format('Y-m-d')] = $att;
        }

        // Identify "working days" (days with at least one record in the group)
        $workingDays = Attendance::whereIn('student_id', $students->pluck('id'))
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select('date')
            ->distinct()
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))
            ->toArray();

        $statusSymbols = [
            'PRESENTE' => '.',
            'FALTA' => '|',
            'JUSTIFICADO' => 'J',
            'RETARDO' => '+',
            'TRABAJO_EN_CASA' => 'TC',
        ];

        return view('exports.attendance', [
            'group' => $group,
            'cycleName' => $group->cycle->name ?? '2025-2026',
            'monthName' => Carbon::createFromDate($this->year, $this->month, 1)->translatedFormat('F'),
            'daysInMonth' => $daysInMonth,
            'students' => $students,
            'workingDays' => $workingDays,
            'attendances' => $attendances,
            'statusSymbols' => $statusSymbols,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        // Excel subtracts approximately ~0.78 from the specified width.
        // To achieve 3.44 we set 4.22 (3.44 + 0.78)
        $sheet->getColumnDimension('A')->setWidth(4.22);

        $sheet->getColumnDimension('B')->setAutoSize(true);

        $highestCol = $sheet->getHighestColumn();

        // Ensure we don't try to loop 'C' to 'B' or 'A' if there are no days
        if ($highestCol !== 'A' && $highestCol !== 'B') {
            $col = 'C';
            while ($col !== $highestCol) {
                // To achieve 2.22 we set 3.00 (2.22 + 0.78)
                $sheet->getColumnDimension($col)->setWidth(3.00);
                $col++;
            }
            $sheet->getColumnDimension($highestCol)->setWidth(3.00);
        }

        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();
        $range = 'A5:'.$highestCol.$highestRow;

        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '00000000'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A1:'.$highestCol.'4')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        return [];
    }
}
