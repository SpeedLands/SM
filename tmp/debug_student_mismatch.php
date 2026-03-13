<?php

require __DIR__.'/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$target = 'VAZQUEZ ROMAN AARON';

// 1. ORIGINAL.xlsx
$spreadsheetOrig = IOFactory::load('ORIGINAL.xlsx');
$sheetOrig = $spreadsheetOrig->getActiveSheet();
echo "ORIGINAL.xlsx:\n";
for ($row = 2; $row <= $sheetOrig->getHighestRow(); $row++) {
    $name = strtoupper(trim($sheetOrig->getCellByColumnAndRow(1, $row)->getValue() ?? ''));
    if ($name === $target) {
        echo "Row $row: ";
        for ($c = 1; $c <= 10; $c++) {
            echo "Col $c: ".$sheetOrig->getCellByColumnAndRow($c, $row)->getValue().' | ';
        }
        echo "\n";
    }
}

// 2. alumnos_2026-03-10.xlsx
$spreadsheetProd = IOFactory::load('alumnos_2026-03-10.xlsx');
$sheetProd = $spreadsheetProd->getSheetByName('Alumnos');
echo "\nalumnos_2026-03-10.xlsx:\n";
for ($row = 2; $row <= $sheetProd->getHighestRow(); $row++) {
    $name = strtoupper(trim($sheetProd->getCellByColumnAndRow(1, $row)->getValue() ?? ''));
    if ($name === $target) {
        echo "Row $row: ";
        for ($c = 1; $c <= 10; $c++) {
            echo "Col $c: ".$sheetProd->getCellByColumnAndRow($c, $row)->getValue().' | ';
        }
        echo "\n";
    }
}
