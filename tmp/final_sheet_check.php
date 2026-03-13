<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__.'/../vendor/autoload.php';

$files = ['DATOS_CORREGIDOS/2G.xlsx', 'DATOS_CORREGIDOS/3G.xlsx'];
foreach ($files as $file) {
    echo "FILE: $file\n";
    $spreadsheet = IOFactory::load($file);
    $names = $spreadsheet->getSheetNames();
    echo 'SHEETS: ['.implode('], [', $names)."]\n\n";
}
