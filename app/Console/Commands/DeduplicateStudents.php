<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DeduplicateStudents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:deduplicate
                            {--force : Ejecutar la deduplicación real (sin esto, sólo muestra qué se haría)}
                            {--by-name : Usar nombre normalizado + grado como fallback cuando no hay CURP}
                            {--keep-group= : El nombre del grupo que se debe PREFERIR conservar (ej. B)}
                            {--discard-group= : El nombre del grupo que se debe PREFERIR eliminar (ej. A)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detecta y elimina alumnos duplicados usando CURP como identificador único. Por defecto corre en modo dry-run (solo muestra, no borra).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = ! $this->option('force');
        $useNameFallback = $this->option('by-name');

        if ($isDryRun) {
            $this->warn('🔍 MODO DRY-RUN: No se eliminará nada. Use --force para aplicar cambios reales.');
            $this->newLine();
        }

        // --- Find duplicates by CURP ---
        $duplicatesByCurp = $this->findDuplicatesByCurp();

        if ($duplicatesByCurp->isEmpty() && ! $useNameFallback) {
            $this->info('✅ No se encontraron alumnos duplicados por CURP.');

            return self::SUCCESS;
        }

        $totalGroups = $duplicatesByCurp->count();
        $totalCopies = $duplicatesByCurp->sum(fn ($group) => $group->count() - 1);

        if ($duplicatesByCurp->isNotEmpty()) {
            $this->warn("⚠️  Se encontraron {$totalGroups} grupo(s) de duplicados por CURP ({$totalCopies} copia(s) a eliminar).");
            $this->newLine();
            $this->printDuplicateGroups($duplicatesByCurp, 'CURP');
        }

        // --- Optional fallback: find duplicates by name + grade ---
        $duplicatesByName = collect();
        if ($useNameFallback) {
            $duplicatesByName = $this->findDuplicatesByName();
            if ($duplicatesByName->isNotEmpty()) {
                $nameGroups = $duplicatesByName->count();
                $nameCopies = $duplicatesByName->sum(fn ($group) => $group->count() - 1);
                $this->warn("⚠️  Se encontraron {$nameGroups} grupo(s) de duplicados por nombre+grado ({$nameCopies} copia(s) a eliminar).");
                $this->newLine();
                $this->printDuplicateGroups($duplicatesByName, 'Nombre+Grado');
            } else {
                $this->info('✅ No se encontraron duplicados adicionales por nombre+grado.');
            }
        }

        $allDuplicateGroups = collect()->concat($duplicatesByCurp)->concat($duplicatesByName);

        if ($allDuplicateGroups->isEmpty()) {
            $this->info('✅ No se encontraron alumnos duplicados.');

            return self::SUCCESS;
        }

        if ($isDryRun) {
            $this->newLine();
            $this->info('ℹ️  Para aplicar la deduplicación, ejecuta: php artisan students:deduplicate --force');

            return self::SUCCESS;
        }

        // --- Execute deduplication ---
        $this->newLine();
        $this->info('Iniciando deduplicacion...');

        $deletedCount = 0;

        DB::transaction(function () use ($allDuplicateGroups, &$deletedCount) {
            foreach ($allDuplicateGroups as $group) {
                $original = $this->pickOriginal($group);
                $copies = $group->filter(fn ($s) => $s->id !== $original->id);

                foreach ($copies as $copy) {
                    $this->transferRelations($copy, $original);
                    $copy->delete();
                    $deletedCount++;
                    $this->line("  🗑  Eliminado: [{$copy->id}] {$copy->name} → conservado [{$original->id}]");
                }
            }
        });

        $this->newLine();
        $this->info("Deduplicacion completa. Se eliminaron {$deletedCount} alumno(s) duplicado(s).");

        return self::SUCCESS;
    }

    /** @return Collection<string, Collection<int, Student>> */
    private function findDuplicatesByCurp(): Collection
    {
        $duplicateCurps = Student::query()
            ->select('curp')
            ->whereNotNull('curp')
            ->where('curp', '!=', '')
            ->groupBy('curp')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('curp');

        if ($duplicateCurps->isEmpty()) {
            return collect();
        }

        return Student::query()
            ->whereIn('curp', $duplicateCurps)
            ->with(['parents', 'cycleAssociations'])
            ->orderBy('curp')
            ->orderBy('id')
            ->get()
            ->toBase()
            ->groupBy(fn (Student $s) => $s->curp);
    }

    /** @return Collection<string, Collection<int, Student>> */
    private function findDuplicatesByName(): Collection
    {
        $allStudents = Student::query()
            ->whereNull('curp')
            ->orWhere('curp', '')
            ->with(['parents', 'cycleAssociations'])
            ->get();

        return $allStudents
            ->toBase()
            ->groupBy(fn (Student $s) => $this->normalizeKey($s->name, $s->grade))
            ->filter(fn ($group) => $group->count() > 1);
    }

    /**
     * Among a group of duplicate students, pick the one to keep.
     * Criteria: most total relations, tiebreaker: lowest id (oldest).
     */
    private function pickOriginal(Collection $group): Student
    {
        $keepGroup = strtoupper($this->option('keep-group') ?? '');
        $discardGroup = strtoupper($this->option('discard-group') ?? '');

        return $group->sortByDesc(function (Student $s) use ($keepGroup, $discardGroup) {
            $score = 0;

            // Priority 1: Keep Group / Discard Group Rules
            $currentGroup = strtoupper($s->group_name ?? '');
            if ($keepGroup && $currentGroup === $keepGroup) {
                $score += 1000;
            }
            if ($discardGroup && $currentGroup === $discardGroup) {
                $score -= 1000;
            }

            // Priority 2: Relationships
            $parentsCount = $s->parents->count();
            $cyclesCount = $s->cycleAssociations->count();
            $score += ($parentsCount * 10) + $cyclesCount;

            // Note: tiebreaker is ID (oldest record) via sortByDesc/first
            return $score;
        })->first();
    }

    /**
     * Move relations from the copy to the original before deletion.
     */
    private function transferRelations(Student $copy, Student $original): void
    {
        // Transfer parent relationships that don't already exist on the original
        foreach ($copy->parents as $parent) {
            $alreadyLinked = $original->parents()->where('parent_id', $parent->id)->exists();
            if (! $alreadyLinked) {
                $original->parents()->attach($parent->id, [
                    'relationship' => $parent->pivot->relationship,
                ]);
                $this->line("    ↳ Transferido padre: {$parent->name} → alumno original");
            }
        }

        // Transfer cycle associations that don't already exist on the original
        foreach ($copy->cycleAssociations as $assoc) {
            $alreadyExists = $original->cycleAssociations()
                ->where('cycle_id', $assoc->cycle_id)
                ->exists();

            if (! $alreadyExists) {
                $original->cycleAssociations()->create([
                    'cycle_id' => $assoc->cycle_id,
                    'class_group_id' => $assoc->class_group_id,
                    'status' => $assoc->status,
                ]);
                $this->line("    ↳ Transferida asociación de ciclo ID:{$assoc->cycle_id}");
            }
        }
    }

    private function printDuplicateGroups(Collection $groups, string $groupedBy): void
    {
        $rows = [];

        foreach ($groups as $key => $group) {
            $original = $this->pickOriginal($group);

            foreach ($group as $student) {
                $isOriginal = $student->id === $original->id;
                $rows[] = [
                    $isOriginal ? 'CONSERVAR' : 'ELIMINAR',
                    $student->id,
                    $student->name,
                    $student->curp ?? '(sin CURP)',
                    $student->grade ?? '-',
                    $student->parents->count().' padres / '.$student->cycleAssociations->count().' ciclos',
                ];
            }

            $rows[] = ['---', '---', '---', '---', '---', '---'];
        }

        $this->table(
            ['Acción', 'ID', 'Nombre', 'CURP', 'Grado', 'Relaciones'],
            $rows,
        );
    }

    private function normalizeKey(string $name, ?string $grade): string
    {
        $normalized = mb_strtolower(trim($name), 'UTF-8');
        $normalized = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $normalized
        );
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return $normalized.'|'.strtolower($grade ?? '');
    }
}
