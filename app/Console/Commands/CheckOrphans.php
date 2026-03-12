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
                            {--parents : Show parents without students instead}
                            {--delete : Delete the orphan parents found (requires --parents)}';

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
            ->get(['id', 'name', 'curp', 'grade', 'group_name', 'turn']);

        if ($students->isEmpty()) {
            $this->info('✅ Todos los alumnos tienen al menos un padre/tutor asociado.');

            return self::SUCCESS;
        }

        $this->warn("⚠️  Se encontraron {$students->count()} alumno(s) sin padre/tutor asociado:");
        $this->newLine();

        $this->table(
            ['ID', 'Nombre', 'CURP', 'Grado', 'Grupo', 'Turno'],
            $students->map(fn (Student $s) => [
                $s->id,
                $s->name,
                $s->curp ?? '(sin CURP)',
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
            ->get(['id', 'name', 'email', 'created_at']);

        if ($parents->isEmpty()) {
            $this->info('✅ Todos los padres/tutores tienen al menos un alumno asociado.');

            return self::SUCCESS;
        }

        $this->warn("⚠️  Se encontraron {$parents->count()} padre(s)/tutor(es) sin alumnos asociados:");
        $this->newLine();

        $this->table(
            ['ID', 'Nombre', 'Email', 'Creado el'],
            $parents->map(fn (User $u) => [
                $u->id,
                $u->name,
                $u->email,
                $u->created_at?->format('Y-m-d'),
            ])
        );

        if ($this->option('delete')) {
            if ($this->confirm("¿Eliminar permanentemente estos {$parents->count()} padre(s) huérfano(s)?")) {
                $deleted = 0;
                foreach ($parents as $parent) {
                    $parent->students()->detach();
                    $parent->delete();
                    $deleted++;
                }
                $this->info("✅ Se eliminaron {$deleted} padre(s) huérfano(s).");
            } else {
                $this->line('Operación cancelada.');
            }
        }

        return self::SUCCESS;
    }
}
