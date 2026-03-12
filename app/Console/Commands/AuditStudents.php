<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class AuditStudents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:audit
                            {--duplicates : Mostrar solo posibles duplicados}
                            {--shifts : Mostrar solo problemas de turno}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Realiza una auditoría completa de los datos de los alumnos buscando inconsistencias.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Iniciando auditoría de alumnos...');
        $this->newLine();

        $students = Student::all();
        $issues = collect();

        // 1. Check Shift Consistency
        if ($this->shouldRun('shifts')) {
            $this->checkShifts($students, $issues);
        }

        // 2. Check Missing Data
        if ($this->shouldRun('default')) {
            $this->checkMissingData($students, $issues);
        }

        // 3. Duplicate Detection (In-Grade and Cross-Grade)
        if ($this->shouldRun('duplicates')) {
            $this->checkDuplicates($students, $issues);
        }

        // 4. Check Orphans (No parents)
        if ($this->shouldRun('default')) {
            $this->checkOrphans($students, $issues);
        }

        if ($issues->isEmpty()) {
            $this->info('✅ No se encontraron problemas obvios en la base de datos.');

            return self::SUCCESS;
        }

        $this->printIssues($issues);

        return self::SUCCESS;
    }

    private function shouldRun(string $type): bool
    {
        if ($type === 'shifts') {
            return $this->option('shifts') || (! $this->option('duplicates') && ! $this->option('shifts'));
        }
        if ($type === 'duplicates') {
            return $this->option('duplicates') || (! $this->option('duplicates') && ! $this->option('shifts'));
        }

        return ! $this->option('duplicates') && ! $this->option('shifts');
    }

    private function checkShifts(Collection $students, Collection $issues): void
    {
        foreach ($students as $student) {
            $group = strtoupper($student->group_name ?? '');
            $turn = strtoupper($student->turn ?? '');

            if (empty($group) || empty($turn)) {
                continue;
            }

            $isMatutinoGroup = in_array($group, ['A', 'B', 'C', 'D']);
            $isVespertinoGroup = in_array($group, ['G', 'H', 'I']);

            if ($isMatutinoGroup && $turn !== 'MATUTINO') {
                $issues->push([
                    'id' => $student->id,
                    'name' => $student->name,
                    'grade' => $student->grade,
                    'group' => $group,
                    'type' => 'Turno Incorrecto',
                    'detail' => "Grupo $group debería ser MATUTINO, tiene $turn",
                ]);
            } elseif ($isVespertinoGroup && $turn !== 'VESPERTINO') {
                $issues->push([
                    'id' => $student->id,
                    'name' => $student->name,
                    'grade' => $student->grade,
                    'group' => $group,
                    'type' => 'Turno Incorrecto',
                    'detail' => "Grupo $group debería ser VESPERTINO, tiene $turn",
                ]);
            }
        }
    }

    private function checkMissingData(Collection $students, Collection $issues): void
    {
        foreach ($students as $student) {
            if (empty($student->curp)) {
                $issues->push([
                    'id' => $student->id,
                    'name' => $student->name,
                    'grade' => $student->grade,
                    'group' => $student->group_name,
                    'type' => 'Dato Faltante',
                    'detail' => 'Falta CURP',
                ]);
            }
        }
    }

    private function checkDuplicates(Collection $students, Collection $issues): void
    {
        // Check by CURP (Global)
        $byCurp = $students->whereNotNull('curp')->where('curp', '!=', '')->groupBy('curp')->filter(fn ($g) => $g->count() > 1);
        foreach ($byCurp as $curp => $group) {
            foreach ($group as $s) {
                $issues->push([
                    'id' => $s->id,
                    'name' => $s->name,
                    'grade' => $s->grade,
                    'group' => $s->group_name,
                    'type' => 'Duplicado (CURP Identica)',
                    'detail' => "CURP $curp compartida con ".($group->count() - 1).' registro(s)',
                ]);
            }
        }

        // Check by Name (Global)
        $normalized = $students->map(function ($s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'normalized' => $this->normalize($s->name),
                'grade' => $s->grade,
                'group' => $s->group_name,
            ];
        });

        $byName = $normalized->groupBy('normalized')->filter(fn ($g) => $g->count() > 1);

        foreach ($byName as $normalizedName => $group) {
            $grades = $group->pluck('grade')->unique();
            $groups = $group->pluck('group')->unique();

            if ($grades->count() > 1) {
                foreach ($group as $s) {
                    $issues->push([
                        'id' => $s['id'],
                        'name' => $s['name'],
                        'grade' => $s['grade'],
                        'group' => $s['group'],
                        'type' => 'Duplicado (Diferente Grado)',
                        'detail' => 'Mismo nombre en grados: '.$grades->implode(', '),
                    ]);
                }
            } elseif ($groups->count() > 1) {
                foreach ($group as $s) {
                    $issues->push([
                        'id' => $s['id'],
                        'name' => $s['name'],
                        'grade' => $s['grade'],
                        'group' => $s['group'],
                        'type' => 'Duplicado (Diferente Grupo)',
                        'detail' => 'Mismo nombre en grupos: '.$groups->implode(', '),
                    ]);
                }
            } else {
                foreach ($group as $s) {
                    $issues->push([
                        'id' => $s['id'],
                        'name' => $s['name'],
                        'grade' => $s['grade'],
                        'group' => $s['group'],
                        'type' => 'Duplicado (Mismo Grado/Grupo)',
                        'detail' => 'Registro idéntico duplicado en el mismo salon',
                    ]);
                }
            }
        }
    }

    private function checkOrphans(Collection $students, Collection $issues): void
    {
        foreach ($students as $student) {
            if ($student->parents->count() === 0) {
                $issues->push([
                    'id' => $student->id,
                    'name' => $student->name,
                    'grade' => $student->grade,
                    'group' => $student->group_name,
                    'type' => 'Alumno Huérfano',
                    'detail' => 'No tiene ningún padre/tutor asociado',
                ]);
            }
        }
    }

    private function normalize(string $name): string
    {
        $normalized = mb_strtolower(trim($name), 'UTF-8');
        $normalized = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $normalized
        );

        return preg_replace('/\s+/', ' ', $normalized);
    }

    private function printIssues(Collection $issues): void
    {
        $grouped = $issues->groupBy('type');

        foreach ($grouped as $type => $group) {
            $this->warn("⚠️  $type (".$group->count().')');
            $this->table(
                ['Nombre', 'Grado', 'Grupo', 'Detalle'],
                $group->map(fn ($i) => [$i['name'], $i['grade'], $i['group'], $i['detail']])->toArray()
            );
            $this->newLine();
        }
    }
}
