<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncDataFromOriginal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-data {--file=ORIGINAL.xlsx : El archivo Excel original para sincronizar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina alumnos y padres que no están presentes en el archivo ORIGINAL.xlsx';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->option('file');

        if (! file_exists($filePath)) {
            $this->error("El archivo no existe: {$filePath}");

            return 1;
        }

        $this->info("Cargando archivo: {$filePath}...");

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        } catch (\Exception $e) {
            $this->error('Error cargando el archivo: '.$e->getMessage());

            return 1;
        }

        $sheet = $spreadsheet->getActiveSheet();
        $originalMap = [];

        foreach ($sheet->getRowIterator(2) as $row) {
            $name = $sheet->getCell('A'.$row->getRowIndex())->getValue();
            if ($name) {
                $originalMap[$this->normalizeName($name)] = true;
            }
        }

        $this->info('Se encontraron '.count($originalMap).' alumnos válidos en el archivo maestro.');

        // 2. Scan and Delete invalid students
        $students = \App\Models\Student::all();
        $deletedCount = 0;

        $this->info('Escaneando base de datos de alumnos...');
        foreach ($students as $student) {
            $normName = $this->normalizeName($student->name);

            if (! isset($originalMap[$normName])) {
                $this->warn("Eliminando alumno: {$student->name} (No está en el archivo maestro)");
                $student->delete();
                $deletedCount++;
            }
        }

        $this->info("Total de alumnos eliminados: $deletedCount");

        // 3. Cleanup orphan parents
        $this->info('Limpiando padres huérfanos...');
        $orphanParents = \App\Models\User::where('role', 'PARENT')
            ->whereDoesntHave('students')
            ->get();

        $parentsDeleted = 0;
        foreach ($orphanParents as $parent) {
            $this->warn("Eliminando padre huérfano: {$parent->name} ({$parent->email})");
            $parent->delete();
            $parentsDeleted++;
        }

        $this->info("Total de padres eliminados: $parentsDeleted");
        $this->info('Sincronización finalizada con éxito.');

        return 0;
    }

    private function normalizeName($text)
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
