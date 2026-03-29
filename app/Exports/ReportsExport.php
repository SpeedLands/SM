<?php

namespace App\Exports;

use App\Models\Cycle;
use App\Models\Report;
use App\Models\StudentCycleAssociation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function __construct(
        private readonly ?string $groupId = null,
        private readonly ?string $studentId = null,
        private readonly bool $allCycles = false,
    ) {}

    public function collection()
    {
        $activeCycle = Cycle::where('is_active', true)->first();
        $query = Report::with(['student', 'infraction', 'teacher', 'cycle']);

        if ($this->groupId && $activeCycle) {
            // "solo los que esten activos en el ciclo actual"
            $studentIds = StudentCycleAssociation::where('class_group_id', $this->groupId)
                ->where('cycle_id', $activeCycle->id)
                ->pluck('student_id');

            $query->whereIn('student_id', $studentIds)
                ->where('cycle_id', $activeCycle->id);
        } elseif ($this->studentId) {
            $query->where('student_id', $this->studentId);

            if (! $this->allCycles && $activeCycle) {
                $query->where('cycle_id', $activeCycle->id);
            }
        }

        return $query->latest('date')
            ->get()
            ->map(fn (Report $report) => [
                'date' => $report->date->format('d/m/Y H:i'),
                'student' => $report->student->name,
                'cycle' => $report->cycle?->name ?? 'N/A',
                'infraction' => $report->infraction->description,
                'severity' => match ($report->infraction->severity) {
                    'GRAVE' => 'Grave',
                    'NORMAL' => 'Normal',
                    default => $report->infraction->severity
                },
                'subject' => $report->subject ?: 'N/A',
                'description' => $report->description,
                'teacher' => $report->teacher?->name ?? 'N/A',
                'status' => $report->status === 'SIGNED' ? 'Firmado' : 'Pendiente',
            ]);
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Alumno',
            'Ciclo Escolar',
            'Infracción',
            'Gravedad',
            'Asunto/Materia',
            'Descripción',
            'Docente',
            'Estado de Firma',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Reportes Disciplinarios';
    }
}
