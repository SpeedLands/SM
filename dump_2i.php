<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\2I.xlsx';
$ss = IOFactory::load($file);
$sheet = $ss->getSheet(0);

echo "FINAL DUMP 2I.xlsx (Rows 1-20):\n";
for ($row = 1; $row <= 20; $row++) {
    echo "[$row] ";
    for ($colIdx = 1; $colIdx <= 11; $colIdx++) {
        $val = $sheet->getCellByColumnAndRow($colIdx, $row)->getValue();
        echo "{$colIdx}:[$val] ";
    }
    echo "\n";
}
