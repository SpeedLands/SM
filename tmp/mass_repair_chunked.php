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
$chunkSize = 500;

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
    $digitsOnly = preg_replace('/[^0-9]/', '', $v);
    if ($digitsOnly !== '' && strlen($digitsOnly) < 7) {
        return true;
    }
    $irregulars = ['123456', '1234567', '12345678', '123456789', '1234567890', 'password', 'teléfono', 'telefono', 'n/a', 'na', 'no', 'sin numero', 'sin número', 'contraseña', 'asdasd', 'null'];

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
    $text = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'], ['a', 'e', 'i', 'o', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'n'], $text);

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

// 2. Repair Alumnos (Save as chunks)
echo "Repairing Alumnos...\n";
$readerA = IOFactory::createReaderForFile($alumnosPath);
$readerA->setReadDataOnly(true);
$ssA = $readerA->load($alumnosPath);
$sheetA = $ssA->getActiveSheet();
$rowsA = $sheetA->getHighestRow();

$alumnosData = [];
for ($i = 2; $i <= $rowsA; $i++) {
    $nombre = strtoupper(trim((string) $sheetA->getCellByColumnAndRow(1, $i)->getValue()));
    if (! $nombre) {
        continue;
    }
    $existingTel = (string) $sheetA->getCellByColumnAndRow(5, $i)->getValue();

    $newTel = '';
    if (isset($originalMap[$nombre])) {
        $newTel = $originalMap[$nombre]['mama'] ?: $originalMap[$nombre]['papa'];
    } elseif (! is_phone_irregular($existingTel)) {
        $newTel = $existingTel;
    }

    $alumnosData[] = [
        'nombre' => $nombre,
        'grado' => $sheetA->getCellByColumnAndRow(2, $i)->getValue(),
        'grupo' => $sheetA->getCellByColumnAndRow(3, $i)->getValue(),
        'turno' => $sheetA->getCellByColumnAndRow(4, $i)->getValue(),
        'tel' => (string) $newTel,
    ];
}

$alumnosChunks = array_chunk($alumnosData, $chunkSize);
foreach ($alumnosChunks as $idx => $chunk) {
    $newSs = new Spreadsheet;
    $newSheet = $newSs->getActiveSheet();
    $cols = ['Nombre', 'Grado', 'Grupo', 'Turno', 'Teléfono'];
    foreach ($cols as $cIdx => $cName) {
        $newSheet->getCellByColumnAndRow($cIdx + 1, 1)->setValueExplicit($cName, DataType::TYPE_STRING);
    }
    $rIdx = 2;
    foreach ($chunk as $row) {
        $newSheet->getCellByColumnAndRow(1, $rIdx)->setValueExplicit($row['nombre'], DataType::TYPE_STRING);
        $newSheet->getCellByColumnAndRow(2, $rIdx)->setValueExplicit($row['grado'], DataType::TYPE_STRING);
        $newSheet->getCellByColumnAndRow(3, $rIdx)->setValueExplicit($row['grupo'], DataType::TYPE_STRING);
        $newSheet->getCellByColumnAndRow(4, $rIdx)->setValueExplicit($row['turno'], DataType::TYPE_STRING);
        $newSheet->getCellByColumnAndRow(5, $rIdx)->setValueExplicit($row['tel'], DataType::TYPE_STRING);
        $rIdx++;
    }
    $writer = new Xlsx($newSs);
    $filename = $base.'alumnos_2026-03-11_CHUNK_'.($idx + 1).'.xlsx';
    $writer->save($filename);
    echo 'Saved Alumnos Chunk '.($idx + 1)." ($filename)\n";
}

// 3. Repair Padres (Save as chunks)
echo "Repairing Padres...\n";
$readerP = IOFactory::createReaderForFile($padresPath);
$readerP->setReadDataOnly(true);
$ssP = $readerP->load($padresPath);
$sheetP = $ssP->getActiveSheet();
$rowsP = $sheetP->getHighestRow();

$parentRecords = [];
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
            'pass' => '',
            'rol' => 'Padre de familia',
            'occ' => $exportData['occ'] ?? '',
        ];

        if ($exportData && ! is_password_irregular($exportData['pass'])) {
            $newRecord['pass'] = $exportData['pass'];
        }

        if ($phone) {
            $newRecord['tel'] = $phone;
            $newRecord['correo'] = $phone.'@escuela.edu.mx';
            if (! $newRecord['pass'] || is_password_irregular($newRecord['pass'])) {
                if ($existsInExport) {
                    $newRecord['pass'] = $phone;
                } else {
                    $newRecord['pass'] = generate_random_password();
                }
            }
        } else {
            if ($exportData && ! is_phone_irregular($exportData['tel'])) {
                $newRecord['tel'] = $exportData['tel'];
                $newRecord['correo'] = $exportData['correo'];
            } else {
                $anyMasterPhone = $masterPhones ? ($masterPhones['mama'] ?: $masterPhones['papa']) : null;
                if ($anyMasterPhone) {
                    $newRecord['correo'] = $anyMasterPhone.'@escuela.edu.mx';
                    if (! $newRecord['pass'] || is_password_irregular($newRecord['pass'])) {
                        if ($existsInExport) {
                            $newRecord['pass'] = $anyMasterPhone;
                        } else {
                            $newRecord['pass'] = generate_random_password();
                        }
                    }
                } else {
                    $emailName = slugify($studentName);
                    $newRecord['correo'] = $emailName.$type.'@escuela.edu.mx';
                    if (! $newRecord['pass'] || is_password_irregular($newRecord['pass'])) {
                        $newRecord['pass'] = generate_random_password();
                    }
                }
            }
        }
        if (is_phone_irregular($newRecord['tel'])) {
            $newRecord['tel'] = '';
        }
        if (! $newRecord['pass']) {
            $newRecord['pass'] = generate_random_password();
        }

        $finalPadres[] = $newRecord;
    }
}

$padresChunks = array_chunk($finalPadres, $chunkSize);
foreach ($padresChunks as $idx => $chunk) {
    $newSs = new Spreadsheet;
    $newSheet = $newSs->getActiveSheet();
    $cols = ['Nombre', 'Correo', 'Teléfono', 'Contraseña', 'Rol', 'Ocupación'];
    foreach ($cols as $cIdx => $cName) {
        $newSheet->getCellByColumnAndRow($cIdx + 1, 1)->setValueExplicit($cName, DataType::TYPE_STRING);
    }
    $rIdx = 2;
    foreach ($chunk as $row) {
        $newSheet->getCellByColumnAndRow(1, $rIdx)->setValueExplicit($row['full'], DataType::TYPE_STRING);
        $newSheet->getCellByColumnAndRow(2, $rIdx)->setValueExplicit($row['correo'], DataType::TYPE_STRING);
        $newSheet->getCellByColumnAndRow(3, $rIdx)->setValueExplicit($row['tel'], DataType::TYPE_STRING);
        $newSheet->getCellByColumnAndRow(4, $rIdx)->setValueExplicit($row['pass'], DataType::TYPE_STRING);
        $newSheet->getCellByColumnAndRow(5, $rIdx)->setValueExplicit($row['rol'], DataType::TYPE_STRING);
        $newSheet->getCellByColumnAndRow(6, $rIdx)->setValueExplicit($row['occ'], DataType::TYPE_STRING);
        $rIdx++;
    }
    $writer = new Xlsx($newSs);
    $filename = $base.'padres_2026-03-11_CHUNK_'.($idx + 1).'.xlsx';
    $writer->save($filename);
    echo 'Saved Padres Chunk '.($idx + 1)." ($filename)\n";
}
echo "Chunking complete.\n";
