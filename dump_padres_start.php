<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\2I.xlsx';
$ss = IOFactory::load($file);
$sheet = $ss->getSheetByName('Padres 2I');

echo "Row Dump 1-20 of Padres 2I:\n";
for ($row = 1; $row <= 20; $row++) {
    $c1 = (string)$sheet->getCell('A' . $row)->getValue();
    $c2 = (string)$sheet->getCell('B' . $row)->getValue();
    $c3 = (string)$sheet->getCell('C' . $row)->getValue();
    echo "[$row] A:[$c1] B:[$c2] C:[$c3]\n";
}
