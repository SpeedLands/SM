<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// 1. Get Production CURPs
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

// 2. Load ORIGINAL records
$originalFile = 'ORIGINAL.xlsx';
$spreadsheetOrig = IOFactory::load($originalFile);
$sheetOrig = $spreadsheetOrig->getActiveSheet();
$highestRowOrig = $sheetOrig->getHighestRow();

$origRecords = [];
for ($row = 2; $row <= $highestRowOrig; $row++) {
    $name = strtoupper(trim($sheetOrig->getCellByColumnAndRow(1, $row)->getValue() ?? ''));
    if (! $name) {
        continue;
    }

    $group = null;
    $turno = null;
    $address = null;

    for ($c = 4; $c <= 6; $c++) {
        $val = strtoupper(trim($sheetOrig->getCellByColumnAndRow($c, $row)->getValue() ?? ''));
        if (preg_match('/^([123])[º°]?\s*([A-I])$/u', $val, $matches)) {
            $group = $matches[1].$matches[2]; // e.g. 1A
        } elseif (stripos($val, 'MATUTINO') !== false || stripos($val, 'VESPERTINO') !== false) {
            $turno = (stripos($val, 'VESPERTINO') !== false) ? 'VESPERTINO' : 'MATUTINO';
        } elseif (strlen($val) > 5 && ! $address) {
            $address = $val;
        }
    }

    $origRecords[] = [
        'full_name' => $name,
        'item1' => $sheetOrig->getCellByColumnAndRow(2, $row)->getValue(),
        'item2' => $sheetOrig->getCellByColumnAndRow(3, $row)->getValue(),
        'group' => $group,
        'turno' => $turno ?: 'MATUTINO',
        'address' => $address,
    ];
}

// 3. Match CURP DATA
$curpDataFile = 'CURP DATA.xlsx';
$spreadsheetCurp = IOFactory::load($curpDataFile);
$sheetCurp = $spreadsheetCurp->getActiveSheet();
$highestRowCurp = $sheetCurp->getHighestRow();

$finalIntegrationList = [];
$handledNames = [
    'CRUZ GARCIA CRISTIAN ALEJANDRO', 'MONCADA RAMIREZ ALFREDO', 'PIZARRO LUCIO JESUS MANUEL',
    'ESCOBEDO VILLAZANA ANGELA YANET', 'VASQUEZ MENDEZ NAOMI ELIZABETH',
];
$excludedNames = ['GALLEGOS MEDINA ROGELIO ALBERTO'];

for ($row = 2; $row <= $highestRowCurp; $row++) {
    $curp = strtoupper(trim($sheetCurp->getCellByColumnAndRow(1, $row)->getValue() ?? ''));
    if (! $curp || isset($prodCurps[$curp])) {
        continue;
    }

    $parts = [
        strtoupper(trim($sheetCurp->getCellByColumnAndRow(2, $row)->getValue() ?? '')),
        strtoupper(trim($sheetCurp->getCellByColumnAndRow(3, $row)->getValue() ?? '')),
        strtoupper(trim($sheetCurp->getCellByColumnAndRow(4, $row)->getValue() ?? '')),
    ];
    $parts = array_filter($parts);

    $match = null;
    foreach ($origRecords as $rec) {
        $allPartsFound = true;
        foreach ($parts as $p) {
            if (strpos($rec['full_name'], $p) === false) {
                $allPartsFound = false;
                break;
            }
        }
        if ($allPartsFound) {
            $match = $rec;
            break;
        }
    }

    if ($match) {
        if (in_array($match['full_name'], $handledNames) || in_array($match['full_name'], $excludedNames)) {
            continue;
        }
        $finalIntegrationList[] = array_merge($match, ['curp' => $curp]);
    } else {
        echo "Warning: Could not match CURP $curp (".implode(' ', $parts).")\n";
    }
}

echo 'Found '.count($finalIntegrationList)." students to integrate.\n";

function slugify($text)
{
    $text = preg_replace('/[^\p{L}\p{N}]+/u', '', $text);

    return strtolower($text);
}

$dir = 'DATOS_CORREGIDOS';
foreach ($finalIntegrationList as $s) {
    if (! $s['group']) {
        echo "Error: No group found for {$s['full_name']}. Skipping.\n";

        continue;
    }

    $file = "$dir/{$s['group']}.xlsx";
    if (! file_exists($file)) {
        echo "Error: File $file not found for group {$s['group']}. Skipping.\n";

        continue;
    }

    echo "Processing {$s['full_name']} into {$s['group']}...\n";

    $tmpFile = "tmp/{$s['group']}_final.xlsx";
    copy($file, $tmpFile);

    $spreadsheet = IOFactory::load($tmpFile);
    $alSheet = null;
    $pdSheet = null;
    foreach ($spreadsheet->getSheetNames() as $name) {
        if (stripos($name, 'Alumno') !== false) {
            $alSheet = $spreadsheet->getSheetByName($name);
        }
        if (stripos($name, 'Padre') !== false) {
            $pdSheet = $spreadsheet->getSheetByName($name);
        }
    }

    if ($alSheet && $pdSheet) {
        $nextAl = $alSheet->getHighestRow() + 1;
        $rowAl = [$s['full_name'], $s['turno'], $s['group'], $s['address'], null, "{$s['item1']} / {$s['item2']}", $s['curp']];
        foreach ($rowAl as $c => $v) {
            $alSheet->setCellValueByColumnAndRow($c + 1, $nextAl, $v);
        }

        $nextPd = $pdSheet->getHighestRow() + 1;
        $slug = slugify($s['full_name']);
        $rowPd = ["Padre de {$s['full_name']}", "{$slug}@escuela.edu.mx", '', bin2hex(random_bytes(5)), 'Padre', ''];
        foreach ($rowPd as $c => $v) {
            $pdSheet->setCellValueByColumnAndRow($c + 1, $nextPd, $v);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tmpFile);

        $winTmp = str_replace('/', '\\', $tmpFile);
        $winFile = str_replace('/', '\\', $file);
        exec("cmd /c \"move /y $winTmp $winFile\"");
        echo "  Saved.\n";
    }
}
