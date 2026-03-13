<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$base = dirname(__DIR__).'/';
$originalPath = $base.'ORIGINAL.xlsx';
$alumnosPath = $base.'alumnos_2026-03-11.xlsx';
$padresPath = $base.'padres_2026-03-11.xlsx';
$outputPath = dirname(__DIR__).'/'; // Output to root

function clean_phone($val)
{
    if (! $val) {
        return null;
    }
    $cleaned = preg_replace('/[^0-9]/', '', (string) $val);

    return (strlen($cleaned) >= 7) ? $cleaned : null;
}

function is_irregular($val)
{
    if ($val === null) {
        return true;
    }
    $v = strtolower(trim((string) $val));
    if (! $v || $v === '#name?') {
        return true;
    }

    // Numeric strings shorter than 7 digits are irregular
    $digitsOnly = preg_replace('/[^0-9]/', '', $v);
    if ($digitsOnly !== '' && strlen($digitsOnly) < 7) {
        return true;
    }

    $irregulars = [
        '123456', '1234567', '12345678', '123456789', '1234567890',
        'password', 'teléfono', 'telefono', 'n/a', 'na', 'no',
        'sin numero', 'sin número', 'contraseña', 'asdasd', 'null',
    ];

    return in_array($v, $irregulars);
}

function slugify($text)
{
    if (! $text) {
        return '';
    }
    $text = strtolower((string) $text);
    $text = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'],
        ['a', 'e', 'i', 'o', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'n'],
        $text
    );

    return str_replace([' ', '.', ',', '-', '_'], '', $text);
}

set_exception_handler(function ($e) {
    echo 'ERROR: '.$e->getMessage()."\n";
    echo 'TRACE: '.$e->getTraceAsString()."\n";
});

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
    $existingTel = $sheetA->getCellByColumnAndRow(5, $i)->getValue();

    $newTel = null;
    if (isset($originalMap[$nombre])) {
        $newTel = $originalMap[$nombre]['mama'] ?: $originalMap[$nombre]['papa'];
    }

    if ($newTel) {
        $sheetA->getCellByColumnAndRow(5, $i)->setValueExplicit($newTel, DataType::TYPE_STRING);
    } else {
        // If not in master list or no phone in master list, check if current tel is irregular
        if (is_irregular($existingTel)) {
            $sheetA->getCellByColumnAndRow(5, $i)->setValueExplicit('', DataType::TYPE_STRING);
        }
    }
}
$writerA = new Xlsx($ssA);
$writerA->save($base.'alumnos_2026-03-11_REPAIRED_V4.xlsx');
echo "Alumnos repaired.\n";

// 3. Repair Padres
echo "Repairing Padres...\n";
$readerP = IOFactory::createReaderForFile($padresPath);
$readerP->setReadDataOnly(true);
$ssP = $readerP->load($padresPath);
$sheetP = $ssP->getActiveSheet();
$rowsP = $sheetP->getHighestRow();

$parentRecords = []; // [studentName][type] = record
for ($i = 2; $i <= $rowsP; $i++) {
    $fullName = trim((string) $sheetP->getCellByColumnAndRow(1, $i)->getValue());
    if (! $fullName) {
        continue;
    }

    $type = (stripos($fullName, 'Madre') !== false) ? 'mama' : 'papa';
    $studentName = strtoupper(preg_replace('/^(Madre de |Padre de )/i', '', $fullName));

    $correo = (string) $sheetP->getCellByColumnAndRow(2, $i)->getValue();
    $tel = (string) $sheetP->getCellByColumnAndRow(3, $i)->getValue();
    $pass = (string) $sheetP->getCellByColumnAndRow(4, $i)->getValue();

    // Deduplication logic: prefer record with non-placeholder data
    $record = [
        'full' => $fullName,
        'correo' => $correo,
        'tel' => $tel,
        'pass' => $pass,
        'rol' => (string) $sheetP->getCellByColumnAndRow(5, $i)->getValue(),
        'occ' => (string) $sheetP->getCellByColumnAndRow(6, $i)->getValue(),
    ];

    if (! isset($parentRecords[$studentName][$type]) || is_irregular($parentRecords[$studentName][$type]['tel'])) {
        $parentRecords[$studentName][$type] = $record;
    }
}

$finalPadres = [];

// Apply repair logic
foreach ($originalMap as $studentName => $phones) {
    foreach (['mama', 'papa'] as $type) {
        $phone = $phones[$type];
        $existsInExport = isset($parentRecords[$studentName][$type]);
        $exportData = $existsInExport ? $parentRecords[$studentName][$type] : null;

        $prefix = ($type === 'mama') ? 'Madre de ' : 'Padre de ';

        $newRecord = [
            'full' => $prefix.$studentName,
            'correo' => '',
            'tel' => '',
            'pass' => 'password',
            'rol' => 'Padre de familia',
            'occ' => $exportData['occ'] ?? '',
        ];

        // Preserve password if it was valid
        if ($exportData && ! is_irregular($exportData['pass'])) {
            $newRecord['pass'] = $exportData['pass'];
        }

        if ($phone) {
            $newRecord['tel'] = $phone;
            $newRecord['correo'] = $phone.'@escuela.edu.mx';
            // If password was irregular, set it to phone number
            if ($newRecord['pass'] === 'password' || is_irregular($newRecord['pass'])) {
                $newRecord['pass'] = $phone;
            }
        } else {
            // No master phone
            if ($exportData && ! is_irregular($exportData['tel'])) {
                // Keep existing good phone/email
                $newRecord['tel'] = $exportData['tel'];
                $newRecord['correo'] = $exportData['correo'];
            } else {
                // FALLBACK: Name-based email
                $familyHasPhone = $phones['mama'] || $phones['papa'];
                if (! $familyHasPhone) {
                    $emailName = slugify($studentName);
                    $newRecord['correo'] = $emailName.$type.'@escuela.edu.mx';
                } else {
                    $anyPhone = $phones['mama'] ?: $phones['papa'];
                    $newRecord['correo'] = $anyPhone.'@escuela.edu.mx';
                    $newRecord['tel'] = '';
                    if (is_irregular($newRecord['pass'])) {
                        $newRecord['pass'] = $anyPhone;
                    }
                }
            }
        }

        // Clean blank phone placeholders
        if (is_irregular($newRecord['tel'])) {
            $newRecord['tel'] = '';
        }

        // Only add if we have a phone OR they already existed in the export
        if ($phone || $existsInExport) {
            $finalPadres[] = $newRecord;
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
foreach ($finalPadres as $data) {
    $newSheetP->getCellByColumnAndRow(1, $r)->setValueExplicit($data['full'], DataType::TYPE_STRING);
    $newSheetP->getCellByColumnAndRow(2, $r)->setValueExplicit($data['correo'], DataType::TYPE_STRING);
    $newSheetP->getCellByColumnAndRow(3, $r)->setValueExplicit($data['tel'], DataType::TYPE_STRING);
    $newSheetP->getCellByColumnAndRow(4, $r)->setValueExplicit($data['pass'], DataType::TYPE_STRING);
    $newSheetP->getCellByColumnAndRow(5, $r)->setValueExplicit($data['rol'], DataType::TYPE_STRING);
    $newSheetP->getCellByColumnAndRow(6, $r)->setValueExplicit($data['occ'], DataType::TYPE_STRING);
    $r++;
}

$writerP = new Xlsx($newSsP);
$writerP->save($base.'padres_2026-03-11_REPAIRED_V4.xlsx');
echo 'Padres repaired. Final count: '.count($finalPadres)." records.\n";
