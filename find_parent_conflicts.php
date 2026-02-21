<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\2I.xlsx';
$ss = IOFactory::load($file);
$sheet = $ss->getSheetByName('Padres 2I');

if (!$sheet) {
    echo "Hoja 'Padres 2I' no encontrada.\n";
    exit;
}

$emailsToFind = ['8787014347@escuela.edu.mx', '8781179264@escuela.edu.mx'];

echo "Buscando correos en 'Padres 2I'...\n";
$highestRow = $sheet->getHighestRow();
$highestCol = $sheet->getHighestColumn();
$highestColIdx = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

for ($row = 1; $row <= $highestRow; $row++) {
    $found = false;
    $rowValues = [];
    for ($col = 1; $col <= $highestColIdx; $col++) {
        $val = (string)$sheet->getCellByColumnAndRow($col, $row)->getValue();
        $rowValues[] = "[$val]";
        foreach ($emailsToFind as $target) {
            if (mb_stripos($val, $target) !== false) {
                $found = true;
            }
        }
    }
    
    if ($found) {
        echo "Fila $row: " . implode(" ", $rowValues) . "\n";
    }
}
