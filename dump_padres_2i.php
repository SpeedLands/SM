<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\2I.xlsx';
$ss = IOFactory::load($file);
$sheet = $ss->getSheetByName('Padres 2I');

echo "DETALLE PADRES 2I (Rows 60-80):\n";
for ($row = 60; $row <= 80; $row++) {
    $c1 = (string)$sheet->getCell('A' . $row)->getValue();
    $c2 = (string)$sheet->getCell('B' . $row)->getValue();
    $c3 = (string)$sheet->getCell('C' . $row)->getValue();
    if ($c1 || $c2) {
        echo "Row $row: Name=[$c1] | Email=[$c2] | Pass=[$c3]\n";
    }
}
