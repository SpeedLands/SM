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
    $curp = strtoupper(trim($sheetProd->getCellByColumnAndRow(7, $row)->getValue() ?? ''));
    if ($curp) {
        $prodCurps[$curp] = true;
    }
}
echo 'Loaded '.count($prodCurps)." production CURPs from col 7.\n";

// 2. Load CURP DATA
$curpDataFile = 'CURP DATA.xlsx';
$spreadsheetCurp = IOFactory::load($curpDataFile);
$sheetCurp = $spreadsheetCurp->getActiveSheet();
$highestRowCurp = $sheetCurp->getHighestRow();

$missingFromProd = [];
for ($row = 2; $row <= $highestRowCurp; $row++) {
    $curp = strtoupper(trim($sheetCurp->getCellByColumnAndRow(1, $row)->getValue() ?? ''));
    if ($curp && ! isset($prodCurps[$curp])) {
        // Construct name: Usually PATERNO MATERNO NOMBRE
        $paterno = trim($sheetCurp->getCellByColumnAndRow(3, $row)->getValue() ?? '');
        $materno = trim($sheetCurp->getCellByColumnAndRow(4, $row)->getValue() ?? '');
        $nombre = trim($sheetCurp->getCellByColumnAndRow(2, $row)->getValue() ?? '');
        $fullName = strtoupper("$paterno $materno $nombre");

        $missingFromProd[$curp] = $fullName;
    }
}
echo 'Found '.count($missingFromProd)." missing students via CURP.\n";

// 3. Load ORIGINAL for details
$originalFile = 'ORIGINAL.xlsx';
$spreadsheetOrig = IOFactory::load($originalFile);
$sheetOrig = $spreadsheetOrig->getActiveSheet();
$highestRowOrig = $sheetOrig->getHighestRow();

$origRecords = [];
for ($row = 2; $row <= $highestRowOrig; $row++) {
    $name = strtoupper(trim($sheetOrig->getCellByColumnAndRow(1, $row)->getValue() ?? ''));
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
foreach ($missingFromProd as $curp => $fullName) {
    if (in_array($fullName, $handledNames)) {
        continue;
    }
    if (in_array($fullName, $excludedNames)) {
        continue;
    }

    // Try to find the record in ORIGINAL
    // Sometimes names have slight differences in spacing
    $match = $origRecords[$fullName] ?? null;

    if (! $match) {
        // Try fuzzy name matching? Let's just list it for now
        echo "  Check: Could not find exact match for $fullName in ORIGINAL.xlsx\n";
    }

    $finalList[] = [
        'name' => $fullName,
        'curp' => $curp,
        'group' => $match ? $match['gpo'] : 'UNKNOWN',
        'turno' => $match ? $match['turno'] : 'UNKNOWN',
        'dir' => $match ? $match['dir'] : null,
        'item1' => $match ? $match['item1'] : null,
        'item2' => $match ? $match['item2'] : null,
    ];
}

echo "\nFinal list of ".count($finalList)." students to integrate:\n";
foreach ($finalList as $s) {
    echo "  - {$s['name']} | Group: {$s['group']} | CURP: {$s['curp']}\n";
}

file_put_contents('tmp/remaining_missing_list_v2.json', json_encode($finalList, JSON_PRETTY_PRINT));
