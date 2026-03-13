<?php

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'DATOS_CORREGIDOS/2A.xlsx';
if (! file_exists($file)) {
    exit("File not found\n");
}

$reader = IOFactory::createReader('Xlsx');
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($file);

$sheetNames = $spreadsheet->getSheetNames();
echo 'Sheet names: '.implode(', ', $sheetNames)."\n\n";

foreach ($sheetNames as $index => $name) {
    echo "Sheet $index: $name\n";
    $sheet = $spreadsheet->getSheet($index);
    $headers = [];
    for ($col = 'A'; $col <= 'Z'; $col++) {
        $val = $sheet->getCell($col.'1')->getValue();
        if ($val === null || $val === '') {
            break;
        }
        $headers[$col] = $val;
    }
    echo 'Headers: '.json_encode($headers)."\n";

    // Check first data row (Row 2) Col B
    echo 'Row 2 Col B: '.$sheet->getCell('B2')->getValue()."\n";
    echo "-------------------\n";
}
