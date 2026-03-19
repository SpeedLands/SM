<?php

use Illuminate\Contracts\Console\Kernel;
use Maatwebsite\Excel\Facades\Excel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function normalize($str)
{
    if (! $str) {
        return '';
    }
    $str = strtoupper(trim((string) $str));
    // Remove accents
    $str = strtr(utf8_decode($str), utf8_decode('ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ'), 'AAAAAAACEEEEIIIIDNOOOOOOUUUUYBsaaaaaaaceeeeiiiidnoooooouuuuyby');
    $str = preg_replace('/\s+/', ' ', $str);

    return trim($str);
}

function isValidCurp($curp)
{
    return preg_match('/^[A-Z]{4}[0-9]{6}[A-Z]{6}[0-9A-Z][0-9]$/', $curp);
}

$curpDataFile = 'CURP DATA.xlsx';
$prodFile = 'alumnos_2026-03-10.xlsx';

echo "Reading $curpDataFile...\n";
$curpData = Excel::toCollection(new class {}, $curpDataFile)->first();
$studentsInCurpData = [];

foreach ($curpData as $rowIndex => $row) {
    $rowArr = $row->toArray();
    if ($rowIndex === 0 || empty($rowArr[0])) {
        continue;
    }

    $curp = strtoupper(trim((string) ($rowArr[0] ?? '')));
    if (! isValidCurp($curp)) {
        continue;
    }

    // Col 1: Nombre, Col 2: Paterno, Col 3: Materno
    $nombre = trim((string) ($rowArr[1] ?? ''));
    $paterno = trim((string) ($rowArr[2] ?? ''));
    $materno = trim((string) ($rowArr[3] ?? ''));
    $fullName = normalize("$paterno $materno $nombre");

    $studentsInCurpData[$curp] = [
        'curp' => $curp,
        'name' => $fullName,
        'grade' => $rowArr[4] ?? '',
        'group' => $rowArr[5] ?? '',
    ];
}

echo 'Total students in CURP DATA: '.count($studentsInCurpData)."\n";

echo "Reading $prodFile...\n";
$prodData = Excel::toCollection(new class {}, $prodFile)->first();
$studentsInProd = [];

foreach ($prodData as $rowIndex => $row) {
    $rowArr = $row->toArray();
    if ($rowIndex === 0 || empty($rowArr[3])) {
        continue;
    }

    $curp = strtoupper(trim((string) ($rowArr[3] ?? '')));
    if (! isValidCurp($curp)) {
        continue;
    }

    $studentsInProd[$curp] = [
        'curp' => $curp,
        'name' => normalize($rowArr[0] ?? ''),
    ];
}

echo 'Total students in PROD: '.count($studentsInProd)."\n\n";

// Discrepancies
$missingInProd = [];
foreach ($studentsInCurpData as $curp => $student) {
    if (! isset($studentsInProd[$curp])) {
        $missingInProd[] = $student;
    }
}

echo "--- DISCREPANCIES (In CURP DATA but not in PROD) ---\n";
echo 'Total Missing: '.count($missingInProd)."\n";
foreach ($missingInProd as $s) {
    echo "  - {$s['curp']} | {$s['name']} [{$s['grade']}{$s['group']}]\n";
}

// Check for the 5 students specifically in CURP DATA
echo "\n--- SPECIFIC CHECK (The 5 students to save) ---\n";
$specificNames = [
    'CRUZ GARCIA CRISTIAN ALEJANDRO',
    'MONCADA RAMIREZ ALFREDO',
    'PIZARRO LUCIO JESUS MANUEL',
    'ESCOBEDO VILLAZANA ANGELA YANET',
    'VASQUEZ MENDEZ NAOMI ELIZABETH',
];

foreach ($specificNames as $name) {
    $found = false;
    $normSearch = normalize($name);
    foreach ($studentsInCurpData as $s) {
        if ($s['name'] === $normSearch) {
            echo "MATCH FOUND: {$s['curp']} | {$s['name']} [{$s['grade']}{$s['group']}]\n";
            $found = true;
            break;
        }
    }
    if (! $found) {
        echo "NOT FOUND in CURP DATA: $name\n";
    }
}
