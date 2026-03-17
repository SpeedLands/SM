<?php

namespace App\Exports;

use App\Models\ClassGroup;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ParentsExport extends StringValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithStyles, WithTitle
{
    public function __construct(
        private readonly ?string $groupId = null,
        private readonly ?string $cycleId = null,
        private readonly bool $generatePasswords = false,
        private readonly bool $includeWithStudents = false,
    ) {}

    public function collection()
    {
        $rows = collect();

        $query = User::query()->where('role', 'PARENT');

        // Apply filters based on the children
        if ($this->groupId) {
            $query->whereHas('students.cycleAssociations', function ($q) {
                $q->where('class_group_id', $this->groupId);
                if ($this->cycleId) {
                    $q->where('cycle_id', $this->cycleId);
                }
            });

            // Eager load only the students for this group
            $query->with(['students' => function ($q) {
                $q->whereHas('cycleAssociations', function ($q2) {
                    $q2->where('class_group_id', $this->groupId);
                    if ($this->cycleId) {
                        $q2->where('cycle_id', $this->cycleId);
                    }
                });
            }]);
        } elseif ($this->cycleId) {
            $query->whereHas('students.cycleAssociations', fn ($q) => $q->where('cycle_id', $this->cycleId));

            $query->with(['students' => function ($q) {
                $q->whereHas('cycleAssociations', fn ($q2) => $q2->where('cycle_id', $this->cycleId));
            }]);
        } else {
            $query->with('students');
        }

        $query->orderBy('name')
            ->get()
            ->each(function (User $parent) use ($rows) {
                $passwordColumn = $parent->plain_password ?? '';

                // Generate new passwords if requested
                if ($this->generatePasswords) {
                    $newPassword = Str::password(8, letters: true, numbers: true, symbols: false, spaces: false);
                    $parent->update([
                        'password' => Hash::make($newPassword),
                        'plain_password' => $newPassword,
                    ]);
                    $passwordColumn = $newPassword;
                }

                if ($parent->students->isEmpty()) {
                    // Parent with no linked children (or none in the selected group): export one row anyway
                    $rows->push([
                        'name' => $parent->name,
                        'email' => $parent->email,
                        'phone' => $parent->phone ?? '',
                        'password' => $passwordColumn,
                        'role' => 'PARENT',
                        'occupation' => $parent->occupation ?? '',
                    ]);

                    return;
                }

                // One row per child-parent link in the selected context
                foreach ($parent->students as $student) {
                    $pivot = $student->pivot;
                    $relationship = $pivot?->relationship ?? 'TUTOR';
                    $relationLabel = ucfirst(strtolower($relationship));

                    $rows->push([
                        'name' => "{$relationLabel} de {$student->name}",
                        'email' => $parent->email,
                        'phone' => $parent->phone ?? '',
                        'password' => $passwordColumn,
                        'role' => 'PARENT',
                        'occupation' => $parent->occupation ?? '',
                    ]);
                }
            });

        return $rows;
    }

    public function headings(): array
    {
        return ['Nombre', 'Correo', 'Teléfono', 'Contraseña', 'Rol', 'Ocupación'];
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

            if ($group) {
                $groupLabel = trim(str_replace(['º', '°'], '', $group->grade)).trim($group->section);

                return $this->includeWithStudents ? "PADRES DE FAMILIA_{$groupLabel}" : $groupLabel;
            }

            return 'Padres';
        }

        return 'Padres';
    }
}
