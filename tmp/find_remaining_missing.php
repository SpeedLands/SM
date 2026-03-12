<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__.'/../vendor/autoload.php';

// 1. Load production CURPs
$prodFile = 'alumnos_2026-03-10.xlsx';
$spreadsheetProd = IOFactory::load($prodFile);
$sheetProd = $spreadsheetProd->getActiveSheet();
$highestRowProd = $sheetProd->getHighestRow();

$prodCurps = [];
for ($row = 2; $row <= $highestRowProd; $row++) {
    $curp = $sheetProd->getCellByColumnAndRow(8, $row)->getValue(); // Column H is CURP in this file usually
    if ($curp) {
        $prodCurps[] = strtoupper(trim($curp));
    }
}
echo 'Loaded '.count($prodCurps)." production CURPs.\n";

// 2. Load CURP DATA
$curpDataFile = 'CURP DATA.xlsx';
$spreadsheetCurp = IOFactory::load($curpDataFile);
$sheetCurp = $spreadsheetCurp->getActiveSheet();
$highestRowCurp = $sheetCurp->getHighestRow();

$missingStudents = [];
for ($row = 2; $row <= $highestRowCurp; $row++) {
    $name = $sheetCurp->getCellByColumnAndRow(1, $row)->getValue();
    $curp = $sheetCurp->getCellByColumnAndRow(7, $row)->getValue(); // Column G is CURP in CURP DATA

    if ($curp) {
        $curpClean = strtoupper(trim($curp));
        if (! in_array($curpClean, $prodCurps)) {
            $missingStudents[] = [
                'name' => $name,
                'curp' => $curpClean,
                'row' => $row,
            ];
        }
    }
}

echo 'Found '.count($missingStudents)." missing students.\n";

// Filter out the ones we already handled
$handledNames = [
    'CRUZ GARCIA CRISTIAN ALEJANDRO',
    'MONCADA RAMIREZ ALFREDO',
    'PIZARRO LUCIO JESUS MANUEL',
    'ESCOBEDO VILLAZANA ANGELA YANET',
    'VASQUEZ MENDEZ NAOMI ELIZABETH',
];
$excludedNames = [
    'GALLEGOS MEDINA ROGELIO ALBERTO',
];

$remainingMissing = [];
foreach ($missingStudents as $ms) {
    if (in_array(strtoupper($ms['name']), $handledNames)) {
        continue;
    }
    if (in_array(strtoupper($ms['name']), $excludedNames)) {
        continue;
    }
    $remainingMissing[] = $ms;
}

echo 'Remaining to process: '.count($remainingMissing)."\n";
foreach ($remainingMissing as $rm) {
    echo '  - '.$rm['name'].' ('.$rm['curp'].")\n";
}

file_put_contents('tmp/remaining_missing_students.json', json_encode($remainingMissing, JSON_PRETTY_PRINT));
