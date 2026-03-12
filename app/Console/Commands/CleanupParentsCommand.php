<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CleanupParentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parents:cleanup {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar cuentas de padres huérfanas o incorrectas basándose en los archivos DATOS_CORREGIDOS';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando escaneo de archivos DATOS_CORREGIDOS...');

        $outputDir = base_path('DATOS_CORREGIDOS/');
        if (! is_dir($outputDir)) {
            $this->error('La carpeta DATOS_CORREGIDOS no existe en '.$outputDir);

            return Command::FAILURE;
        }

        $files = glob($outputDir.'*.xlsx');
        $validEmails = [];

        foreach ($files as $file) {
            $filename = basename($file);
            if (str_contains($filename, 'Maestros') || str_contains($filename, '~')) {
                continue;
            }

            $this->line("Procesando $filename...");

            try {
                $reader = IOFactory::createReader('Xlsx');
                $reader->setReadDataOnly(true);
                $ss = $reader->load($file);

                $sheetNames = $ss->getSheetNames();
                $foundInFile = 0;

                foreach ($sheetNames as $name) {
                    $sheet = $ss->getSheetByName($name);
                    $highestRow = $sheet->getHighestRow();
                    $highestColumn = $sheet->getHighestColumn();

                    // Solo escaneamos las primeras 10 columnas por eficiencia,
                    // ya que el correo suele estar al principio (Col B)
                    $maxCol = 'J';
                    if ($highestColumn < $maxCol) {
                        $maxCol = $highestColumn;
                    }

                    for ($row = 1; $row <= $highestRow; $row++) {
                        for ($col = 'A'; $col <= $maxCol; $col++) {
                            $value = trim((string) $sheet->getCell($col.$row)->getValue());

                            if (str_ends_with(mb_strtolower($value, 'UTF-8'), '@escuela.edu.mx')) {
                                $validEmails[] = mb_strtolower($value, 'UTF-8');
                                $foundInFile++;
                            }
                        }
                    }
                }

                if ($foundInFile === 0) {
                    $this->warn("No se encontraron correos @escuela.edu.mx en $filename.");
                } else {
                    $this->line("   - Se encontraron $foundInFile correos en este archivo.");
                }

                $ss->disconnectWorksheets();
                unset($ss);
            } catch (\Exception $e) {
                $this->error("Error leyendo $filename: ".$e->getMessage());
            }
        }

        $validEmails = array_unique($validEmails);
        $this->info('Se encontraron '.count($validEmails).' correos VALIDOS de padres en los excels.');

        // Buscar padres en BD que NO estén en la lista de excels
        $parentsToDelete = User::where('role', 'PARENT')
            ->whereNotIn('email', $validEmails)
            ->get();

        if ($parentsToDelete->isEmpty()) {
            $this->info('Todo está en orden. No hay cuentas de padres sobrantes/incorrectas para eliminar.');

            return Command::SUCCESS;
        }

        $this->warn('ATENCIÓN: Se encontraron '.$parentsToDelete->count().' cuentas de PADRES en la base de datos que NO existen en los archivos corregidos.');

        $this->table(
            ['ID', 'Nombre', 'Correo', 'Creado El'],
            $parentsToDelete->map(fn ($p) => [$p->id, $p->name, $p->email, $p->created_at->format('Y-m-d H:i:s')])->toArray()
        );

        if ($this->option('force') || $this->confirm('¿Deseas ELIMINAR permanentemente estas '.$parentsToDelete->count().' cuentas de la base de datos?')) {
            $deletedCount = 0;
            DB::transaction(function () use ($parentsToDelete, &$deletedCount) {
                foreach ($parentsToDelete as $parent) {
                    // Eliminar sus registros pivot primero
                    $parent->students()->detach();
                    $parent->delete();
                    $deletedCount++;
                }
            });
            $this->info("✅ Se han eliminado $deletedCount cuentas correctamente.");
        } else {
            $this->line('Operación cancelada.');
        }

        return Command::SUCCESS;
    }
}
