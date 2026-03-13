<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__.'/../vendor/autoload.php';

$file = 'CURP DATA.xlsx';
$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getActiveSheet();
$highestRow = $sheet->getHighestRow();
echo "File: $file | Highest Row: $highestRow\n";

for ($row = 1; $row <= 3; $row++) {
    echo "Row $row:\n";
    for ($col = 1; $col <= 10; $col++) {
        echo "  Col $col: ".$sheet->getCellByColumnAndRow($col, $row)->getValue()."\n";
    }
}
