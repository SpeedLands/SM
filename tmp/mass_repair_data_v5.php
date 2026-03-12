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

function clean_phone($val)
{
    if (! $val) {
        return null;
    }
    $cleaned = preg_replace('/[^0-9]/', '', (string) $val);

    return (strlen($cleaned) >= 7) ? $cleaned : null;
}

function is_phone_irregular($val)
{
    if ($val === null) {
        return true;
    }
    $v = strtolower(trim((string) $val));
    if (! $v || $v === '#name?') {
        return true;
    }

    // Numeric strings shorter than 7 digits are irregular FOR PHONES
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

function is_password_irregular($val)
{
    if ($val === null) {
        return true;
    }
    $v = strtolower(trim((string) $val));
    if (! $v || $v === '#name?') {
        return true;
    }

    // We are MUCH more lenient with passwords. Only common placeholders are irregular.
    $irregulars = ['password', 'contraseña', '123456', '12345678'];

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
        if (is_phone_irregular($existingTel)) {
            $sheetA->getCellByColumnAndRow(5, $i)->setValueExplicit('', DataType::TYPE_STRING);
        }
    }
}
$writerA = new Xlsx($ssA);
$writerA->save($base.'alumnos_2026-03-11_REPAIRED_V5.xlsx');
echo "Alumnos repaired.\n";

// 3. Repair Padres
echo "Repairing Padres...\n";
$readerP = IOFactory::createReaderForFile($padresPath);
$readerP->setReadDataOnly(true);
$ssP = $readerP->load($padresPath);
$sheetP = $ssP->getActiveSheet();
$rowsP = $sheetP->getHighestRow();

$parentRecords = []; // [studentName][type] = record
$studentNamesInExport = [];

for ($i = 2; $i <= $rowsP; $i++) {
    $fullName = trim((string) $sheetP->getCellByColumnAndRow(1, $i)->getValue());
    if (! $fullName) {
        continue;
    }

    $type = (stripos($fullName, 'Madre') !== false) ? 'mama' : 'papa';
    $studentName = strtoupper(preg_replace('/^(Madre de |Padre de )/i', '', $fullName));

    $studentNamesInExport[$studentName] = true;

    $record = [
        'full' => $fullName,
        'correo' => (string) $sheetP->getCellByColumnAndRow(2, $i)->getValue(),
        'tel' => (string) $sheetP->getCellByColumnAndRow(3, $i)->getValue(),
        'pass' => (string) $sheetP->getCellByColumnAndRow(4, $i)->getValue(),
        'rol' => (string) $sheetP->getCellByColumnAndRow(5, $i)->getValue(),
        'occ' => (string) $sheetP->getCellByColumnAndRow(6, $i)->getValue(),
    ];

    // Deduplication logic: prefer record with non-placeholder data
    if (! isset($parentRecords[$studentName][$type]) || is_phone_irregular($parentRecords[$studentName][$type]['tel'])) {
        $parentRecords[$studentName][$type] = $record;
    }
}

// Prepare combined student list (Master + Export others)
$allStudentNames = array_unique(array_merge(array_keys($originalMap), array_keys($studentNamesInExport)));

$finalPadres = [];

foreach ($allStudentNames as $studentName) {
    foreach (['mama', 'papa'] as $type) {
        $masterPhones = $originalMap[$studentName] ?? null;
        $phone = $masterPhones ? $masterPhones[$type] : null;

        $existsInExport = isset($parentRecords[$studentName][$type]);
        $exportData = $existsInExport ? $parentRecords[$studentName][$type] : null;

        if (! $phone && ! $existsInExport) {
            // If not in master for this type AND not in export, skip?
            // Except if the other parent HAS a phone in master, maybe we should create this account?
            // User said: "si tienen los dos numero ... tienen cuenta los dos"
            // But if master doesn't have a number for this type, we only create if the export already had them.
            continue;
        }

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
        if ($exportData && ! is_password_irregular($exportData['pass'])) {
            $newRecord['pass'] = $exportData['pass'];
        }

        if ($phone) {
            $newRecord['tel'] = $phone;
            $newRecord['correo'] = $phone.'@escuela.edu.mx';
            // If password was irregular, set it to phone number
            if ($newRecord['pass'] === 'password' || is_password_irregular($newRecord['pass'])) {
                $newRecord['pass'] = $phone;
            }
        } else {
            // No master phone for this type
            if ($exportData && ! is_phone_irregular($exportData['tel'])) {
                // Keep existing good phone/email from export
                $newRecord['tel'] = $exportData['tel'];
                $newRecord['correo'] = $exportData['correo'];
            } else {
                // FALLBACK: Does ANY parent in this family have a phone in master?
                $anyMasterPhone = $masterPhones ? ($masterPhones['mama'] ?: $masterPhones['papa']) : null;

                if ($anyMasterPhone) {
                    $newRecord['correo'] = $anyMasterPhone.'@escuela.edu.mx';
                    $newRecord['tel'] = ''; // Clear placeholder
                    if (is_password_irregular($newRecord['pass'])) {
                        $newRecord['pass'] = $anyMasterPhone;
                    }
                } else {
                    // No phones at all in master for this family
                    $emailName = slugify($studentName);
                    $newRecord['correo'] = $emailName.$type.'@escuela.edu.mx';
                    $newRecord['tel'] = ''; // Clear placeholder
                    // pass remains 'password' or preserved from export if valid
                }
            }
        }

        // Final cleaning of phone placeholders
        if (is_phone_irregular($newRecord['tel'])) {
            $newRecord['tel'] = '';
        }

        $finalPadres[] = $newRecord;
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
$writerP->save($base.'padres_2026-03-11_REPAIRED_V5.xlsx');
echo 'Padres repaired. Final count: '.count($finalPadres)." records.\n";
