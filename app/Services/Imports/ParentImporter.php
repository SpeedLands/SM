<?php

namespace App\Services\Imports;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Handles the import of parent users from Excel data, including
 * student matching, name concatenation, and relationship linking.
 */
class ParentImporter
{
    use ImportUtils;

    public function __construct(
        protected StudentMatcher $matcher = new StudentMatcher
    ) {}

    /**
     * Import parent rows, linking them to students in the given group context.
     */
    public function import(Collection $rows, string $currentGrade, string $currentSection, array $columnMapping = []): array
    {
        $report = $this->initReport($currentGrade, $currentSection);

        $emailIdx = $columnMapping['email'] ?? 1;
        $nameIdx = $columnMapping['name'] ?? 0;
        $phoneIdx = $columnMapping['phone'] ?? 2;
        $passwordIdx = $columnMapping['password'] ?? 3;
        $occupationIdx = $columnMapping['occupation'] ?? 5;

        $parentsByEmail = $this->groupRowsByEmail($rows, $emailIdx, $nameIdx, $phoneIdx, $passwordIdx, $occupationIdx, $report);

        foreach ($parentsByEmail as $email => $parentRows) {
            $this->processParentGroup($email, $parentRows, $currentGrade, $currentSection, $report);
        }

        return $report;
    }

    /**
     * Initialize the report structure.
     */
    protected function initReport(string $grade, string $section): array
    {
        return [
            'summary' => [
                'students' => ['created' => 0, 'updated' => 0, 'total' => 0],
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
                'sheet_name' => "{$grade}{$section}",
                'grade' => $grade,
                'section' => $section,
            ],
        ];
    }

    /**
     * Group rows by email, validating emails along the way.
     */
    protected function groupRowsByEmail(
        Collection $rows,
        int $emailIdx,
        int $nameIdx,
        int $phoneIdx,
        int $passwordIdx,
        int $occupationIdx,
        array &$report
    ): array {
        $parentsByEmail = [];

        foreach ($rows as $index => $row) {
            $email = $this->sanitizeEmail($row[$emailIdx] ?? '');

            if (empty($email) || in_array(strtolower($email), ['correo', 'email', 'e-mail', 'mail', 'turno', 'grado', 'grupo', 'direccion'])) {
                continue;
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
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

        return $parentsByEmail;
    }

    /**
     * Process a group of rows sharing the same parent email.
     */
    protected function processParentGroup(string $email, array $parentRows, string $grade, string $section, array &$report): void
    {
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

                $matchResult = $this->matcher->findInGroup(
                    $mapping['student_name'],
                    $grade,
                    $section
                );

                if ($matchResult['student']) {
                    $studentsToLink[] = $matchResult['student'];
                    $childrenNames[] = $matchResult['student']->name;

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
                'group' => "{$grade}{$section}",
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

        if (empty($studentsToLink)) {
            return;
        }

        $relationship = $relationship ?? 'TUTOR';
        $childrenNames = array_unique($childrenNames);

        $user = User::where('email', $email)->with('students')->first();
        $allChildrenNames = $childrenNames;

        if ($user && $user->students->isNotEmpty()) {
            $existingNames = $user->students->pluck('name')->toArray();
            $allChildrenNames = array_unique(array_merge($existingNames, $childrenNames));
        }

        $concatenatedName = $this->buildParentName($relationship, $allChildrenNames);

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

                if (! empty($phone)) {
                    $user->phone = $phone;
                }
                if (! empty($occupation)) {
                    $user->occupation = $occupation;
                }

                $user->save();

                if ($password) {
                    $user->password = Hash::make((string) $password);
                    $user->plain_password = (string) $password;
                    $user->save();
                }
            }

            $report['summary']['parents']['updated']++;
        }

        $report['summary']['parents']['total']++;

        foreach ($studentsToLink as $student) {
            $student->parents()->syncWithoutDetaching([
                $user->id => ['relationship' => $relationship],
            ]);
            $report['summary']['links']['successful']++;
        }

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

        $report['details']['parents_by_child_count'][$childrenCount] = ($report['details']['parents_by_child_count'][$childrenCount] ?? 0) + 1;
    }

    /**
     * Build the concatenated parent name from relationship and children names.
     */
    protected function buildParentName(string $relationship, array $allChildrenNames): string
    {
        $childrenCountTotal = count($allChildrenNames);

        if ($childrenCountTotal > 3) {
            $firstThree = array_slice($allChildrenNames, 0, 3);
            $childrenList = implode(', ', $firstThree).' y '.($childrenCountTotal - 3).' más';
            $concatenatedName = ucfirst(strtolower($relationship))." de {$childrenCountTotal} alumnos ({$childrenList})";
        } else {
            $childrenList = implode(', ', $allChildrenNames);
            $concatenatedName = ucfirst(strtolower($relationship)).' de '.$childrenList;
        }

        return Str::limit($concatenatedName, 255, '');
    }
}
