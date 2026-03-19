<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SyncDataFromOriginal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-data
                            {--file=ORIGINAL.xlsx : El archivo Excel original para sincronizar}
                            {--curp-col=B : Columna del CURP en el Excel (letra). Deja vacío si el Excel no tiene CURP.}
                            {--name-col=A : Columna del nombre en el Excel (letra)}
                            {--dry-run : Sólo muestra qué se eliminaría, sin borrar nada}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina alumnos y padres que no están presentes en el archivo ORIGINAL.xlsx. Usa CURP como llave primaria y nombre como fallback.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->option('file');
        $isDryRun = $this->option('dry-run');
        $curpCol = strtoupper((string) $this->option('curp-col'));
        $nameCol = strtoupper((string) $this->option('name-col'));

        if ($isDryRun) {
            $this->warn('🔍 MODO DRY-RUN: No se eliminará nada. Use sin --dry-run para aplicar cambios.');
            $this->newLine();
        }

        if (! file_exists($filePath)) {
            $this->error("El archivo no existe: {$filePath}");

            return 1;
        }

        $this->info("Cargando archivo: {$filePath}...");

        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (\Exception $e) {
            $this->error('Error cargando el archivo: '.$e->getMessage());

            return 1;
        }

        $sheet = $spreadsheet->getActiveSheet();

        // Build master map: CURP → true (primary) and name → true (fallback)
        $validCurps = [];
        $validNames = [];

        foreach ($sheet->getRowIterator(2) as $row) {
            $rowIndex = $row->getRowIndex();
            $name = trim((string) $sheet->getCell($nameCol.$rowIndex)->getValue());
            $curp = trim((string) $sheet->getCell($curpCol.$rowIndex)->getValue());

            if ($curp && strlen($curp) >= 18) {
                $validCurps[strtoupper($curp)] = true;
            }

            if ($name) {
                $validNames[$this->normalizeName($name)] = true;
            }
        }

        $hasCurpData = count($validCurps) > 0;

        $this->info('Se encontraron '.count($validNames).' alumnos válidos en el archivo maestro.');
        if ($hasCurpData) {
            $this->info('  → '.count($validCurps).' tienen CURP (se usará CURP como llave primaria).');
        } else {
            $this->warn('  → No se encontraron CURPs en la columna '.$curpCol.'. Se usará solo el nombre como criterio.');
        }

        // Scan DB
        $students = Student::all();
        $deletedCount = 0;
        $toDelete = [];

        foreach ($students as $student) {
            $studentCurp = strtoupper(trim((string) $student->curp));
            $normName = $this->normalizeName($student->name);

            if ($hasCurpData && $studentCurp) {
                // Primary match: by CURP
                if (! isset($validCurps[$studentCurp])) {
                    $toDelete[] = $student;
                }
            } else {
                // Fallback match: by normalized name
                if (! isset($validNames[$normName])) {
                    $toDelete[] = $student;
                }
            }
        }

        if (empty($toDelete)) {
            $this->info('✅ Todos los alumnos en BD coinciden con el archivo maestro. Nada que eliminar.');
        } else {
            $this->warn('Se encontraron '.count($toDelete).' alumno(s) que NO están en el archivo maestro:');
            $this->table(
                ['ID', 'Nombre', 'CURP', 'Grado'],
                array_map(
                    fn ($s) => [$s->id, $s->name, $s->curp ?? '(sin CURP)', $s->grade ?? '-'],
                    $toDelete
                )
            );

            if (! $isDryRun) {
                foreach ($toDelete as $student) {
                    $this->warn("Eliminando alumno: {$student->name} (CURP: {$student->curp})");
                    $student->delete();
                    $deletedCount++;
                }
                $this->info("Total de alumnos eliminados: {$deletedCount}");
            } else {
                $this->info('(dry-run) Se eliminarían '.count($toDelete).' alumno(s).');
            }
        }

        // Cleanup orphan parents (only if not dry-run)
        $this->newLine();
        $this->info('Revisando padres huérfanos...');
        $orphanParents = User::where('role', 'PARENT')
            ->whereDoesntHave('students')
            ->get();

        if ($orphanParents->isEmpty()) {
            $this->info('✅ No hay padres huérfanos.');
        } else {
            $this->warn("Se encontraron {$orphanParents->count()} padre(s) huérfano(s):");

            $parentsDeleted = 0;
            foreach ($orphanParents as $parent) {
                if (! $isDryRun) {
                    $this->warn("  Eliminando padre huérfano: {$parent->name} ({$parent->email})");
                    $parent->delete();
                    $parentsDeleted++;
                } else {
                    $this->line("  (dry-run) Se eliminaría padre: {$parent->name} ({$parent->email})");
                }
            }

            if (! $isDryRun) {
                $this->info("Total de padres eliminados: {$parentsDeleted}");
            }
        }

        $this->newLine();
        $this->info($isDryRun ? '🔍 Dry-run finalizado. Ejecuta sin --dry-run para aplicar cambios.' : '✅ Sincronización finalizada con éxito.');

        return 0;
    }

    private function normalizeName(string $text): string
    {
        if (! $text) {
            return '';
        }

        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $text
        );

        return preg_replace('/\s+/', ' ', $text);
    }
}
