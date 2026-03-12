<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__.'/../vendor/autoload.php';

$file = 'alumnos_2026-03-10.xlsx';
$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getSheetByName('Alumnos');
if (! $sheet) {
    echo "Sheet 'Alumnos' not found! Available: ".implode(', ', $spreadsheet->getSheetNames())."\n";
    exit;
}

$highestRow = $sheet->getHighestRow();
echo "Highest Row: $highestRow\n";

for ($row = 1; $row <= 5; $row++) {
    echo "Row $row:\n";
    for ($col = 1; $col <= 10; $col++) {
        echo "  Col $col: ".$sheet->getCellByColumnAndRow($col, $row)->getValue()."\n";
    }
}
