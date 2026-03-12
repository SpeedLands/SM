<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__.'/../vendor/autoload.php';

$file = 'alumnos_2026-03-10.xlsx';
$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getActiveSheet();
$headers = [];
for ($i = 1; $i <= 15; $i++) {
    $headers[$i] = $sheet->getCellByColumnAndRow($i, 1)->getValue();
}
echo "File: $file\n";
print_r($headers);

$row2 = [];
for ($i = 1; $i <= 15; $i++) {
    $row2[$i] = $sheet->getCellByColumnAndRow($i, 2)->getValue();
}
echo "Row 2:\n";
print_r($row2);
