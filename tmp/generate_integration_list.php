<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__.'/../vendor/autoload.php';

// 1. Load production CURPs
$prodFile = 'alumnos_2026-03-10.xlsx';
$spreadsheetProd = IOFactory::load($prodFile);
$sheetProd = $spreadsheetProd->getSheetByName('Alumnos');
$highestRowProd = $sheetProd->getHighestRow();

$prodCurps = [];
for ($row = 2; $row <= $highestRowProd; $row++) {
    $curp = $sheetProd->getCellByColumnAndRow(8, $row)->getValue();
    if ($curp) {
        $prodCurps[] = strtoupper(trim($curp));
    }
}
echo 'Loaded '.count($prodCurps)." production CURPs.\n";

// 2. Load CURP DATA
$curpDataFile = 'CURP DATA.xlsx';
$spreadsheetCurp = IOFactory::load($curpDataFile);
$sheetCurp = $spreadsheetCurp->getSheetByName('Hoja1');
$highestRowCurp = $sheetCurp->getHighestRow();

$missingStudentsViaCurp = [];
for ($row = 2; $row <= $highestRowCurp; $row++) {
    $name = trim($sheetCurp->getCellByColumnAndRow(1, $row)->getValue());
    $curp = trim($sheetCurp->getCellByColumnAndRow(7, $row)->getValue());

    if ($curp) {
        $curpClean = strtoupper(trim($curp));
        if (! in_array($curpClean, $prodCurps)) {
            $missingStudentsViaCurp[$curpClean] = $name;
        }
    }
}
echo 'Found '.count($missingStudentsViaCurp)." missing students via CURP.\n";

// 3. Load ORIGINAL for details
$originalFile = 'ORIGINAL.xlsx';
$spreadsheetOrig = IOFactory::load($originalFile);
$sheetOrig = $spreadsheetOrig->getActiveSheet();
$highestRowOrig = $sheetOrig->getHighestRow();

$origRecords = [];
for ($row = 2; $row <= $highestRowOrig; $row++) {
    $name = strtoupper(trim($sheetOrig->getCellByColumnAndRow(1, $row)->getValue()));
    if ($name) {
        $origRecords[$name] = [
            'item1' => $sheetOrig->getCellByColumnAndRow(2, $row)->getValue(),
            'item2' => $sheetOrig->getCellByColumnAndRow(3, $row)->getValue(),
            'dir' => $sheetOrig->getCellByColumnAndRow(4, $row)->getValue(),
            'gpo' => $sheetOrig->getCellByColumnAndRow(5, $row)->getValue(),
            'turno' => $sheetOrig->getCellByColumnAndRow(6, $row)->getValue(),
        ];
    }
}

// 4. Combine and Filter
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

$finalList = [];
foreach ($missingStudentsViaCurp as $curp => $name) {
    if (in_array(strtoupper($name), $handledNames)) {
        continue;
    }
    if (in_array(strtoupper($name), $excludedNames)) {
        continue;
    }

    $detail = $origRecords[strtoupper($name)] ?? null;

    $finalList[] = [
        'name' => $name,
        'curp' => $curp,
        'group' => $detail ? $detail['gpo'] : 'UNKNOWN',
        'turno' => $detail ? $detail['turno'] : 'UNKNOWN',
        'dir' => $detail ? $detail['dir'] : null,
        'item1' => $detail ? $detail['item1'] : null,
        'item2' => $detail ? $detail['item2'] : null,
    ];
}

echo 'Final list of '.count($finalList)." students to integrate:\n";
foreach ($finalList as $s) {
    echo "  - {$s['name']} | Group: {$s['group']} | CURP: {$s['curp']}\n";
}

file_put_contents('tmp/final_remaining_integration_list.json', json_encode($finalList, JSON_PRETTY_PRINT));
