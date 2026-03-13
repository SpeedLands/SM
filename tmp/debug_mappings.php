<?php

require __DIR__.'/../vendor/autoload.php';

$missing = json_decode(file_get_contents('tmp/remaining_missing_list_v2.json'), true);
$handledNames = [
    'CRUZ GARCIA CRISTIAN ALEJANDRO', 'MONCADA RAMIREZ ALFREDO', 'PIZARRO LUCIO JESUS MANUEL',
    'ESCOBEDO VILLAZANA ANGELA YANET', 'VASQUEZ MENDEZ NAOMI ELIZABETH',
];
$excludedNames = ['GALLEGOS MEDINA ROGELIO ALBERTO'];

foreach ($missing as $k => $s) {
    if (in_array($s['name'], $handledNames) || in_array($s['name'], $excludedNames)) {
        unset($missing[$k]);
    }
}

echo 'Remaining Missing: '.count($missing)."\n";

foreach ($missing as $s) {
    echo "  - {$s['name']} | Group: {$s['group']} | Dir: {$s['dir']} | CURP: {$s['curp']}\n";
}

// Check ORIGINAL for a few of these
use PhpOffice\PhpSpreadsheet\IOFactory;

$spreadsheetOrig = IOFactory::load('ORIGINAL.xlsx');
$sheetOrig = $spreadsheetOrig->getActiveSheet();
$highestRowOrig = $sheetOrig->getHighestRow();

echo "\nFuzzy search in ORIGINAL for 'INFANTE':\n";
for ($row = 2; $row <= $highestRowOrig; $row++) {
    $name = strtoupper($sheetOrig->getCellByColumnAndRow(1, $row)->getValue());
    if (strpos($name, 'INFANTE') !== false) {
        echo "  Row $row: $name | GPO: ".$sheetOrig->getCellByColumnAndRow(5, $row)->getValue().' | TURNO: '.$sheetOrig->getCellByColumnAndRow(6, $row)->getValue()."\n";
    }
}
