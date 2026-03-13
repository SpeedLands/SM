<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

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

// 3. Repair Padres
$readerP = IOFactory::createReaderForFile($padresPath);
$readerP->setReadDataOnly(true);
$ssP = $readerP->load($padresPath);
$sheetP = $ssP->getActiveSheet();
$rowsP = $sheetP->getHighestRow();

$parentRecords = [];
for ($i = 2; $i <= $rowsP; $i++) {
    $fullName = trim((string) $sheetP->getCellByColumnAndRow(1, $i)->getValue());
    $type = (stripos($fullName, 'Madre') !== false) ? 'mama' : 'papa';
    $studentNameRaw = preg_replace('/^(Madre de |Padre de )/i', '', $fullName);
    $slug = slugify($studentNameRaw);
    $record = ['pass' => (string) $sheetP->getCellByColumnAndRow(4, $i)->getValue()];
    $parentRecords[$slug][$type] = $record;
}

$checkSlug = slugify('TREVIÑO LOPEZ EMMANUEL');
echo "Check Slug: $checkSlug\n";
if (isset($parentRecords[$checkSlug]['papa'])) {
    echo 'Found Export Data for Papa: '.$parentRecords[$checkSlug]['papa']['pass']."\n";
} else {
    echo "NOT FOUND Export Data for Papa!\n";
    // Let's see what's in 'trevinolopezemmanuel' keys
    print_r(array_keys($parentRecords[$checkSlug] ?? []));
}
