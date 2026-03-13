<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Helper functions
function slugify($text)
{
    $text = preg_replace('/[^\p{L}\p{N}]+/u', '', $text);

    return strtolower($text);
}

function getGroupCode($g, $gp)
{
    if (! $g || ! $gp) {
        return null;
    }
    $g = preg_replace('/[^1-3]/', '', (string) $g);
    $gp = preg_replace('/[^A-I]/', '', strtoupper((string) $gp));

    return ($g && $gp) ? $g.$gp : null;
}

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

// 2. Load ORIGINAL records for mapping
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
            $group = $matches[1].$matches[2];
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
        'words' => array_filter(explode(' ', $name)),
    ];
}

// 3. Identify all missing students from CURP DATA and Integrate
$curpDataFile = 'CURP DATA.xlsx';
$spreadsheetCurp = IOFactory::load($curpDataFile);
$sheetCurp = $spreadsheetCurp->getActiveSheet();
$highestRowCurp = $sheetCurp->getHighestRow();

$handledNames = [
    'CRUZ GARCIA CRISTIAN ALEJANDRO', 'MONCADA RAMIREZ ALFREDO', 'PIZARRO LUCIO JESUS MANUEL',
    'ESCOBEDO VILLAZANA ANGELA YANET', 'VASQUEZ MENDEZ NAOMI ELIZABETH',
];
$excludedNames = ['GALLEGOS MEDINA ROGELIO ALBERTO'];

echo "Starting Integration...\n";

for ($row = 2; $row <= $highestRowCurp; $row++) {
    $curp = strtoupper(trim($sheetCurp->getCellByColumnAndRow(1, $row)->getValue() ?? ''));
    if (! $curp || isset($prodCurps[$curp])) {
        continue;
    }

    // Get name parts from CURP DATA
    $p1 = strtoupper(trim($sheetCurp->getCellByColumnAndRow(2, $row)->getValue() ?? ''));
    $p2 = strtoupper(trim($sheetCurp->getCellByColumnAndRow(3, $row)->getValue() ?? ''));
    $p3 = strtoupper(trim($sheetCurp->getCellByColumnAndRow(4, $row)->getValue() ?? ''));

    $cGroup = getGroupCode($sheetCurp->getCellByColumnAndRow(5, $row)->getValue(), $sheetCurp->getCellByColumnAndRow(6, $row)->getValue());

    // Try matching to ORIGINAL
    $match = null;
    $cWords = array_filter(array_merge(explode(' ', $p1), explode(' ', $p2), explode(' ', $p3)));

    foreach ($origRecords as $rec) {
        $allWordsFound = true;
        foreach ($cWords as $cw) {
            $found = false;
            foreach ($rec['words'] as $rw) {
                if ($rw === $cw) {
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                $allWordsFound = false;
                break;
            }
        }
        if ($allWordsFound) {
            $match = $rec;
            break;
        }
    }

    if ($match && (in_array($match['full_name'], $handledNames) || in_array($match['full_name'], $excludedNames))) {
        continue;
    }

    // Determine data for integration
    $finalName = $match ? $match['full_name'] : "$p2 $p3 $p1";
    $finalGroup = $match ? $match['group'] : $cGroup;
    $finalTurno = $match ? $match['turno'] : 'MATUTINO';
    $finalAddress = $match ? $match['address'] : '';
    $finalPhone = $match ? "{$match['item1']} / {$match['item2']}" : '';

    if (! $finalGroup) {
        echo "Error: No group found for $finalName (CURP: $curp). Skipping.\n";

        continue;
    }

    // Integrate
    $file = "DATOS_CORREGIDOS/$finalGroup.xlsx";
    if (! file_exists($file)) {
        echo "Error: File $file not found for student $finalName. Skipping.\n";

        continue;
    }

    echo "Integrating $finalName into $finalGroup...\n";

    $tmpFile = "tmp/{$finalGroup}_final_sync.xlsx";
    copy($file, $tmpFile);

    $spreadsheet = IOFactory::load($tmpFile);
    $alSheet = null;
    $pdSheet = null;
    foreach ($spreadsheet->getSheetNames() as $n) {
        if (stripos($n, 'Alumno') !== false) {
            $alSheet = $spreadsheet->getSheetByName($n);
        }
        if (stripos($n, 'Padre') !== false) {
            $pdSheet = $spreadsheet->getSheetByName($n);
        }
    }

    if ($alSheet && $pdSheet) {
        $nextAl = $alSheet->getHighestRow() + 1;
        $rowAl = [$finalName, $finalTurno, $finalGroup, $finalAddress, null, $finalPhone, $curp];
        foreach ($rowAl as $c => $v) {
            $alSheet->setCellValueByColumnAndRow($c + 1, $nextAl, $v);
        }

        $nextPd = $pdSheet->getHighestRow() + 1;
        $slug = slugify($finalName);
        $rowPd = ["Padre de $finalName", "{$slug}@escuela.edu.mx", '', bin2hex(random_bytes(5)), 'Padre', ''];
        foreach ($rowPd as $c => $v) {
            $pdSheet->setCellValueByColumnAndRow($c + 1, $nextPd, $v);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tmpFile);

        $winTmp = str_replace('/', '\\', $tmpFile);
        $winFile = str_replace('/', '\\', $file);
        exec("cmd /c \"move /y $winTmp $winFile\"");
        echo "  Successfully saved.\n";
    } else {
        echo "  Error: Sheets not found in $file.\n";
    }
}
echo "Integration Complete.\n";
