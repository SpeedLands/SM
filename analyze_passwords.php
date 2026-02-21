<?php

require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$datosDir = __DIR__.'/DATOS';
$files = glob($datosDir.'/*.xlsx');

// Prioritize 3rd grade files by sorting descending
rsort($files);

$parents = []; // email => password
$conflicts = []; // email => [passwords]
$fileMappings = []; // filename => [row_index => email]

echo "Analizando archivos...\n";

foreach ($files as $file) {
    if (basename($file) === 'Maestros.xlsx') {
        continue;
    }

    echo 'Procesando '.basename($file)."...\n";
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $data = $sheet->toArray(null, true, true, true);

    $fileMappings[basename($file)] = [];

    foreach ($data as $rowIndex => $row) {
        if ($rowIndex === 1) {
            continue;
        } // Skip header

        $email = trim((string) ($row['B'] ?? ''));
        $password = trim((string) ($row['D'] ?? ''));

        if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            continue;
        }

        $fileMappings[basename($file)][$rowIndex] = $email;

        if (! isset($parents[$email])) {
            $parents[$email] = $password;
        } else {
            if ($parents[$email] !== $password) {
                if (! isset($conflicts[$email])) {
                    $conflicts[$email] = [$parents[$email]];
                }
                if (! in_array($password, $conflicts[$email])) {
                    $conflicts[$email][] = $password;
                }
            }
        }
    }
}

echo "\n--- RESULTADOS DEL ANÁLISIS ---\n";
echo 'Total de padres únicos encontrados: '.count($parents)."\n";
echo 'Padres con conflictos de contraseña: '.count($conflicts)."\n";

if (count($conflicts) > 0) {
    echo "\nEjemplos de conflictos:\n";
    $i = 0;
    foreach ($conflicts as $email => $passes) {
        echo "- $email: ".implode(' | ', $passes)."\n";
        if (++$i >= 10) {
            break;
        }
    }
}

// Check for the --apply flag
if (isset($argv[1]) && $argv[1] === '--apply') {
    echo "\nAplicanado cambios a los archivos...\n";

    foreach ($files as $file) {
        if (basename($file) === 'Maestros.xlsx') {
            continue;
        }

        $filename = basename($file);
        echo "Actualizando $filename...\n";

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();

        $changed = false;
        if (isset($fileMappings[$filename])) {
            foreach ($fileMappings[$filename] as $rowIndex => $email) {
                $correctPassword = $parents[$email];
                $currentPassword = $sheet->getCell("D$rowIndex")->getValue();

                if ($currentPassword !== $correctPassword) {
                    $sheet->setCellValueExplicit("D$rowIndex", $correctPassword, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $changed = true;
                }
            }
        }

        if ($changed) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($file);
            echo "  [OK] Guardado.\n";
        } else {
            echo "  [SKIP] Sin cambios.\n";
        }
    }
    echo "\n¡Proceso de unificación completado!\n";
} else {
    echo "\nPara aplicar los cambios, ejecuta: php analyze_passwords.php --apply\n";
}
