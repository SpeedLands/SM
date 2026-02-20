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
        if (! class_exists('Maatwebsite\Excel\Facades\Excel')) {
            throw new \Exception('La librería de Excel no está instalada o requiere que habilites la extensión "gd" en tu PHP.');
        }

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
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
        $sheetNames = $spreadsheet->getSheetNames();
        $sheetName = $sheetNames[$sheetIndex] ?? '';

        // Extract grade and section from sheet name (e.g., "3A" -> "3º", "A")
        $context = $this->extractGradeAndSection($sheetName);

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

        return DB::transaction(function () use ($rows, $type, $sheets, $parentSheetIndex, $context) {
            $stats = match ($type) {
                'TEACHERS' => $this->importTeachers($rows),
                'PARENTS' => $this->importParents($rows, $context['grade'], $context['section']),
                'STUDENTS' => $this->importStudents($rows, $parentSheetIndex !== null ? collect($sheets[$parentSheetIndex]) : null, $context['grade'], $context['section']),
                default => throw new \Exception("Invalid import type: {$type}"),
            };

            // Post-import: Check for students missing parents in the active cycle
            if (($type === 'STUDENTS' || $type === 'PARENTS') && isset($stats['touched_student_ids'])) {
                $stats['action_items'] = array_merge(
                    $stats['action_items'] ?? [],
                    $this->getIncompleteStudentActionItems()
                );
            }

            return $stats;
        });
    }

    /**
     * Parse grade and section from sheet name (e.g. "3A", "3A MATUTINO", "TERCERO A")
     */
    private function extractGradeAndSection(string $sheetName): array
    {
        $grade = '1º'; // Default
        $section = 'A'; // Default (Valid group)

        if (preg_match('/(\d+)([A-Z])/i', $sheetName, $matches)) {
            $grade = $matches[1].'º';
            $section = strtoupper($matches[2]);
        }

        return [
            'grade' => $grade,
            'section' => $section,
        ];
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

                $email = trim((string) ($row[1] ?? ''));

                // Silent skip for headers or empty rows
                if (empty($email) || in_array(strtolower($email), ['correo', 'email', 'e-mail'])) {
                    continue;
                }

                $name = $row[0] ?? null;
                $password = $row[2] ?? null;
                $roleLabel = $row[3] ?? '';

                if (! $name || strtolower(trim($name)) === 'nombre') {
                    continue;
                }

                // Normalize Role
                $role = match (strtoupper(trim($roleLabel))) {
                    'ADMINISTRADOR', 'ADMISTRADOR', 'DIRECTOR' => 'ADMIN',
                    default => 'TEACHER', // Default to teacher
                };

                $user = User::where('email', $email)->first();

                if ($user) {
                    $updateData = [
                        'name' => $name,
                        'role' => $role,
                    ];

                    // Optimization: Only re-hash if provided and different
                    if ($password && ! Hash::check((string) $password, $user->password)) {
                        $updateData['password'] = Hash::make((string) $password);
                    }

                    $user->update($updateData);
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

    public function importParents(Collection $rows, string $currentGrade, string $currentSection): array
    {
        $report = [
            'summary' => [
                'students' => ['created' => 0, 'updated' => 0, 'total' => 0], // Placeholder if needed
                'parents' => ['created' => 0, 'updated' => 0, 'total' => 0, 'with_multiple_children' => 0],
                'links' => ['successful' => 0, 'failed' => 0],
            ],
            'notifications' => [
                'success' => [],
                'warnings' => [],
                'errors' => [],
            ],
            'details' => [
                'parents_by_child_count' => [],
                'sheet_name' => "{$currentGrade}{$currentSection}",
                'grade' => $currentGrade,
                'section' => $currentSection,
            ],
        ];

        // Step 1: Group by email
        $parentsByEmail = [];
        foreach ($rows as $index => $row) {
            $email = trim((string) ($row[1] ?? ''));

            // Silent skip for headers or empty rows
            if (empty($email) || in_array(strtolower($email), ['correo', 'email', 'e-mail', 'mail'])) {
                continue;
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $report['notifications']['errors'][] = [
                    'type' => 'invalid_email',
                    'message' => 'Email inválido',
                    'row' => $index + 2,
                    'value' => $email,
                    'action' => 'Fila omitida',
                ];

                continue;
            }

            if (! isset($parentsByEmail[$email])) {
                $parentsByEmail[$email] = [];
            }

            $parentsByEmail[$email][] = [
                'row_index' => $index,
                'name' => $row[0] ?? null,
                'email' => $email,
                'phone' => $row[2] ?? null,
                'password' => $row[3] ?? null,
                'occupation' => $row[5] ?? null,
            ];
        }

        // Step 2: Process each email group
        foreach ($parentsByEmail as $email => $parentRows) {
            $childrenCount = count($parentRows);

            // Check for different passwords in the same group
            $passwords = array_unique(array_filter(array_column($parentRows, 'password')));
            if (count($passwords) > 1) {
                $report['notifications']['warnings'][] = [
                    'type' => 'password_mismatch',
                    'message' => 'Contraseñas diferentes encontradas para el mismo correo',
                    'parent_email' => $email,
                    'passwords_found' => count($passwords),
                    'rows' => array_map(fn ($r) => $r['row_index'] + 2, $parentRows),
                    'action' => 'Usando la primera contraseña encontrada',
                ];
            }

            $firstRow = $parentRows[0];
            $phone = $firstRow['phone'];
            $password = $firstRow['password'];
            $occupation = $firstRow['occupation'];

            $childrenNames = [];
            $relationship = null;
            $studentsToLink = [];
            $fuzzyMatches = [];
            $notFoundChildren = [];

            foreach ($parentRows as $parentRow) {
                $mapping = $this->extractRelationAndName($parentRow['name']);

                if ($mapping['student_name']) {
                    $relationship = $relationship ?? $mapping['relationship'];

                    // Search for the student in the group context
                    $matchResult = $this->findStudentInGroupWithDetails(
                        $mapping['student_name'],
                        $currentGrade,
                        $currentSection
                    );

                    if ($matchResult['student']) {
                        $studentsToLink[] = $matchResult['student'];
                        $childrenNames[] = $matchResult['student']->name; // Use DB name for consistency

                        if ($matchResult['method'] === 'fuzzy') {
                            $fuzzyMatches[] = [
                                'searched' => $mapping['student_name'],
                                'found' => $matchResult['student']->name,
                                'similarity' => $matchResult['similarity'],
                            ];
                        }
                    } else {
                        $notFoundChildren[] = [
                            'name' => $mapping['student_name'],
                            'row' => $parentRow['row_index'] + 2,
                        ];
                    }
                }
            }

            // Report missing students
            foreach ($notFoundChildren as $notFound) {
                $report['notifications']['warnings'][] = [
                    'type' => 'student_not_found',
                    'message' => "No se encontró al alumno solicitado: \"{$notFound['name']}\"",
                    'parent_email' => $email,
                    'student_searched' => $notFound['name'],
                    'group' => "{$currentGrade}{$currentSection}",
                    'row' => $notFound['row'],
                    'suggestion' => 'Verificar que el nombre esté escrito exactamente como en la lista de alumnos.',
                ];
                $report['summary']['links']['failed']++;
            }

            // Report fuzzy matches
            foreach ($fuzzyMatches as $fuzzy) {
                $report['notifications']['success'][] = [
                    'type' => 'fuzzy_match',
                    'message' => "Vínculo sugerido por similitud ({$fuzzy['similarity']}%)",
                    'student_searched' => $fuzzy['searched'],
                    'student_found' => $fuzzy['found'],
                    'similarity' => $fuzzy['similarity'],
                    'action' => 'Vinculado automáticamente',
                ];
            }

            // Skip parent if no students could be linked at all
            if (empty($studentsToLink)) {
                continue;
            }

            // Step 3: BUILD CONCATENATED NAME
            $relationship = $relationship ?? 'TUTOR';
            $childrenNames = array_unique($childrenNames);

            // Fetch existing user to calculate cumulative name if they exist
            $user = User::where('email', $email)->with('students')->first();
            $allChildrenNames = $childrenNames;

            if ($user && $user->students->isNotEmpty()) {
                $existingNames = $user->students->pluck('name')->toArray();
                $allChildrenNames = array_unique(array_merge($existingNames, $childrenNames));
            }

            $childrenList = implode(', ', $allChildrenNames);
            $concatenatedName = ucfirst(strtolower($relationship)).' de '.$childrenList;

            // Step 4: Create/Update Parent User
            if (! $user) {
                $user = User::create([
                    'email' => $email,
                    'name' => $concatenatedName,
                    'phone' => $phone,
                    'occupation' => $occupation,
                    'role' => 'PARENT',
                    'status' => 'ACTIVE',
                    'password' => Hash::make($password ?: Str::random(10)),
                ]);
                $report['summary']['parents']['created']++;
            } else {
                // PROTECTION: Do not update name or password if user is ADMIN or TEACHER
                if (in_array($user->role, ['ADMIN', 'TEACHER'])) {
                    $report['notifications']['success'][] = [
                        'type' => 'staff_parent',
                        'message' => 'Personal administrativo/docente con hijos vinculados',
                        'user_name' => $user->name,
                        'user_role' => $user->role,
                        'parent_email' => $email,
                        'action' => 'Nombre y contraseña protegidos (se mantienen datos de personal)',
                    ];
                } else {
                    $user->update([
                        'name' => $concatenatedName,
                        'phone' => $phone,
                        'occupation' => $occupation,
                    ]);

                    // Optimization: Only re-hash if provided and different
                    if ($password && ! Hash::check((string) $password, $user->password)) {
                        $user->password = Hash::make((string) $password);
                        $user->save();
                    }
                }

                $report['summary']['parents']['updated']++;
            }

            $report['summary']['parents']['total']++;

            // Step 5: SYNC ALL CHILDREN
            foreach ($studentsToLink as $student) {
                $student->parents()->syncWithoutDetaching([
                    $user->id => ['relationship' => $relationship],
                ]);
                $report['summary']['links']['successful']++;
            }

            // Multiple children notification
            if ($childrenCount > 1) {
                $report['summary']['parents']['with_multiple_children']++;
                $report['notifications']['success'][] = [
                    'type' => 'multiple_children',
                    'message' => 'Padre con múltiples hijos identificado',
                    'parent_email' => $email,
                    'parent_name' => $concatenatedName,
                    'children_count' => $childrenCount,
                    'children' => $childrenNames,
                    'rows_processed' => array_map(fn ($r) => $r['row_index'] + 2, $parentRows),
                ];
            }

            // Distribution
            $report['details']['parents_by_child_count'][$childrenCount] = ($report['details']['parents_by_child_count'][$childrenCount] ?? 0) + 1;
        }

        return $report;
    }

    /**
     * Specialized student finder that attempts exact, partial, and fuzzy matching.
     */
    private function findStudentInGroupWithDetails(string $studentName, string $grade, string $section): array
    {
        // 1. Exact match
        $student = Student::where('name', $studentName)
            ->where('grade', $grade)
            ->where('group_name', $section)
            ->first();

        if ($student) {
            return ['student' => $student, 'method' => 'exact', 'similarity' => 100];
        }

        // 2. Partial match (LIKE)
        $student = Student::where('name', 'LIKE', "%{$studentName}%")
            ->where('grade', $grade)
            ->where('group_name', $section)
            ->first();

        if ($student) {
            return ['student' => $student, 'method' => 'like', 'similarity' => 95];
        }

        // 3. Fuzzy matching (85% similarity threshold)
        $candidates = Student::where('grade', $grade)
            ->where('group_name', $section)
            ->get();

        $bestMatch = null;
        $bestSimilarity = 0;

        foreach ($candidates as $candidate) {
            similar_text(strtoupper($studentName), strtoupper($candidate->name), $similarity);
            if ($similarity > $bestSimilarity && $similarity >= 85) {
                $bestMatch = $candidate;
                $bestSimilarity = $similarity;
            }
        }

        if ($bestMatch) {
            return ['student' => $bestMatch, 'method' => 'fuzzy', 'similarity' => round($bestSimilarity)];
        }

        return ['student' => null, 'method' => 'none', 'similarity' => 0];
    }

    public function importStudents(Collection $rows, ?Collection $parentRows = null, string $currentGrade = '1º', string $currentSection = 'A'): array
    {
        $activeCycle = Cycle::where('is_active', true)->firstOrFail();
        $report = [
            'summary' => [
                'students' => ['created' => 0, 'updated' => 0, 'total' => 0],
                'parents' => ['created' => 0, 'updated' => 0, 'total' => 0],
                'links' => ['successful' => 0, 'failed' => 0],
            ],
            'notifications' => [
                'success' => [],
                'warnings' => [],
                'errors' => [],
            ],
            'touched_student_ids' => [],
        ];

        // Find or Create ClassGroup once
        $group = ClassGroup::firstOrCreate([
            'cycle_id' => $activeCycle->id,
            'grade' => $currentGrade,
            'section' => $currentSection,
        ]);

        foreach ($rows as $index => $row) {
            try {
                // Excel Structure: Nombre | Turno | Grado / Grupo | Dirección | Teléfono | Otro Contacto
                $name = trim((string) ($row[0] ?? ''));
                $turn = $row[1] ?? 'MATUTINO';

                // Silent skip for headers or empty rows
                if (empty($name) || in_array(strtolower($name), ['nombre', 'estudiante', 'alumno'])) {
                    continue;
                }

                // Create/Update Student
                $student = Student::updateOrCreate(
                    ['name' => $name],
                    [
                        'birth_date' => '2010-01-01', // Default
                        'grade' => $currentGrade,
                        'group_name' => $currentSection,
                        'turn' => strtoupper(trim($turn)),
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

                if ($student->wasRecentlyCreated) {
                    $report['summary']['students']['created']++;
                } else {
                    $report['summary']['students']['updated']++;
                }
                $report['summary']['students']['total']++;
                $report['touched_student_ids'][] = $student->id;

            } catch (\Exception $e) {
                $report['notifications']['errors'][] = [
                    'type' => 'import_error',
                    'message' => 'Error importando alumno en fila '.($index + 2).': '.$e->getMessage(),
                    'row' => $index + 2,
                    'action' => 'Fila omitida',
                ];
            }
        }

        // If parent rows provided (e.g. from another sheet or below), process them
        if ($parentRows && $parentRows->count() > 0) {
            $parentReport = $this->importParents($parentRows, $currentGrade, $currentSection);

            // Merge results
            $report['summary']['parents'] = $parentReport['summary']['parents'];
            $report['summary']['links'] = $parentReport['summary']['links'];
            $report['notifications']['success'] = array_merge($report['notifications']['success'], $parentReport['notifications']['success']);
            $report['notifications']['warnings'] = array_merge($report['notifications']['warnings'], $parentReport['notifications']['warnings']);
            $report['notifications']['errors'] = array_merge($report['notifications']['errors'], $parentReport['notifications']['errors']);
            $report['details'] = $parentReport['details'];
        }

        return $report;
    }

    private function extractRelationAndName($parentName): array
    {
        // "Padre de X", "Madre de X", "Tutor de X"
        if (preg_match('/^(Padre|Madre|Tutor)\s+de\s+(.+)$/i', $parentName, $matches)) {
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
