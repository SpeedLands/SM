<?php

namespace App\Services;

use App\Models\Cycle;
use App\Models\Student;
use App\Services\Imports\ImportUtils;
use App\Services\Imports\ParentImporter;
use App\Services\Imports\StudentImporter;
use App\Services\Imports\TeacherImporter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Orchestrator for Excel import operations.
 *
 * Delegates to specialized importers:
 * - TeacherImporter
 * - ParentImporter
 * - StudentImporter
 */
class ExcelImportService
{
    use ImportUtils;

    public function __construct(
        protected TeacherImporter $teacherImporter = new TeacherImporter,
        protected ParentImporter $parentImporter = new ParentImporter,
        protected StudentImporter $studentImporter = new StudentImporter,
    ) {}

    /**
     * Get information about all sheets in the file.
     */
    public function getSheetsInfo(UploadedFile $file): array
    {
        if (! class_exists('Maatwebsite\Excel\Facades\Excel')) {
            throw new \Exception('La librería de Excel no está instalada o requiere que habilites la extensión "gd" en tu PHP.');
        }

        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheetNames = $spreadsheet->getSheetNames();

        $sheets = Excel::toArray(new class implements ToArray
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
                $guessed[$field] = $mapping['index'];
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
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheetNames = $spreadsheet->getSheetNames();
        $sheetName = $sheetNames[$sheetIndex] ?? '';

        $context = $this->extractGradeAndSection($sheetName);

        $sheets = Excel::toArray(new class implements ToArray
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
            $parentSpreadsheet = Excel::toArray(new class implements ToArray
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

        set_time_limit(300);

        // Remove header
        $header = $rows->shift();
        if ($parentRows) {
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
     * Delegate to TeacherImporter.
     */
    protected function importTeachers(Collection $rows, array $columnMapping): array
    {
        return $this->teacherImporter->import($rows, $columnMapping);
    }

    /**
     * Delegate to ParentImporter.
     * Public for backward compatibility with existing tests.
     */
    public function importParents(Collection $rows, string $currentGrade, string $currentSection, array $columnMapping = []): array
    {
        return $this->parentImporter->import($rows, $currentGrade, $currentSection, $columnMapping);
    }

    /**
     * Delegate to StudentImporter.
     * Public for backward compatibility with existing tests.
     */
    public function importStudents(Collection $rows, ?Collection $parentRows = null, string $currentGrade = '1º', string $currentSection = 'A', array $columnMapping = [], array $parentColumnMapping = []): array
    {
        return $this->studentImporter->import($rows, $parentRows, $currentGrade, $currentSection, $columnMapping, $parentColumnMapping);
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
            ->where(function ($query) {
                $query->whereDoesntHave('parents', function ($q) {
                    $q->where('relationship', 'PADRE');
                })
                    ->orWhereDoesntHave('parents', function ($q) {
                        $q->where('relationship', 'MADRE');
                    });
            })
            ->with(['parents' => function ($q) {
                $q->select('id', 'name')->whereIn('relationship', ['PADRE', 'MADRE']);
            }])
            ->get();

        $items = [];
        foreach ($incompleteStudents as $student) {
            $hasFather = $student->parents->contains(fn ($p) => $p->pivot->relationship === 'PADRE');
            $hasMother = $student->parents->contains(fn ($p) => $p->pivot->relationship === 'MADRE');

            $missing = [];
            if (! $hasFather) {
                $missing[] = 'PADRE';
            }
            if (! $hasMother) {
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
}
