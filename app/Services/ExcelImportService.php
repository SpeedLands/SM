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
                ['index' => 6, 'label' => 'CURP', 'field' => 'curp', 'required' => false],
            ],
        };
    }

    /**
     * Guess column mapping based on headers.
     */
    public function guessMapping(array $headers, string $type): array
    {
        $headers = array_map(fn ($h) => mb_strtolower(trim((string) $h), 'UTF-8'), $headers);
        $mappings = $this->getColumnMappings($type);
        $guessed = [];

        $synonyms = [
            'name' => ['nombre', 'alumno', 'estudiante', 'nombre del alumno', 'nombre completo', 'parent', 'padre', 'madre', 'tutor'],
            'email' => ['correo', 'email', 'e-mail', 'correo electrónico', 'usuario'],
            'password' => ['password', 'contraseña', 'clave', 'pass'],
            'role' => ['rol', 'tipo', 'nivel'],
            'phone' => ['teléfono', 'telefono', 'celular', 'móvil', 'movil', 'tel'],
            'occupation' => ['ocupación', 'ocupacion', 'trabajo', 'oficio'],
            'turn' => ['turno', 'horario'],
            'group' => ['grado/grupo', 'grado', 'grupo', 'sección', 'seccion', 'aula'],
            'address' => ['dirección', 'direccion', 'domicilio', 'calle'],
            'other_contact' => ['otro contacto', 'contacto alternativo', 'referencia'],
            'curp' => ['curp', 'clave curp'],
        ];

        foreach ($mappings as $mapping) {
            $field = $mapping['field'];
            $fieldSynonyms = $synonyms[$field] ?? [$field];

            $found = false;
            foreach ($headers as $idx => $header) {
                if (empty($header)) {
                    continue;
                }

                foreach ($fieldSynonyms as $synonym) {
                    if (str_contains($header, $synonym) || str_contains($synonym, $header)) {
                        $guessed[$field] = $idx;
                        $found = true;
                        break 2;
                    }
                }
            }

            if (! $found) {
                $guessed[$field] = $mapping['index']; // Fallback to default index
            }
        }

        return $guessed;
    }

    /**
     * Import data based on type and sheet.
     */
    public function import(
        UploadedFile $file,
        string $type,
        int $sheetIndex,
        ?int $parentSheetIndex = null,
        array $columnMapping = [],
        ?UploadedFile $parentFile = null,
        bool $dryRun = false,
        array $parentColumnMapping = []
    ): array {
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

        // Handle separate parent file if provided
        $parentRows = null;
        if ($parentFile) {
            $parentSpreadsheet = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray
            {
                public function array(array $array)
                {
                    return $array;
                }
            }, $parentFile);

            if (isset($parentSpreadsheet[$parentSheetIndex ?? 0])) {
                $parentRows = collect($parentSpreadsheet[$parentSheetIndex ?? 0]);
            }
        } elseif ($parentSheetIndex !== null) {
            $parentRows = collect($sheets[$parentSheetIndex]);
        }

        // Increase execution time limit to 5 minutes for bulk imports
        set_time_limit(300);

        // Remove header
        $header = $rows->shift();
        if ($parentRows && ! $parentFile) {
            $parentRows->shift();
        } elseif ($parentRows && $parentFile) {
            $parentRows->shift();
        }

        if ($parentSheetIndex !== null && $sheetIndex === $parentSheetIndex) {
            throw new \Exception('No puedes seleccionar la misma hoja para alumnos y padres.');
        }

        if ($dryRun) {
            DB::beginTransaction();
        }

        try {
            $stats = DB::transaction(function () use ($rows, $type, $parentRows, $context, $columnMapping, $parentColumnMapping) {
                return match ($type) {
                    'TEACHERS' => $this->importTeachers($rows, $columnMapping),
                    'PARENTS' => $this->importParents($rows, $context['grade'], $context['section'], $columnMapping),
                    'STUDENTS' => $this->importStudents($rows, $parentRows, $context['grade'], $context['section'], $columnMapping, $parentColumnMapping),
                    default => throw new \Exception("Invalid import type: {$type}"),
                };
            });

            // Post-import: Check for students missing parents in the active cycle
            if (($type === 'STUDENTS' || $type === 'PARENTS') && isset($stats['touched_student_ids'])) {
                $stats['action_items'] = array_merge(
                    $stats['action_items'] ?? [],
                    $this->getIncompleteStudentActionItems()
                );
            }

            if ($dryRun) {
                DB::rollBack();
                $stats['is_simulation'] = true;
                $stats['notifications']['warnings'][] = [
                    'message' => 'ESTA ES UNA SIMULACIÓN. No se han guardado cambios permanentes.',
                ];
            }

            return $stats;
        } catch (\Exception $e) {
            if ($dryRun) {
                DB::rollBack();
            }
            throw $e;
        }
    }

    /**
     * Parse grade and section from sheet name (e.g. "3A", "3A MATUTINO", "TERCERO A")
     */
    public function extractGradeAndSection(string $sheetName): array
    {
        $grade = '1º'; // Default
        $section = 'A'; // Default (Valid group)

        // Normalize: remove accents, uppercase
        $normalized = strtoupper($this->stripAccents($sheetName));

        // Map ordinal words to numbers
        $search = ['PRIMERO', 'SEGUNDO', 'TERCERO', 'CUARTO', 'QUINTO', 'SEXTO'];
        $replace = ['1', '2', '3', '4', '5', '6'];
        $normalized = str_replace($search, $replace, $normalized);

        // Step 1: Find the first continuous sequence of digits
        if (preg_match('/(\d+)/', $normalized, $numMatches, PREG_OFFSET_CAPTURE)) {
            $gradeNum = $numMatches[0][0];
            $grade = $gradeNum.'º';
            $offset = $numMatches[0][1] + strlen($gradeNum);
            $after = substr($normalized, $offset);

            // Step 2: Find the first single isolated letter after the number
            // Skips noise like "GRADO", "ERO", "°", "º", etc. by looking for a \bWordBoundary\b
            if (preg_match('/(?:[^A-Z]|\b[A-Z]{2,}\b)*\b([A-Z])\b/i', $after, $letterMatches)) {
                $section = strtoupper($letterMatches[1]);
            }
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

    protected function importTeachers(Collection $rows, array $columnMapping): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'total' => 0, 'errors' => 0];

        foreach ($rows as $row) {
            try {
                $emailIdx = $columnMapping['email'] ?? 1;
                $nameIdx = $columnMapping['name'] ?? 0;
                $passwordIdx = $columnMapping['password'] ?? 2;
                $roleIdx = $columnMapping['role'] ?? 3;

                $email = $this->sanitizeEmail($row[$emailIdx] ?? '');

                // Silent skip for headers or empty rows
                if (empty($email) || in_array(strtolower($email), ['correo', 'email', 'e-mail'])) {
                    continue;
                }

                $name = $row[$nameIdx] ?? null;
                $password = $row[$passwordIdx] ?? null;
                $roleLabel = $row[$roleIdx] ?? '';

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

                    // Optimization: Direct update without redundant check to save time
                    if ($password) {
                        $updateData['password'] = Hash::make((string) $password);
                        $updateData['plain_password'] = (string) $password;
                    }

                    $user->update($updateData);
                    $stats['updated']++;
                } else {
                    $plainPassword = $password ?: Str::random(10);
                    User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make($plainPassword),
                        'plain_password' => $plainPassword,
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

    public function importParents(Collection $rows, string $currentGrade, string $currentSection, array $columnMapping): array
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

        $emailIdx = $columnMapping['email'] ?? 1;
        $nameIdx = $columnMapping['name'] ?? 0;
        $phoneIdx = $columnMapping['phone'] ?? 2;
        $passwordIdx = $columnMapping['password'] ?? 3;
        $occupationIdx = $columnMapping['occupation'] ?? 5;

        // Step 1: Group by email
        $parentsByEmail = [];
        foreach ($rows as $index => $row) {
            $email = $this->sanitizeEmail($row[$emailIdx] ?? '');

            // Silent skip for headers or empty rows
            if (empty($email) || in_array(strtolower($email), ['correo', 'email', 'e-mail', 'mail', 'turno', 'grado', 'grupo', 'direccion'])) {
                continue;
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // If it looks like a header (e.g. contains words but no @), just skip without error
                if (! str_contains($email, '@') && strlen($email) > 2) {
                    continue;
                }

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
                'name' => $row[$nameIdx] ?? null,
                'email' => $email,
                'phone' => $this->sanitizePhone($row[$phoneIdx] ?? null),
                'password' => $row[$passwordIdx] ?? null,
                'occupation' => $row[$occupationIdx] ?? null,
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

            // Optimization: If there are many children, summarize to avoid SQL truncation error
            $childrenCountTotal = count($allChildrenNames);
            if ($childrenCountTotal > 3) {
                $firstThree = array_slice($allChildrenNames, 0, 3);
                $childrenList = implode(', ', $firstThree).' y '.($childrenCountTotal - 3).' más';
                $concatenatedName = ucfirst(strtolower($relationship))." de {$childrenCountTotal} alumnos ({$childrenList})";
            } else {
                $childrenList = implode(', ', $allChildrenNames);
                $concatenatedName = ucfirst(strtolower($relationship)).' de '.$childrenList;
            }

            // Final safety: Truncate to 255 characters (standard Laravel string limit)
            $concatenatedName = Str::limit($concatenatedName, 255, '');

            // Step 4: Create/Update Parent User
            if (! $user) {
                $plainPassword = $password ?: Str::random(10);
                $user = User::create([
                    'email' => $email,
                    'name' => $concatenatedName,
                    'phone' => $phone,
                    'occupation' => $occupation,
                    'role' => 'PARENT',
                    'status' => 'ACTIVE',
                    'password' => Hash::make($plainPassword),
                    'plain_password' => $plainPassword,
                ]);
                $report['summary']['parents']['created']++;
            } else {
                // PROTECTION: Do not update name or password if user is ADMIN or TEACHER
                if (in_array($user->role, ['ADMIN', 'TEACHER'])) {
                    $report['notifications']['success'][] = [
                        'type' => 'staff_parent',
                        'message' => "Personal ({$user->role}) identificado como padre: {$user->name}",
                        'user_name' => $user->name,
                        'user_email' => $user->email,
                        'user_role' => $user->role,
                        'children' => $childrenNames,
                        'action' => 'Perfil protegido. Alumnos vinculados correctamente.',
                    ];
                } else {
                    $user->name = $concatenatedName;
                    
                    if (!empty($phone)) {
                        $user->phone = $phone;
                    }
                    if (!empty($occupation)) {
                        $user->occupation = $occupation;
                    }
                    
                    $user->save();

                    // Optimization: Direct update without redundant check to save time
                    if ($password) {
                        $user->password = Hash::make((string) $password);
                        $user->plain_password = (string) $password;
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
        // 1. Exact match in specific group
        $student = Student::where('name', $studentName)
            ->where('grade', $grade)
            ->where('group_name', $section)
            ->first();

        if ($student) {
            return ['student' => $student, 'method' => 'exact', 'similarity' => 100];
        }

        // 2. Exact match globally (Fallback if sheet name was incorrectly parsed)
        $student = Student::where('name', $studentName)->first();
        if ($student) {
            return ['student' => $student, 'method' => 'exact_global', 'similarity' => 100];
        }

        // 3. Partial match (LIKE) in specific group
        $student = Student::where('name', 'LIKE', "%{$studentName}%")
            ->where('grade', $grade)
            ->where('group_name', $section)
            ->first();

        if ($student) {
            return ['student' => $student, 'method' => 'like', 'similarity' => 95];
        }

        // 4. Partial match (LIKE) globally
        $student = Student::where('name', 'LIKE', "%{$studentName}%")->first();
        if ($student) {
            return ['student' => $student, 'method' => 'like_global', 'similarity' => 95];
        }

        // 5. Fuzzy matching globally (85% similarity threshold)
        // Global search because typos are common and the grade/section context might be wrong.
        static $allStudentsCache = null;
        if ($allStudentsCache === null) {
            $allStudentsCache = Student::all(); // Load once per import session
        }
        $candidates = $allStudentsCache;

        $bestMatch = null;
        $bestSimilarity = 0;

        foreach ($candidates as $candidate) {
            // Optimization: skip obviously different lengths
            if (abs(strlen($studentName) - strlen($candidate->name)) > 15) {
                continue;
            }

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

    public function importStudents(Collection $rows, ?Collection $parentRows = null, string $currentGrade = '1º', string $currentSection = 'A', array $columnMapping = [], array $parentColumnMapping = []): array
    {
        // Guard: detect if user accidentally swapped sheets (parents sheet selected as students)
        $this->assertNotParentsSheet($rows, $columnMapping);

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

        $nameIdx = $columnMapping['name'] ?? 0;
        $turnIdx = $columnMapping['turn'] ?? 1;
        $groupIdx = $columnMapping['group'] ?? null;
        $addressIdx = $columnMapping['address'] ?? 3;
        $phoneIdx = $columnMapping['phone'] ?? 4;
        $otherIdx = $columnMapping['other_contact'] ?? 5;
        $curpIdx = $columnMapping['curp'] ?? 6;

        // Cache for ClassGroups to avoid redundant queries in mixed sheets
        $groupCache = [];
        $getGroup = function ($grade, $section) use (&$groupCache, $activeCycle) {
            $key = "{$grade}-{$section}";
            if (! isset($groupCache[$key])) {
                $groupCache[$key] = ClassGroup::firstOrCreate([
                    'cycle_id' => $activeCycle->id,
                    'grade' => $grade,
                    'section' => $section,
                ]);
            }

            return $groupCache[$key];
        };

        foreach ($rows as $index => $row) {
            try {
                $name = trim((string) ($row[$nameIdx] ?? ''));
                $turn = $row[$turnIdx] ?? 'MATUTINO';
                $curp = isset($row[$curpIdx]) ? trim((string) $row[$curpIdx]) : null;

                // Handle dynamic grade/section if a column is mapped
                $rowGrade = $currentGrade;
                $rowSection = $currentSection;
                if ($groupIdx !== null && isset($row[$groupIdx])) {
                    $parsed = $this->extractGradeAndSection((string) $row[$groupIdx]);
                    $rowGrade = $parsed['grade'];
                    $rowSection = $parsed['section'];
                }

                $group = $getGroup($rowGrade, $rowSection);

                // Silent skip for headers or empty rows
                if (empty($name) || in_array(strtolower($name), ['nombre', 'estudiante', 'alumno'])) {
                    continue;
                }

                // Create/Update Student - Identified by Name + Grade + Section to prevent overwriting homonyms in different groups
                $student = Student::firstOrNew([
                    'name' => $name,
                    'grade' => $rowGrade,
                    'group_name' => $rowSection,
                ]);

                if ($curp) {
                    $student->curp = strtoupper($curp);
                }

                if ($turn) {
                    $student->turn = $this->normalizeTurn($turn);
                }

                if (! $student->exists && ! $student->birth_date) {
                    $student->birth_date = '2010-01-01'; // Default
                }

                $wasRecentlyCreated = ! $student->exists;
                $student->save();

                // Handle PII data
                $pii = $student->pii()->firstOrNew(['student_id' => $student->id]);
                
                $address = trim((string) ($row[$addressIdx] ?? ''));
                if ($address !== '') {
                    $pii->address_encrypted = $address;
                }

                $cleanPhone = $this->sanitizePhone($row[$phoneIdx] ?? null);
                if ($cleanPhone !== null) {
                    $pii->contact_phone_encrypted = $cleanPhone;
                }

                $cleanOther = $this->sanitizePhone($row[$otherIdx] ?? null);
                if ($cleanOther !== null) {
                    $pii->other_contact_encrypted = $cleanOther;
                }

                $pii->save();

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

                if ($wasRecentlyCreated) {
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

        // Use the last row's grade/section for parents if it was populated from the file
        $finalGrade = $rowGrade ?? $currentGrade;
        $finalSection = $rowSection ?? $currentSection;

        // If parent rows provided (e.g. from another sheet or below), process them
        if ($parentRows && $parentRows->count() > 0) {
            $parentReport = $this->importParents($parentRows, $finalGrade, $finalSection, ! empty($parentColumnMapping) ? $parentColumnMapping : $columnMapping);

            // Merge results
            $report['summary']['parents'] = $parentReport['summary']['parents'];
            $report['summary']['links'] = $parentReport['summary']['links'];
            $report['notifications']['success'] = array_merge($report['notifications']['success'], $parentReport['notifications']['success']);
            $report['notifications']['warnings'] = array_merge($report['notifications']['warnings'], $parentReport['notifications']['warnings']);
            $report['notifications']['errors'] = array_merge($report['notifications']['errors'], $parentReport['notifications']['errors']);
            $report['details'] = $parentReport['details'];
        }

        // If parents weren't processed but we still want the sheet name output correctly
        if (! isset($report['details']['sheet_name'])) {
            $report['details'] = [
                'sheet_name' => "{$finalGrade} {$finalSection}",
            ];
        }

        return $report;
    }

    protected function extractRelationAndName($parentName): array
    {
        // Normalize accents in the prefix before matching
        $normalized = $this->stripAccents((string) $parentName);

        // Accepts variations: Padre/Papa/Papi/Mama/Madre/Tutor/Tutora/Abuelo/Abuela/Tio/Tia
        if (preg_match('/^(Padre|Papa|Papi|Mama|Madre|Tutor|Tutora|Abuelo|Abuela|Tio|Tia|Tutor Legal)\s+de\s+(.+)$/i', $normalized, $matches)) {
            // Map common aliases to standard relationship
            $alias = strtoupper($matches[1]);
            $relationship = match ($alias) {
                'PAPA', 'PAPI' => 'PADRE',
                'MAMA' => 'MADRE',
                'TIO' => 'TUTOR',
                'TIA' => 'TUTORA',
                default => $alias,
            };

            return [
                'relationship' => $relationship,
                'student_name' => trim($matches[2]),
            ];
        }

        return [
            'relationship' => 'TUTOR',
            'student_name' => null,
        ];
    }

    /**
     * Normalize a turn value to MATUTINO or VESPERTINO, tolerating typos and accents.
     */
    private function normalizeTurn(mixed $turn): string
    {
        $clean = strtoupper(trim($this->stripAccents((string) ($turn ?? ''))));

        // Explicit mapping for known variants
        $matutino = ['MATUTINO', 'MATUTIN', 'MATUINO', 'MAÑANA', 'MANANA', 'MAT', 'M', 'MORNING'];
        $vespertino = ['VESPERTINO', 'VESPERTIN', 'VESPERTNO', 'TARDE', 'VESPER', 'VES', 'V', 'AFTERNOON'];

        if (in_array($clean, $matutino, true)) {
            return 'MATUTINO';
        }

        if (in_array($clean, $vespertino, true)) {
            return 'VESPERTINO';
        }

        // Fuzzy: if it starts with M -> MATUTINO, if starts with V/T -> VESPERTINO
        if (str_starts_with($clean, 'M')) {
            return 'MATUTINO';
        }

        if (str_starts_with($clean, 'V') || str_starts_with($clean, 'T')) {
            return 'VESPERTINO';
        }

        // Default fallback
        return 'MATUTINO';
    }

    /**
     * Throw an exception if the rows look like a parents sheet (names follow "Padre de X" pattern).
     * This protects against the user accidentally swapping the student and parent sheets.
     */
    private function assertNotParentsSheet(Collection $rows, array $columnMapping = []): void
    {
        $nameIdx = $columnMapping['name'] ?? 0;
        $sample = $rows->take(10);
        $parentLikeCount = 0;

        foreach ($sample as $row) {
            $name = trim((string) ($row[$nameIdx] ?? ''));
            if (preg_match('/^(Padre|Madre|Tutor|Papa|Mama|Abuelo|Abuela)\s+de\s+/i', $name)) {
                $parentLikeCount++;
            }
        }

        // If more than half the sampled rows look like parent rows, reject
        $total = $sample->count();
        if ($total > 0 && ($parentLikeCount / $total) >= 0.5) {
            throw new \Exception(
                "La hoja seleccionada parece contener datos de PADRES (detectados {$parentLikeCount} de {$total} registros con formato \"Padre de...\"). "
                .'Por favor selecciona la hoja correcta de ALUMNOS.'
            );
        }
    }

    /**
     * Remove accent characters from a string for fuzzy comparisons.
     */
    private function stripAccents(string $text): string
    {
        $search = ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ', 'ü', 'Ü'];
        $replace = ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U', 'n', 'N', 'u', 'U'];

        return str_replace($search, $replace, $text);
    }

    /**
     * Sanitize email address by removing accents and converting special characters.
     */
    protected function sanitizeEmail($email): string
    {
        if (! $email) {
            return '';
        }

        $email = trim((string) $email);

        return strtolower($this->stripAccents($email));
    }

    /**
     * Sanitize phone numbers. Returns null if the value clearly isn't a phone number.
     */
    protected function sanitizePhone($phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $phone = trim((string) $phone);

        // If it contains a word (e.g. "PADRE", "NO TIENE", "N/A"), reject it.
        // We look for 3 or more consecutive letters.
        if (preg_match('/[a-zA-Z]{3,}/', $phone)) {
            return null;
        }

        // Keep only numbers, plus, minus, parenthesis and spaces
        $clean = preg_replace('/[^0-9\+\-\(\)\s]/', '', $phone);

        return trim($clean) === '' ? null : trim($clean);
    }
}
