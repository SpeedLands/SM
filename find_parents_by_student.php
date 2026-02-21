<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\2I.xlsx';
$ss = IOFactory::load($file);
$sheet = $ss->getSheetByName('Padres 2I');

$studentPart = 'ALVARADO ZARAGOZA CESAR ALEJANDRO';

echo "Buscando padres de '$studentPart' en Padres 2I...\n";
$highestRow = $sheet->getHighestRow();

for ($row = 1; $row <= $highestRow; $row++) {
    $val = (string)$sheet->getCell('A' . $row)->getValue();
    if (mb_stripos($val, $studentPart) !== false) {
        $e = (string)$sheet->getCell('B' . $row)->getValue();
        $p = (string)$sheet->getCell('C' . $row)->getValue();
        echo "Row $row: Name=[$val] | Email=[$e] | Pass=[$p]\n";
    }
}
