<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__.'/../vendor/autoload.php';

$file = 'ORIGINAL.xlsx';
$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getActiveSheet();
$headers = [];
for ($i = 1; $i <= 10; $i++) {
    $headers[$i] = $sheet->getCellByColumnAndRow($i, 1)->getValue();
}
echo "File: $file\n";
print_r($headers);
