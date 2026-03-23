<?php

namespace App\Services\Imports;

use App\Models\ClassGroup;
use App\Models\Cycle;
use App\Models\Student;
use App\Models\StudentCycleAssociation;
use Illuminate\Support\Collection;

/**
 * Handles the import of students from Excel data, including PII,
 * cycle associations, and optional parent linking.
 */
class StudentImporter
{
    use ImportUtils;

    public function __construct(
        protected ParentImporter $parentImporter = new ParentImporter
    ) {}

    /**
     * Import student rows into the database.
     */
    public function import(
        Collection $rows,
        ?Collection $parentRows = null,
        string $currentGrade = '1º',
        string $currentSection = 'A',
        array $columnMapping = [],
        array $parentColumnMapping = []
    ): array {
        $this->assertNotParentsSheet($rows, $columnMapping);

        $activeCycle = Cycle::where('is_active', true)->firstOrFail();
        $report = $this->initReport();

        $nameIdx = $columnMapping['name'] ?? 0;
        $turnIdx = $columnMapping['turn'] ?? 1;
        $groupIdx = $columnMapping['group'] ?? null;
        $addressIdx = $columnMapping['address'] ?? 3;
        $phoneIdx = $columnMapping['phone'] ?? 4;
        $otherIdx = $columnMapping['other_contact'] ?? 5;
        $curpIdx = $columnMapping['curp'] ?? 6;

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

        $rowGrade = $currentGrade;
        $rowSection = $currentSection;

        foreach ($rows as $index => $row) {
            try {
                $name = trim((string) ($row[$nameIdx] ?? ''));
                $turn = $row[$turnIdx] ?? 'MATUTINO';
                $curp = isset($row[$curpIdx]) ? trim((string) $row[$curpIdx]) : null;

                if ($groupIdx !== null && isset($row[$groupIdx])) {
                    $parsed = $this->extractGradeAndSection((string) $row[$groupIdx]);
                    $rowGrade = $parsed['grade'];
                    $rowSection = $parsed['section'];
                }

                $group = $getGroup($rowGrade, $rowSection);

                if (empty($name) || in_array(strtolower($name), ['nombre', 'estudiante', 'alumno'])) {
                    continue;
                }

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
                    $student->birth_date = '2010-01-01';
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

        $finalGrade = $rowGrade ?? $currentGrade;
        $finalSection = $rowSection ?? $currentSection;

        // If parent rows provided, delegate to ParentImporter
        if ($parentRows && $parentRows->count() > 0) {
            $parentReport = $this->parentImporter->import(
                $parentRows,
                $finalGrade,
                $finalSection,
                ! empty($parentColumnMapping) ? $parentColumnMapping : $columnMapping
            );

            $report['summary']['parents'] = $parentReport['summary']['parents'];
            $report['summary']['links'] = $parentReport['summary']['links'];
            $report['notifications']['success'] = array_merge($report['notifications']['success'], $parentReport['notifications']['success']);
            $report['notifications']['warnings'] = array_merge($report['notifications']['warnings'], $parentReport['notifications']['warnings']);
            $report['notifications']['errors'] = array_merge($report['notifications']['errors'], $parentReport['notifications']['errors']);
            $report['details'] = $parentReport['details'];
        }

        if (! isset($report['details']['sheet_name'])) {
            $report['details'] = [
                'sheet_name' => "{$finalGrade} {$finalSection}",
            ];
        }

        return $report;
    }

    /**
     * Initialize the report structure.
     */
    protected function initReport(): array
    {
        return [
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
    }

    /**
     * Throw if the rows look like a parents sheet.
     */
    protected function assertNotParentsSheet(Collection $rows, array $columnMapping = []): void
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

        $total = $sample->count();
        if ($total > 0 && ($parentLikeCount / $total) >= 0.5) {
            throw new \Exception(
                "La hoja seleccionada parece contener datos de PADRES (detectados {$parentLikeCount} de {$total} registros con formato \"Padre de...\"). "
                .'Por favor selecciona la hoja correcta de ALUMNOS.'
            );
        }
    }
}
