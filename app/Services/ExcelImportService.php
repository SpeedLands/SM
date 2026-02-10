<?php

namespace App\Services;

use App\Models\ClassGroup;
use App\Models\Cycle;
use App\Models\Student;
use App\Models\StudentCycleAssociation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExcelImportService
{
    /**
     * Get information about all sheets in the file.
     */
    public function getSheetsInfo(UploadedFile $file): array
    {
        // Load the spreadsheet to get actual sheet names
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
        $sheetNames = $spreadsheet->getSheetNames();

        // Get the data from all sheets
        $sheets = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray
        {
            public function array(array $array)
            {
                return $array;
            }
        }, $file);

        $info = [];
        foreach ($sheets as $index => $rows) {
            $info[] = [
                'index' => $index,
                'name' => $sheetNames[$index] ?? 'Sheet '.($index + 1),
                'rows_count' => count($rows),
                'header' => $rows[0] ?? [],
                'preview' => array_slice($rows, 1, 5),
            ];
        }

        return $info;
    }

    /**
     * Get column mappings for a specific import type.
     */
    public function getColumnMappings(string $type): array
    {
        return match ($type) {
            'TEACHERS' => [
                ['index' => 0, 'label' => 'Nombre', 'field' => 'name', 'required' => true],
                ['index' => 1, 'label' => 'Correo', 'field' => 'email', 'required' => true],
                ['index' => 2, 'label' => 'Contraseña', 'field' => 'password', 'required' => false],
                ['index' => 3, 'label' => 'Rol', 'field' => 'role', 'required' => false],
            ],
            'PARENTS' => [
                ['index' => 0, 'label' => 'Nombre', 'field' => 'name', 'required' => true],
                ['index' => 1, 'label' => 'Correo', 'field' => 'email', 'required' => true],
                ['index' => 2, 'label' => 'Teléfono', 'field' => 'phone', 'required' => false],
                ['index' => 3, 'label' => 'Contraseña', 'field' => 'password', 'required' => false],
                ['index' => 4, 'label' => 'Rol', 'field' => 'role', 'required' => false],
                ['index' => 5, 'label' => 'Ocupación', 'field' => 'occupation', 'required' => false],
            ],
            'STUDENTS' => [
                ['index' => 0, 'label' => 'Nombre', 'field' => 'name', 'required' => true],
                ['index' => 1, 'label' => 'Turno', 'field' => 'turn', 'required' => false],
                ['index' => 2, 'label' => 'Grado/Grupo', 'field' => 'group', 'required' => false],
                ['index' => 3, 'label' => 'Dirección', 'field' => 'address', 'required' => false],
                ['index' => 4, 'label' => 'Teléfono', 'field' => 'phone', 'required' => false],
                ['index' => 5, 'label' => 'Otro Contacto', 'field' => 'other_contact', 'required' => false],
            ],
        };
    }

    /**
     * Import data based on type and sheet.
     */
    public function import(UploadedFile $file, string $type, int $sheetIndex, ?int $parentSheetIndex = null): array
    {
        $sheets = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray
        {
            public function array(array $array)
            {
                return $array;
            }
        }, $file);

        if (! isset($sheets[$sheetIndex])) {
            throw new \Exception("Sheet index {$sheetIndex} not found.");
        }

        $rows = collect($sheets[$sheetIndex]);
        // Remove header
        $header = $rows->shift();

        return DB::transaction(function () use ($rows, $type, $sheets, $parentSheetIndex) {
            $stats = match ($type) {
                'TEACHERS' => $this->importTeachers($rows),
                'PARENTS' => $this->importParents($rows),
                'STUDENTS' => $this->importStudents($rows, $parentSheetIndex !== null ? collect($sheets[$parentSheetIndex]) : null),
                default => throw new \Exception("Invalid import type: {$type}"),
            };

            // Post-import: Check for students missing parents in the active cycle
            if ($type === 'STUDENTS' || $type === 'PARENTS') {
                $stats['action_items'] = array_merge(
                    $stats['action_items'] ?? [],
                    $this->getIncompleteStudentActionItems()
                );
            }

            return $stats;
        });
    }

    private function getIncompleteStudentActionItems(): array
    {
        $activeCycle = Cycle::where('is_active', true)->first();
        if (! $activeCycle) {
            return [];
        }

        $incompleteStudents = Student::whereHas('cycleAssociations', function ($q) use ($activeCycle) {
            $q->where('cycle_id', $activeCycle->id);
        })
            ->withCount(['parents as father_count' => function ($q) {
                $q->where('relationship', 'PADRE');
            }])
            ->withCount(['parents as mother_count' => function ($q) {
                $q->where('relationship', 'MADRE');
            }])
            ->get()
            ->filter(function ($student) {
                return $student->father_count == 0 || $student->mother_count == 0;
            });

        $items = [];
        foreach ($incompleteStudents as $student) {
            $missing = [];
            if ($student->father_count == 0) {
                $missing[] = 'PADRE';
            }
            if ($student->mother_count == 0) {
                $missing[] = 'MADRE';
            }

            $items[] = [
                'type' => 'INCOMPLETE_STUDENT',
                'title' => "Alumno: {$student->name}",
                'message' => 'Le falta registrar: '.implode(' y ', $missing),
                'student_id' => $student->id,
            ];
        }

        return $items;
    }

    protected function importTeachers(Collection $rows): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'errors' => 0];

        foreach ($rows as $row) {
            try {
                // Excel Structure: Nombre | Correo | Contraseña | Rol
                // Index: 0, 1, 2, 3

                $name = $row[0] ?? null;
                $email = $row[1] ?? null;
                $password = $row[2] ?? null;
                $roleLabel = $row[3] ?? '';

                if (! $email || ! $name) {
                    continue;
                }

                // Normalize Role
                $role = match (strtoupper(trim($roleLabel))) {
                    'ADMINISTRADOR', 'ADMISTRADOR', 'DIRECTOR' => 'ADMIN',
                    default => 'TEACHER', // Default to teacher
                };

                $user = User::where('email', $email)->first();

                if ($user) {
                    $user->update([
                        'name' => $name,
                        'role' => $role,
                        // Update password only if provided and different?
                        // For safety, let's update it if provided in the "import" logic usually implies synchronization
                        'password' => $password ? Hash::make($password) : $user->password,
                    ]);
                    $stats['updated']++;
                } else {
                    User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make($password ?: Str::random(10)),
                        'role' => $role,
                        'status' => 'ACTIVE',
                    ]);
                    $stats['created']++;
                }

            } catch (\Exception $e) {
                $stats['errors']++;
            }
        }

        return $stats;
    }

    protected function importParents(Collection $rows): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'errors' => 0, 'action_items' => []];

        // Remove header if it was passed raw, but import() handles that.
        // Excel Structure: Nombre | Correo | Teléfono | Contraseña | Rol | Ocupación
        // Index: 0, 1, 2, 3, 4, 5

        foreach ($rows as $row) {
            try {
                $name = $row[0] ?? null;
                $email = $row[1] ?? null;
                $phone = $row[2] ?? null;
                $password = $row[3] ?? null;
                // $role = $row[4]; // Should be Parent
                $occupation = $row[5] ?? null;

                if (! $email || ! $name) {
                    continue;
                }

                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'phone' => $phone,
                        'password' => $password ? Hash::make($password) : Hash::make(Str::random(10)), // Will define password only on creation if not exists, distinct logic than update usually? method above was specific.
                        'role' => 'PARENT',
                        'occupation' => $occupation,
                        'status' => 'ACTIVE',
                    ]
                );

                if ($user->wasRecentlyCreated) {
                    $stats['created']++;
                } else {
                    $stats['updated']++;
                }

                // Try linking if name contains "Padre/Madre de"
                if (! $this->linkParentByDescription($user)) {
                    $stats['action_items'][] = [
                        'type' => 'UNLINKED_PARENT',
                        'title' => "Padre sin vínculo: {$user->name}",
                        'message' => 'No se pudo identificar automáticamente al alumno para este padre.',
                        'user_id' => $user->id,
                    ];
                }

            } catch (\Exception $e) {
                $stats['errors']++;
            }
        }

        return $stats;
    }

    protected function importStudents(Collection $rows, ?Collection $parentRows = null): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'errors' => 0, 'action_items' => []];
        $activeCycle = Cycle::where('is_active', true)->firstOrFail();

        // If parent rows provided, index them by Student Name found in "Padre de [STUDENT NAME]"
        $parentsByStudentName = [];
        if ($parentRows) {
            $parentHeader = $parentRows->shift();
            foreach ($parentRows as $pRow) {
                // Identify student name and relationship from parent name "Padre de ..."
                $pName = $pRow[0] ?? '';
                $mapping = $this->extractRelationAndName($pName);
                if ($mapping['student_name']) {
                    $parentsByStudentName[strtoupper(trim($mapping['student_name']))][] = [
                        'row' => $pRow,
                        'relationship' => $mapping['relationship'],
                    ];
                }
            }
        }

        foreach ($rows as $row) {
            try {
                // Excel Structure: Nombre | Turno | Grado / Grupo | Dirección | Teléfono | Otro Contacto
                // Index: 0, 1, 2, 3, 4, 5

                $name = $row[0] ?? null;
                $turn = $row[1] ?? 'MATUTINO';
                $groupStr = $row[2] ?? ''; // "3A", "3B"

                if (! $name) {
                    continue;
                }

                // Parse Grade/Group
                $grade = null;
                $section = null;
                if (preg_match('/(\d+)([A-Z]+)/i', $groupStr, $matches)) {
                    $grade = $matches[1].'º'; // Append ordinal indicator to match DB convention (e.g., 3º)
                    $section = strtoupper($matches[2]);
                }

                // Find or Create ClassGroup
                $group = null;
                if ($grade && $section) {
                    $group = ClassGroup::firstOrCreate([
                        'cycle_id' => $activeCycle->id,
                        'grade' => $grade,
                        'section' => $section,
                    ]);
                }

                // Create/Update Student
                $student = Student::updateOrCreate(
                    ['name' => $name], // Matching by name is risky but standard for this level of import
                    [
                        'birth_date' => '2010-01-01', // Default birth date as it's not in Excel but required
                        'grade' => $grade,
                        'group_name' => $section, // Redundant but in model
                        'turn' => trim($turn),
                    ]
                );

                // Handle PII data
                $student->pii()->updateOrCreate(
                    ['student_id' => $student->id],
                    [
                        'address_encrypted' => $row[3] ?? null,
                        'contact_phone_encrypted' => $row[4] ?? null,
                        'other_contact_encrypted' => $row[5] ?? null,
                    ]
                );

                // Association
                if ($group) {
                    StudentCycleAssociation::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'cycle_id' => $activeCycle->id,
                        ],
                        [
                            'class_group_id' => $group->id,
                            'status' => 'ACTIVE',
                        ]
                    );
                }

                if ($student->wasRecentlyCreated) {
                    $stats['created']++;
                } else {
                    $stats['updated']++;
                }

                // Link Parents if available
                if (isset($parentsByStudentName[strtoupper(trim($name))])) {
                    foreach ($parentsByStudentName[strtoupper(trim($name))] as $pInfo) {
                        $pData = $pInfo['row'];
                        $relationship = $pInfo['relationship'];

                        // Create Parent User 'Inline'
                        $pEmail = $pData[1] ?? null;
                        if ($pEmail) {
                            $parentUser = User::firstOrCreate(
                                ['email' => $pEmail],
                                [
                                    'name' => $pData[0],
                                    'phone' => $pData[2] ?? null,
                                    'password' => Hash::make($pData[3] ?? Str::random(10)),
                                    'role' => 'PARENT',
                                    'occupation' => $pData[5] ?? null,
                                    'status' => 'ACTIVE',
                                ]
                            );

                            // Link
                            $student->parents()->syncWithoutDetaching([
                                $parentUser->id => ['relationship' => $relationship],
                            ]);
                        }
                    }
                }

            } catch (\Exception $e) {
                dump($e->getMessage());
                $stats['errors']++;
            }
        }

        return $stats;
    }

    private function linkParentByDescription(User $parent): bool
    {
        $mapping = $this->extractRelationAndName($parent->name);
        if ($mapping['student_name']) {
            $student = Student::where('name', 'LIKE', "%{$mapping['student_name']}%")->first();
            if ($student) {
                $student->parents()->syncWithoutDetaching([
                    $parent->id => ['relationship' => $mapping['relationship']],
                ]);

                return true;
            }
        }

        return false;
    }

    private function extractRelationAndName($parentName): array
    {
        // "Padre de X", "Madre de X", "Tutor de X"
        if (preg_match('/(Padre|Madre|Tutor)\s+de\s+(.+)/i', $parentName, $matches)) {
            return [
                'relationship' => strtoupper($matches[1]), // PADRE, MADRE, TUTOR
                'student_name' => trim($matches[2]),
            ];
        }

        return [
            'relationship' => 'TUTOR',
            'student_name' => null,
        ];
    }
}
