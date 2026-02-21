<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\2I.xlsx';
$ss = IOFactory::load($file);
$sheet = $ss->getSheet(0);

$highestRow = $sheet->getHighestRow();
echo "Buscando ALVARADO en 2I.xlsx...\n";
for ($row = 1; $row <= $highestRow; $row++) {
    $name = (string)$sheet->getCell('A' . $row)->getValue();
    if (str_contains(mb_strtolower($name), 'alvarado')) {
        echo "Fila $row: ";
        for ($col = 'A'; $col <= 'G'; $col++) {
            echo "$col: [" . $sheet->getCell($col . $row)->getValue() . "] ";
        }
        echo "\n";
    }
}
