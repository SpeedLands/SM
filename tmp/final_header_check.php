<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__.'/../vendor/autoload.php';

echo "--- alumnos_2026-03-10.xlsx ---\n";
$sheet = IOFactory::load('alumnos_2026-03-10.xlsx')->getActiveSheet();
for ($i = 1; $i <= 10; $i++) {
    echo "  Col $i: ".$sheet->getCellByColumnAndRow($i, 1)->getValue()."\n";
}

echo "\n--- CURP DATA.xlsx ---\n";
$sheet = IOFactory::load('CURP DATA.xlsx')->getActiveSheet();
for ($i = 1; $i <= 10; $i++) {
    echo "  Col $i: ".$sheet->getCellByColumnAndRow($i, 1)->getValue()."\n";
}
