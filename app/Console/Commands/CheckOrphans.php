<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;

class CheckOrphans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-orphans
                            {--parents : Show parents without students instead}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List students without associated parents, or parents without associated students.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('parents')) {
            return $this->checkParentsWithoutStudents();
        }

        return $this->checkStudentsWithoutParents();
    }

    private function checkStudentsWithoutParents(): int
    {
        $students = Student::query()
            ->doesntHave('parents')
            ->orderBy('grade')
            ->orderBy('name')
            ->get(['id', 'name', 'grade', 'group_name', 'turn']);

        if ($students->isEmpty()) {
            $this->info('✅ Todos los alumnos tienen al menos un padre/tutor asociado.');

            return self::SUCCESS;
        }

        $this->warn("⚠️  Se encontraron {$students->count()} alumno(s) sin padre/tutor asociado:");
        $this->newLine();

        $this->table(
            ['ID', 'Nombre', 'Grado', 'Grupo', 'Turno'],
            $students->map(fn (Student $s) => [
                $s->id,
                $s->name,
                $s->grade,
                $s->group_name ?? '-',
                $s->turn ?? '-',
            ])
        );

        return self::SUCCESS;
    }

    private function checkParentsWithoutStudents(): int
    {
        $parents = User::query()
            ->where('role', 'PARENT')
            ->doesntHave('students')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        if ($parents->isEmpty()) {
            $this->info('✅ Todos los padres/tutores tienen al menos un alumno asociado.');

            return self::SUCCESS;
        }

        $this->warn("⚠️  Se encontraron {$parents->count()} padre(s)/tutor(es) sin alumnos asociados:");
        $this->newLine();

        $this->table(
            ['ID', 'Nombre', 'Email'],
            $parents->map(fn (User $u) => [
                $u->id,
                $u->name,
                $u->email,
            ])
        );

        return self::SUCCESS;
    }
}
