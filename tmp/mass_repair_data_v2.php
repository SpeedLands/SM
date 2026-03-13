<?php

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$base = 'c:/Users/juanp/Desktop/Apps/sm/';
$alumnosPath = $base.'alumnos_2026-03-11.xlsx';
$padresPath = $base.'padres_2026-03-11.xlsx';
$originalPath = $base.'ORIGINAL.xlsx';

function cleanPhone($phone)
{
    if (! $phone) {
        return '';
    }
    $clean = preg_replace('/[^0-9]/', '', (string) $phone);
    if (strlen($clean) > 10) {
        $clean = substr($clean, 0, 10);
    }

    return $clean;
}

set_exception_handler(function ($e) {
    echo 'ERROR: '.$e->getMessage()."\n";
    echo 'FILE: '.$e->getFile().' LINE: '.$e->getLine()."\n";
    echo 'TRACE: '.$e->getTraceAsString()."\n";
});

// 1. Map Original Data
echo "Loading ORIGINAL.xlsx...\n";
$ssOrig = IOFactory::load($originalPath);
echo "Mapping ORIGINAL.xlsx rows...\n";
$sheetOrig = $ssOrig->getActiveSheet();
$rowsOrig = $sheetOrig->getHighestRow();
$originalMap = [];
for ($i = 2; $i <= $rowsOrig; $i++) {
    $nameRaw = $sheetOrig->getCell('A'.$i)->getValue();
    $name = strtoupper(trim((string) $nameRaw));
    if (! $name) {
        continue;
    }
    $originalMap[$name] = [
        'mama' => cleanPhone($sheetOrig->getCell('B'.$i)->getValue()),
        'papa' => cleanPhone($sheetOrig->getCell('C'.$i)->getValue()),
        'original_name' => (string) $nameRaw,
    ];
}
echo 'Map created with '.count($originalMap)." entries.\n";

/*
// 2. Repair Alumnos
echo "Repairing Alumnos...\n";
$ssA = IOFactory::load($alumnosPath);
$sheetA = $ssA->getActiveSheet();
$rowsA = $sheetA->getHighestRow();
for ($i = 2; $i <= $rowsA; $i++) {
    $nombreCell = $sheetA->getCell('A' . $i);
    if (!$nombreCell) continue;
    $nombre = strtoupper(trim((string)$nombreCell->getValue()));
    if (isset($originalMap[$nombre])) {
        $tel = $originalMap[$nombre]['mama'] ?: $originalMap[$nombre]['papa'];
        if ($tel) {
            $sheetA->setCellValue('E' . $i, $tel);
        }
    }
}
$writerA = new Xlsx($ssA);
$writerA->save($base . 'alumnos_2026-03-11_REPAIRED.xlsx');
*/

// 3. Repair Padres with Deduplication
echo "Repairing and Deduplicating Padres...\n";
$ssP = IOFactory::load($padresPath);
$sheetP = $ssP->getActiveSheet();
$rowsP = $sheetP->getHighestRow();

$newPadresData = [];
for ($i = 2; $i <= $rowsP; $i++) {
    $nombreCell = $sheetP->getCell('A'.$i);
    if (! $nombreCell) {
        continue;
    }
    $nombreFull = trim((string) $nombreCell->getValue());
    if (! $nombreFull) {
        continue;
    }

    $studentName = strtoupper(preg_replace('/^(Madre de |Padre de )/i', '', $nombreFull));
    $type = (stripos($nombreFull, 'Madre') !== false) ? 'mama' : 'papa';

    // Key for deduplication
    $key = $studentName.'|'.$type;

    if (! isset($newPadresData[$key])) {
        $newPadresData[$key] = [
            'original_name_on_sheet' => $nombreFull,
            'student_name' => $studentName,
            'type' => $type,
            'correo' => (string) $sheetP->getCell('B'.$i)->getValue(),
            'tel' => (string) $sheetP->getCell('C'.$i)->getValue(),
            'pass' => (string) $sheetP->getCell('D'.$i)->getValue(),
            'rol' => (string) $sheetP->getCell('E'.$i)->getValue(),
            'occ' => (string) $sheetP->getCell('F'.$i)->getValue(),
        ];
    }
}
echo 'Found '.count($newPadresData)." unique parent-student pairs.\n";

// Apply Logic to deduplicated data
foreach ($newPadresData as $key => &$data) {
    if (isset($originalMap[$data['student_name']])) {
        $orig = $originalMap[$data['student_name']];
        $ownPhone = $orig[$data['type']];
        $otherPhone = ($data['type'] === 'mama') ? $orig['papa'] : $orig['mama'];
        $bestPhone = $ownPhone ?: $otherPhone;

        if ($bestPhone) {
            $data['correo'] = $bestPhone.'@escuela.edu.mx';
            $data['tel'] = $bestPhone;
            $data['pass'] = $bestPhone;
        } else {
            // No phone for Mom or Dad in original list
            $cleanName = str_replace(' ', '', strtolower($data['student_name']));
            $data['correo'] = $cleanName.($data['type'] === 'mama' ? 'mama' : 'papa').'@escuela.edu.mx';
            $data['tel'] = 'password';
            $data['pass'] = 'password';
        }
    }
}

// Write new spreadsheet for Padres
echo "Writing repaired Padres file...\n";
$newSsP = new Spreadsheet;
$newSheetP = $newSsP->getActiveSheet();
// Header
$newSheetP->setCellValue('A1', 'Nombre');
$newSheetP->setCellValue('B1', 'Correo');
$newSheetP->setCellValue('C1', 'Teléfono');
$newSheetP->setCellValue('D1', 'Contraseña');
$newSheetP->setCellValue('E1', 'Rol');
$newSheetP->setCellValue('F1', 'Ocupación');

$r = 2;
foreach ($newPadresData as $data) {
    $newSheetP->setCellValue('A'.$r, $data['original_name_on_sheet']);
    $newSheetP->setCellValue('B'.$r, $data['correo']);
    $newSheetP->setCellValue('C'.$r, $data['tel']);
    $newSheetP->setCellValue('D'.$r, $data['pass']);
    $newSheetP->setCellValue('E'.$r, $data['rol']);
    $newSheetP->setCellValue('F'.$r, $data['occ']);
    $r++;
}

$writerP = new Xlsx($newSsP);
$writerP->save($base.'padres_2026-03-11_REPAIRED.xlsx');

echo "Repair complete. Saved as _REPAIRED files.\n";
