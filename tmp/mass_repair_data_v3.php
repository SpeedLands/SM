<?php

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$base = 'c:/Users/juanp/Desktop/Apps/sm/';
$alumnosPath = $base.'alumnos_2026-03-11.xlsx';
$padresPath = $base.'padres_2026-03-11.xlsx';
$originalPath = $base.'ORIGINAL.xlsx';

function clean_phone($phone)
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
    echo 'TRACE: '.$e->getTraceAsString()."\n";
});

use PhpOffice\PhpSpreadsheet\Cell\DataType;

// ... (clean_phone and exception handler)

// 1. Map Original Data
echo "Mapping ORIGINAL.xlsx...\n";
$readerOrig = IOFactory::createReaderForFile($originalPath);
$readerOrig->setReadDataOnly(true);
$ssOrig = $readerOrig->load($originalPath);
$sheetOrig = $ssOrig->getActiveSheet();
$rowsOrig = $sheetOrig->getHighestRow();
$originalMap = [];
for ($i = 2; $i <= $rowsOrig; $i++) {
    $name = strtoupper(trim((string) $sheetOrig->getCellByColumnAndRow(1, $i)->getValue()));
    if (! $name) {
        continue;
    }
    $originalMap[$name] = [
        'mama' => clean_phone($sheetOrig->getCellByColumnAndRow(2, $i)->getValue()),
        'papa' => clean_phone($sheetOrig->getCellByColumnAndRow(3, $i)->getValue()),
    ];
}
echo 'Map created with '.count($originalMap)." students.\n";

// 2. Repair Alumnos
echo "Repairing Alumnos...\n";
$readerA = IOFactory::createReaderForFile($alumnosPath);
$readerA->setReadDataOnly(true);
$ssA = $readerA->load($alumnosPath);
$sheetA = $ssA->getActiveSheet();
$rowsA = $sheetA->getHighestRow();
for ($i = 2; $i <= $rowsA; $i++) {
    $nombre = strtoupper(trim((string) $sheetA->getCellByColumnAndRow(1, $i)->getValue()));
    if (isset($originalMap[$nombre])) {
        $tel = $originalMap[$nombre]['mama'] ?: $originalMap[$nombre]['papa'];
        if ($tel) {
            $sheetA->getCellByColumnAndRow(5, $i)->setValueExplicit($tel, DataType::TYPE_STRING);
        }
    }
}
$writerA = new Xlsx($ssA);
$writerA->save($base.'alumnos_2026-03-11_REPAIRED.xlsx');
echo "Alumnos repaired.\n";

// 3. Repair Padres
echo "Repairing Padres...\n";
$readerP = IOFactory::createReaderForFile($padresPath);
$readerP->setReadDataOnly(true);
$ssP = $readerP->load($padresPath);
$sheetP = $ssP->getActiveSheet();
$rowsP = $sheetP->getHighestRow();

$newPadres = [];
for ($i = 2; $i <= $rowsP; $i++) {
    $fullName = trim((string) $sheetP->getCellByColumnAndRow(1, $i)->getValue());
    if (! $fullName) {
        continue;
    }

    $studentName = strtoupper(preg_replace('/^(Madre de |Padre de )/i', '', $fullName));
    $type = (stripos($fullName, 'Madre') !== false) ? 'mama' : 'papa';

    $key = $studentName.'|'.$type;

    if (! isset($newPadres[$key])) {
        $newPadres[$key] = [
            'fullName' => $fullName,
            'studentName' => $studentName,
            'type' => $type,
            'correo' => (string) $sheetP->getCellByColumnAndRow(2, $i)->getValue(),
            'tel' => (string) $sheetP->getCellByColumnAndRow(3, $i)->getValue(),
            'pass' => (string) $sheetP->getCellByColumnAndRow(4, $i)->getValue(),
            'rol' => (string) $sheetP->getCellByColumnAndRow(5, $i)->getValue(),
            'occ' => (string) $sheetP->getCellByColumnAndRow(6, $i)->getValue(),
        ];
    }
}

foreach ($newPadres as $key => &$data) {
    if (isset($originalMap[$data['studentName']])) {
        $orig = $originalMap[$data['studentName']];
        $bestPhone = $orig[$data['type']] ?: ($orig['mama'] ?: $orig['papa']);

        if ($bestPhone) {
            $data['correo'] = $bestPhone.'@escuela.edu.mx';
            $data['tel'] = $bestPhone;
            $data['pass'] = $bestPhone;
        } else {
            $emailName = str_replace(' ', '', strtolower($data['studentName']));
            $data['correo'] = $emailName.($data['type'] === 'mama' ? 'mama' : 'papa').'@escuela.edu.mx';
            $data['tel'] = 'password';
            $data['pass'] = 'password';
        }
    }
}

// Write repaired file
$newSsP = new Spreadsheet;
$newSheetP = $newSsP->getActiveSheet();
$cols = ['Nombre', 'Correo', 'Teléfono', 'Contraseña', 'Rol', 'Ocupación'];
foreach ($cols as $idx => $name) {
    $newSheetP->getCellByColumnAndRow($idx + 1, 1)->setValueExplicit($name, DataType::TYPE_STRING);
}

$r = 2;
foreach ($newPadres as $data) {
    $newSheetP->getCellByColumnAndRow(1, $r)->setValueExplicit($data['fullName'], DataType::TYPE_STRING);
    $newSheetP->getCellByColumnAndRow(2, $r)->setValueExplicit($data['correo'], DataType::TYPE_STRING);
    $newSheetP->getCellByColumnAndRow(3, $r)->setValueExplicit($data['tel'], DataType::TYPE_STRING);
    $newSheetP->getCellByColumnAndRow(4, $r)->setValueExplicit($data['pass'], DataType::TYPE_STRING);
    $newSheetP->getCellByColumnAndRow(5, $r)->setValueExplicit($data['rol'], DataType::TYPE_STRING);
    $newSheetP->getCellByColumnAndRow(6, $r)->setValueExplicit($data['occ'], DataType::TYPE_STRING);
    $r++;
}

$writerP = new Xlsx($newSsP);
$writerP->save($base.'padres_2026-03-11_REPAIRED.xlsx');
echo "Padres repaired. Complete.\n";
