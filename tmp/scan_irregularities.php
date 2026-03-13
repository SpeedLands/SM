<?php

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$base = 'c:/Users/juanp/Desktop/Apps/sm/';
$alumnosPath = $base.'alumnos_2026-03-11.xlsx';
$padresPath = $base.'padres_2026-03-11.xlsx';
$originalPath = $base.'ORIGINAL.xlsx';

function cleanPhone($phone)
{
    if (! $phone) {
        return '';
    }

    return preg_replace('/[^0-9]/', '', (string) $phone);
}

function isIrregular($phone)
{
    $clean = cleanPhone($phone);
    if (empty($clean)) {
        return true;
    }
    if (strlen($clean) < 10) {
        return true;
    }
    if (preg_match('/^(0|1|2|3|4|5|6|7|8|9)\1+$/', $clean)) {
        return true;
    } // Repeating digits like 000...
    if (in_array($clean, ['1234567', '12345678', '123456789', '1234567890'])) {
        return true;
    }

    return false;
}

$report = [
    'padres_irregular' => [],
    'alumnos_irregular' => [],
];

// Scan Padres
echo "Scanning Padres...\n";
$ssP = IOFactory::load($padresPath);
$sheetP = $ssP->getActiveSheet();
$rowsP = $sheetP->getHighestRow();
for ($i = 2; $i <= $rowsP; $i++) {
    $nombre = $sheetP->getCell('A'.$i)->getValue();
    $correo = $sheetP->getCell('B'.$i)->getValue();
    $tel = $sheetP->getCell('C'.$i)->getValue();
    $pass = $sheetP->getCell('D'.$i)->getValue();

    $irregularTel = isIrregular($tel);
    $irregularPass = (strtolower((string) $pass) === 'password' || $pass === '1234567');

    if ($irregularTel || $irregularPass) {
        $report['padres_irregular'][] = [
            'row' => $i,
            'nombre' => $nombre,
            'correo' => $correo,
            'tel' => $tel,
            'pass' => $pass,
            'reason' => ($irregularTel ? 'Irregular Tel' : '').($irregularPass ? ' Irregular Pass' : ''),
        ];
    }
}

// Scan Alumnos
echo "Scanning Alumnos...\n";
$ssA = IOFactory::load($alumnosPath);
$sheetA = $ssA->getActiveSheet();
$rowsA = $sheetA->getHighestRow();
for ($i = 2; $i <= $rowsA; $i++) {
    $nombre = $sheetA->getCell('A'.$i)->getValue();
    $tel = $sheetA->getCell('E'.$i)->getValue();

    if (isIrregular($tel)) {
        $report['alumnos_irregular'][] = [
            'row' => $i,
            'nombre' => $nombre,
            'tel' => $tel,
        ];
    }
}

file_put_contents($base.'tmp/irregularity_report.json', json_encode($report, JSON_PRETTY_PRINT));
echo "Report generated in tmp/irregularity_report.json\n";
echo 'Found '.count($report['padres_irregular'])." irregular parents.\n";
echo 'Found '.count($report['alumnos_irregular'])." irregular students.\n";
