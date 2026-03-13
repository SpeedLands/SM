<?php

require __DIR__.'/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$students5 = [
    'CRUZ GARCIA CRISTIAN ALEJANDRO',
    'MONCADA RAMIREZ ALFREDO',
    'PIZARRO LUCIO JESUS MANUEL',
    'ESCOBEDO VILLAZANA ANGELA YANET',
    'VASQUEZ MENDEZ NAOMI ELIZABETH',
];

$listData = json_decode(file_get_contents('tmp/remaining_missing_list_v2.json'), true);
$missingCurps = [];
foreach ($listData as $s) {
    if (! in_array($s['name'], $students5)) {
        $missingCurps[$s['curp']] = $s['name'];
    }
}

$modified = [];
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
            break;
        }
    }

    if (! $alSheet) {
        continue;
    }

    $found = [];
    for ($row = 2; $row <= $alSheet->getHighestRow(); $row++) {
        $name = strtoupper(trim($alSheet->getCellByColumnAndRow(1, $row)->getValue() ?? ''));
        $curp = strtoupper(trim($alSheet->getCellByColumnAndRow(7, $row)->getValue() ?? ''));

        if (in_array($name, $students5) || isset($missingCurps[$curp])) {
            $found[] = $name;
        }
    }

    if (! empty($found)) {
        $modified[$group] = array_unique($found);
    }
}

ksort($modified);
file_put_contents('tmp/mod_summary.json', json_encode($modified, JSON_PRETTY_PRINT));
echo "Summary saved to tmp/mod_summary.json\n";
