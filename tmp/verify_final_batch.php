<?php

require __DIR__.'/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$list = json_decode(file_get_contents('tmp/remaining_missing_list_v2.json'), true);
$handledNames = [
    'CRUZ GARCIA CRISTIAN ALEJANDRO', 'MONCADA RAMIREZ ALFREDO', 'PIZARRO LUCIO JESUS MANUEL',
    'ESCOBEDO VILLAZANA ANGELA YANET', 'VASQUEZ MENDEZ NAOMI ELIZABETH',
];
$excludedNames = ['GALLEGOS MEDINA ROGELIO ALBERTO'];

$toVerify = [];
foreach ($list as $s) {
    if (in_array($s['name'], $handledNames) || in_array($s['name'], $excludedNames)) {
        continue;
    }
    $toVerify[$s['curp']] = $s['name'];
}

echo 'Verifying '.count($toVerify)." CURPs across DATOS_CORREGIDOS...\n";

$foundCount = 0;
$files = glob('DATOS_CORREGIDOS/*.xlsx');

foreach ($files as $file) {
    $spreadsheet = IOFactory::load($file);
    $alSheet = null;
    foreach ($spreadsheet->getSheetNames() as $n) {
        if (stripos($n, 'Alumno') !== false) {
            $alSheet = $spreadsheet->getSheetByName($n);
        }
    }

    if ($alSheet) {
        for ($row = 2; $row <= $alSheet->getHighestRow(); $row++) {
            $curp = strtoupper(trim($alSheet->getCellByColumnAndRow(7, $row)->getValue() ?? ''));
            if (isset($toVerify[$curp])) {
                echo "  [FOUND] {$toVerify[$curp]} in $file (Row $row)\n";
                unset($toVerify[$curp]);
                $foundCount++;
            }
        }
    }
}

echo "\nTotal Found: $foundCount\n";
echo 'Missing: '.count($toVerify)."\n";
foreach ($toVerify as $c => $n) {
    echo "  - $n ($c)\n";
}
