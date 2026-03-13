<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__.'/../vendor/autoload.php';

$files = ['alumnos_2026-03-10.xlsx', 'CURP DATA.xlsx'];
foreach ($files as $file) {
    echo "FILE: $file\n";
    $spreadsheet = IOFactory::load($file);
    echo 'SHEETS: '.implode(', ', $spreadsheet->getSheetNames())."\n\n";
}
