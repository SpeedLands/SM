<?php

require __DIR__.'/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$search = ['VAZQUEZ', 'AARON'];

function matches($name, $search)
{
    if (! $name) {
        return false;
    }
    $name = strtoupper($name);
    foreach ($search as $s) {
        if (strpos($name, $s) === false) {
            return false;
        }
    }

    return true;
}

// 1. ORIGINAL.xlsx
$spreadsheetOrig = IOFactory::load('ORIGINAL.xlsx');
$sheetOrig = $spreadsheetOrig->getActiveSheet();
echo "ORIGINAL.xlsx hits:\n";
for ($row = 2; $row <= $sheetOrig->getHighestRow(); $row++) {
    $name = $sheetOrig->getCellByColumnAndRow(1, $row)->getValue();
    if (matches($name, $search)) {
        echo "Row $row: ";
        for ($c = 1; $c <= 6; $c++) {
            echo "Col $c: ".$sheetOrig->getCellByColumnAndRow($c, $row)->getValue().' | ';
        }
        echo "\n";
    }
}

// 2. alumnos_2026-03-10.xlsx
$spreadsheetProd = IOFactory::load('alumnos_2026-03-10.xlsx');
$sheetProd = $spreadsheetProd->getSheetByName('Alumnos');
echo "\nalumnos_2026-03-10.xlsx hits:\n";
for ($row = 2; $row <= $sheetProd->getHighestRow(); $row++) {
    $name = $sheetProd->getCellByColumnAndRow(1, $row)->getValue();
    if (matches($name, $search)) {
        echo "Row $row: ";
        for ($c = 1; $c <= 10; $c++) {
            echo "Col $c: ".$sheetProd->getCellByColumnAndRow($c, $row)->getValue().' | ';
        }
        echo "\n";
    }
}
