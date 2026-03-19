<?php

use Illuminate\Contracts\Console\Kernel;
use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// 1. Identify missing students
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

$curpDataFile = 'CURP DATA.xlsx';
$spreadsheetCurp = IOFactory::load($curpDataFile);
$sheetCurp = $spreadsheetCurp->getActiveSheet();
$highestRowCurp = $sheetCurp->getHighestRow();

$missing = [];
for ($row = 2; $row <= $highestRowCurp; $row++) {
    $curp = strtoupper(trim($sheetCurp->getCellByColumnAndRow(1, $row)->getValue() ?? ''));
    if ($curp && ! isset($prodCurps[$curp])) {
        $paterno = trim($sheetCurp->getCellByColumnAndRow(3, $row)->getValue() ?? '');
        $materno = trim($sheetCurp->getCellByColumnAndRow(4, $row)->getValue() ?? '');
        $nombre = trim($sheetCurp->getCellByColumnAndRow(2, $row)->getValue() ?? '');

        $missing[$curp] = [
            'paterno' => strtoupper($paterno),
            'materno' => strtoupper($materno),
            'nombre' => strtoupper($nombre),
            'curp' => $curp,
            'gdata_gpo' => $sheetCurp->getCellByColumnAndRow(5, $row)->getValue().$sheetCurp->getCellByColumnAndRow(6, $row)->getValue(),
        ];
    }
}

// Filter out handled/excluded
$handledNames = [
    'CRUZ GARCIA CRISTIAN ALEJANDRO', 'MONCADA RAMIREZ ALFREDO', 'PIZARRO LUCIO JESUS MANUEL',
    'ESCOBEDO VILLAZANA ANGELA YANET', 'VASQUEZ MENDEZ NAOMI ELIZABETH', 'JASSO MEDELLIN CRISTIAN TADEO',
];
$excludedNames = ['GALLEGOS MEDINA ROGELIO ALBERTO'];

foreach ($missing as $curp => $s) {
    $fullName = "{$s['paterno']} {$s['materno']} {$s['nombre']}";
    if (in_array($fullName, $handledNames) || in_array($fullName, $excludedNames)) {
        unset($missing[$curp]);
    }
}

// 2. Load ORIGINAL for details
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
            'gpo' => strtoupper(trim($sheetOrig->getCellByColumnAndRow(5, $row)->getValue() ?? '')),
            'turno' => strtoupper(trim($sheetOrig->getCellByColumnAndRow(6, $row)->getValue() ?? '')),
        ];
    }
}

function cleanStr($str)
{
    return str_replace([' ', '/', 'º', 'º '], '', $str);
}

function slugify($text)
{
    $text = preg_replace('~[^\pL\d]+~u', '', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = strtolower($text);

    return $text;
}

// 3. Process integration
$dir = 'DATOS_CORREGIDOS';
$toProcess = [];

foreach ($missing as $curp => $s) {
    $bestName = "{$s['paterno']} {$s['materno']} {$s['nombre']}";
    $match = $origRecords[$bestName] ?? null;

    // Fuzzy matching if no exact match (sometimes Materno/Nombre order or spaces differ)
    if (! $match) {
        foreach ($origRecords as $oname => $orec) {
            // Simple check: do all parts exist in the name?
            if (strpos($oname, $s['paterno']) !== false && strpos($oname, $s['nombre']) !== false) {
                $match = $orec;
                $bestName = $oname;
                break;
            }
        }
    }

    if (! $match) {
        echo "Could not find mapping for $bestName\n";

        continue;
    }

    $groupCode = cleanStr($match['gpo']);
    if (! $groupCode) {
        $groupCode = cleanStr($s['gdata_gpo']);
    }

    $toProcess[$groupCode][] = [
        'name' => $bestName,
        'curp' => $curp,
        'turno' => $match['turno'] ?: 'UNKNOWN',
        'dir' => $match['dir'],
        'item1' => $match['item1'],
        'item2' => $match['item2'],
    ];
}

foreach ($toProcess as $group => $students) {
    $file = "$dir/$group.xlsx";
    if (! file_exists($file)) {
        echo "Group file $file not found for group $group\n";

        continue;
    }

    echo "Processing Group $group ($file)...\n";

    // Use tmp copy strategy
    $tmpFile = "tmp/{$group}_integration.xlsx";
    copy($file, $tmpFile);

    $spreadsheet = IOFactory::load($tmpFile);

    $alumnoSheet = null;
    $padreSheet = null;
    foreach ($spreadsheet->getSheetNames() as $name) {
        if (stripos($name, 'Alumno') !== false) {
            $alumnoSheet = $spreadsheet->getSheetByName($name);
        }
        if (stripos($name, 'Padre') !== false) {
            $padreSheet = $spreadsheet->getSheetByName($name);
        }
    }

    if (! $alumnoSheet || ! $padreSheet) {
        echo "Sheets not found in $group file.\n";

        continue;
    }

    $nextAlRow = $alumnoSheet->getHighestRow() + 1;
    $nextPdRow = $padreSheet->getHighestRow() + 1;

    foreach ($students as $s) {
        // Alumno
        $rowAl = [
            $s['name'],
            $s['turno'],
            $group, // Or use specific format from file
            $s['dir'],
            null,
            $s['item1'].($s['item2'] ? ' / '.$s['item2'] : ''),
            $s['curp'],
        ];
        foreach ($rowAl as $col => $val) {
            $alumnoSheet->setCellValueByColumnAndRow($col + 1, $nextAlRow, $val);
        }
        echo "  Integrated Alumno: {$s['name']} at $nextAlRow\n";
        $nextAlRow++;

        // Padre (Name-based email)
        $slug = slugify($s['name']);
        $rowPd = [
            "Padre de {$s['name']}",
            "{$slug}@escuela.edu.mx",
            '', // Phone
            bin2hex(random_bytes(5)),
            'Padre',
            '',
        ];
        foreach ($rowPd as $col => $val) {
            $padreSheet->setCellValueByColumnAndRow($col + 1, $nextPdRow, $val);
        }
        echo "  Integrated Padre: {$slug}@escuela... at $nextPdRow\n";
        $nextPdRow++;
    }

    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save($tmpFile);

    // Move back
    exec("cmd /c \"move /y $tmpFile $file\"");
    echo "Saved $file\n";
}
