<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\3I.xlsx';
$ss = IOFactory::load($file);
$sheet = $ss->getSheet(0);

echo "DUMP 3I.xlsx (Row 1-2):\n";
for ($row = 1; $row <= 2; $row++) {
    echo "R$row: ";
    for ($colIdx = 1; $colIdx <= 10; $colIdx++) {
        $val = $sheet->getCellByColumnAndRow($colIdx, $row)->getValue();
        echo "[$val] ";
    }
    echo "\n";
}
