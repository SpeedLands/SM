<?php

require __DIR__.'/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$missingCurps = [];

// 1. Load the 5 specific students
$students5 = [
    'CRUZ GARCIA CRISTIAN ALEJANDRO',
    'MONCADA RAMIREZ ALFREDO',
    'PIZARRO LUCIO JESUS MANUEL',
    'ESCOBEDO VILLAZANA ANGELA YANET',
    'VASQUEZ MENDEZ NAOMI ELIZABETH',
];

// 2. Load the 18 students from final batch (remaining_missing_list_v2.json)
$list = json_decode(file_get_contents('tmp/remaining_missing_list_v2.json'), true);
foreach ($list as $s) {
    if (in_array($s['name'], $students5)) {
        continue;
    }
    $missingCurps[$s['curp']] = $s['name'];
}

$modifiedGroups = [];

$files = glob('DATOS_CORREGIDOS/*.xlsx');
foreach ($files as $file) {
    $group = basename($file, '.xlsx');
    if ($group === 'Maestros') {
        continue;
    }

    $spreadsheet = IOFactory::load($file);
    $alSheet = null;
    foreach ($spreadsheet->getSheetNames() as $n) {
        if (stripos($n, 'Alumno') !== false) {
            $alSheet = $spreadsheet->getSheetByName($n);
        }
    }

    if (! $alSheet) {
        continue;
    }

    $addedInThisFile = [];
    for ($row = 2; $row <= $alSheet->getHighestRow(); $row++) {
        $name = strtoupper(trim($alSheet->getCellByColumnAndRow(1, $row)->getValue() ?? ''));
        $curp = strtoupper(trim($alSheet->getCellByColumnAndRow(7, $row)->getValue() ?? ''));

        if (in_array($name, $students5) || isset($missingCurps[$curp])) {
            $addedInThisFile[] = $name;
        }
    }

    if (! empty($addedInThisFile)) {
        $modifiedGroups[$group] = array_unique($addedInThisFile);
    }
}

ksort($modifiedGroups);
echo json_encode($modifiedGroups, JSON_PRETTY_PRINT);
