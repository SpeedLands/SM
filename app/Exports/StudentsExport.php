<?php

namespace App\Exports;

use App\Models\ClassGroup;
use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentsExport extends StringValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithStyles, WithTitle
{
    public function __construct(
        private readonly ?string $groupId = null,
        private readonly ?string $cycleId = null,
    ) {}

    public function collection()
    {
        $query = Student::query()->with('pii');

        if ($this->groupId) {
            $group = ClassGroup::find($this->groupId);

            if ($group) {
                $query->whereHas('cycleAssociations', function ($q) {
                    $q->where('class_group_id', $this->groupId);

                    if ($this->cycleId) {
                        $q->where('cycle_id', $this->cycleId);
                    }
                });
            }
        } elseif ($this->cycleId) {
            $query->whereHas('cycleAssociations', fn ($q) => $q->where('cycle_id', $this->cycleId));
        }

        return $query
            ->orderBy('grade')
            ->orderBy('group_name')
            ->orderBy('name')
            ->get()
            ->map(fn (Student $student) => [
                'name' => $student->name,
                'turn' => $student->turn,
                'group' => trim(str_replace(['º', '°'], '', $student->grade)).trim($student->group_name),
                'address' => $student->pii?->address_encrypted ?? '',
                'phone' => $student->pii?->contact_phone_encrypted ?? '',
                'other_contact' => $student->pii?->other_contact_encrypted ?? '',
                'curp' => $student->curp ?? '',
            ]);
    }

    public function headings(): array
    {
        return ['Nombre', 'Turno', 'Grado/Grupo', 'Dirección', 'Teléfono', 'Otro Contacto', 'CURP'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        if ($this->groupId) {
            $group = ClassGroup::find($this->groupId);

            return $group ? trim(str_replace(['º', '°'], '', $group->grade)).trim($group->section) : 'Alumnos';
        }

        return 'Alumnos';
    }
}
