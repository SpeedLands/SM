<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\2I.xlsx';
$ss = IOFactory::load($file);
$sheet = $ss->getSheet(0);

$targetEmail = '8781554286@escuela.edu.mx';
$targetPass = '8781554286';

echo "FORZANDO actualización en 2I.xlsx para $targetEmail...\n";

$highestRow = $sheet->getHighestRow();
$updates = 0;
for ($row = 2; $row <= $highestRow; $row++) {
    $e = trim(mb_strtolower((string)$sheet->getCell('C' . $row)->getValue(), 'UTF-8'));
    if ($e === $targetEmail) {
        $p = (string)$sheet->getCell('E' . $row)->getValue();
        if ($p !== $targetPass) {
            echo "  Fila $row: Cambiando '$p' por '$targetPass'\n";
            $sheet->setCellValue('E' . $row, $targetPass);
            $updates++;
        }
    }
}

if ($updates > 0) {
    echo "Guardando $updates cambios...\n";
    $writer = IOFactory::createWriter($ss, 'Xlsx');
    $writer->save($file);
    echo "¡Guardado exitoso!\n";
} else {
    echo "No se encontraron filas que requieran cambio.\n";
}
