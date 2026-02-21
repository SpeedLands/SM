<?php

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$datosDir = __DIR__.'/DATOS';
$files = glob($datosDir.'/*.xlsx');

$students = []; // name => [ ['file' => ..., 'row' => ...], ... ]

echo "Analizando alumnos en archivos Excel...\n";

foreach ($files as $file) {
    $filename = basename($file);
    if ($filename === 'Maestros.xlsx') {
        continue;
    }

    echo "Procesando $filename...\n";
    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, true);

        foreach ($data as $rowIndex => $row) {
            if ($rowIndex === 1) {
                continue;
            } // Skip header

            $name = trim((string) ($row['A'] ?? ''));

            // In Parents mode, Column A is "Padre de X".
            // We need to check if we are reading a Student list or a Parent list.
            // Based on service logic, importParents uses Col A for Parent Name.
            // But usually these files have Students in one sheet.

            // Let's look for names that DON'T start with "Padre de" or "Madre de"
            // if we want to be sure, OR assume the user refers to the student list structure.
            // Actually, in the project, the user usually has a "Students" sheet or equivalent.

            if (empty($name) || in_array(strtolower($name), ['nombre', 'estudiante', 'alumno'])) {
                continue;
            }

            // If it's a parent row like "Padre de ...", skip it for this specific student check
            if (preg_match('/^(Padre|Madre|Tutor|Abuelo|Abuela)\s+de\s+/i', $name)) {
                continue;
            }

            if (! isset($students[$name])) {
                $students[$name] = [];
            }
            $students[$name][] = [
                'file' => $filename,
                'row' => $rowIndex,
            ];
        }
    } catch (\Exception $e) {
        echo "Error en $filename: ".$e->getMessage()."\n";
    }
}

$duplicates = array_filter($students, function ($occurrences) {
    return count($occurrences) > 1;
});

echo "\n--- RESULTADOS DE ALUMNOS DUPLICADOS ---\n";
echo 'Total de alumnos analizados: '.count($students)."\n";
echo 'Nombres encontrados en más de un lugar: '.count($duplicates)."\n";

foreach ($duplicates as $name => $occurrences) {
    echo "\nAlumno: $name\n";
    foreach ($occurrences as $occ) {
        echo "  - Archivo: {$occ['file']} (Fila: {$occ['row']})\n";
    }
}
