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

    $irregulars = ['password', 'contraseña', '123456', '12345678'];

    return in_array($v, $irregulars);
}

function slugify($text)
{
    if (! $text) {
        return '';
    }
    $text = mb_strtolower((string) $text, 'UTF-8');
    $text = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'],
        ['a', 'e', 'i', 'o', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'n'],
        $text
    );

    return str_replace([' ', '.', ',', '-', '_'], '', $text);
}

function generate_random_password($length = 10)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';

    return substr(str_shuffle($chars), 0, $length);
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
$writerA->save($base.'alumnos_2026-03-11_REPAIRED_V6.xlsx');
echo "Alumnos repaired.\n";

// 3. Repair Padres
echo "Repairing Padres...\n";
$readerP = IOFactory::createReaderForFile($padresPath);
$readerP->setReadDataOnly(true);
$ssP = $readerP->load($padresPath);
$sheetP = $ssP->getActiveSheet();
$rowsP = $sheetP->getHighestRow();

$parentRecords = []; // [slugifiedStudentName][type] = record
$studentNamesInExport = [];

for ($i = 2; $i <= $rowsP; $i++) {
    $fullName = trim((string) $sheetP->getCellByColumnAndRow(1, $i)->getValue());
    if (! $fullName) {
        continue;
    }

    $type = (stripos($fullName, 'Madre') !== false) ? 'mama' : 'papa';
    $studentNameRaw = preg_replace('/^(Madre de |Padre de )/i', '', $fullName);
    $studentNameSlug = slugify($studentNameRaw);

    $studentNamesInExport[$studentNameSlug] = strtoupper(trim($studentNameRaw));

    $record = [
        'full' => $fullName,
        'correo' => (string) $sheetP->getCellByColumnAndRow(2, $i)->getValue(),
        'tel' => (string) $sheetP->getCellByColumnAndRow(3, $i)->getValue(),
        'pass' => (string) $sheetP->getCellByColumnAndRow(4, $i)->getValue(),
        'rol' => (string) $sheetP->getCellByColumnAndRow(5, $i)->getValue(),
        'occ' => (string) $sheetP->getCellByColumnAndRow(6, $i)->getValue(),
    ];

    if (! isset($parentRecords[$studentNameSlug][$type]) || is_phone_irregular($parentRecords[$studentNameSlug][$type]['tel'])) {
        $parentRecords[$studentNameSlug][$type] = $record;
    }
}

// Prepare combined student list (Master + Export others)
$masterSlugs = [];
foreach (array_keys($originalMap) as $name) {
    $masterSlugs[slugify($name)] = $name;
}

$allSlugs = array_unique(array_merge(array_keys($masterSlugs), array_keys($studentNamesInExport)));

$finalPadres = [];

foreach ($allSlugs as $slug) {
    $studentName = $masterSlugs[$slug] ?? $studentNamesInExport[$slug];

    foreach (['mama', 'papa'] as $type) {
        $masterPhones = $originalMap[$studentName] ?? null;
        $phone = $masterPhones ? $masterPhones[$type] : null;

        $existsInExport = isset($parentRecords[$slug][$type]);
        $exportData = $existsInExport ? $parentRecords[$slug][$type] : null;

        if (! $phone && ! $existsInExport) {
            continue;
        }

        $prefix = ($type === 'mama') ? 'Madre de ' : 'Padre de ';

        $newRecord = [
            'full' => $prefix.$studentName,
            'correo' => '',
            'tel' => '',
            'pass' => '', // placeholder
            'rol' => 'Padre de familia',
            'occ' => $exportData['occ'] ?? '',
        ];

        // 1. Check password preservation
        if ($exportData && ! is_password_irregular($exportData['pass'])) {
            $newRecord['pass'] = $exportData['pass'];
        }

        // 2. Determine base info
        if ($phone) {
            $newRecord['tel'] = $phone;
            $newRecord['correo'] = $phone.'@escuela.edu.mx';

            if (! $newRecord['pass'] || is_password_irregular($newRecord['pass'])) {
                // If it exists in export but had a bad password, or it's NEW
                if ($existsInExport) {
                    $newRecord['pass'] = $phone; // Re-use phone as pass for legacy reasons or if explicitly requested before
                } else {
                    $newRecord['pass'] = generate_random_password(); // New parents get random
                }
            }
        } else {
            // No master phone for this type
            if ($exportData && ! is_phone_irregular($exportData['tel'])) {
                $newRecord['tel'] = $exportData['tel'];
                $newRecord['correo'] = $exportData['correo'];
            } else {
                // FALLBACK: Family level logic
                $anyMasterPhone = $masterPhones ? ($masterPhones['mama'] ?: $masterPhones['papa']) : null;

                if ($anyMasterPhone) {
                    $newRecord['correo'] = $anyMasterPhone.'@escuela.edu.mx';
                    $newRecord['tel'] = '';
                    if (! $newRecord['pass'] || is_password_irregular($newRecord['pass'])) {
                        if ($existsInExport) {
                            $newRecord['pass'] = $anyMasterPhone;
                        } else {
                            $newRecord['pass'] = generate_random_password();
                        }
                    }
                } else {
                    // No master phones at all
                    $emailName = slugify($studentName);
                    $newRecord['correo'] = $emailName.$type.'@escuela.edu.mx';
                    $newRecord['tel'] = '';
                    if (! $newRecord['pass'] || is_password_irregular($newRecord['pass'])) {
                        $newRecord['pass'] = generate_random_password();
                    }
                }
            }
        }

        // Final cleaning
        if (is_phone_irregular($newRecord['tel'])) {
            $newRecord['tel'] = '';
        }
        if (! $newRecord['pass']) {
            $newRecord['pass'] = generate_random_password();
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
$writerP->save($base.'padres_2026-03-11_REPAIRED_V6.xlsx');
echo 'Padres repaired. Final count: '.count($finalPadres)." records.\n";
