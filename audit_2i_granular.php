<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\2I.xlsx';
$ss = IOFactory::load($file);
$sheet = $ss->getSheet(0);

$highestRow = $sheet->getHighestRow();
for ($row = 2; $row <= $highestRow; $row++) {
    $e = trim(mb_strtolower((string)$sheet->getCell('C' . $row)->getValue(), 'UTF-8'));
    $p = trim((string)$sheet->getCell('E' . $row)->getValue());
    if ($e) {
        echo "Row $row: Email=[$e] | Pass=[$p]\n";
    }
}
