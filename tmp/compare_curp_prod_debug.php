<?php

use Maatwebsite\Excel\Facades\Excel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function normalize($str)
{
    if (! $str) {
        return '';
    }
    $str = strtoupper(trim((string) $str));
    // Remove accents
    $str = strtr(utf8_decode($str), utf8_decode('ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ'), 'AAAAAAACEEEEIIIIDNOOOOOOUUUUYBsaaaaaaaceeeeiiiidnoooooouuuuyby');
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
$prodDataCollection = Excel::toCollection(new class {}, $prodFile);
$prodSheet = $prodDataCollection->first();
$studentsInProd = [];

foreach ($prodSheet as $rowIndex => $row) {
    $rowArr = $row->toArray();
    if ($rowIndex === 0) {
        continue;
    }

    // Debug first 5 rows
    if ($rowIndex <= 5) {
        echo "Row $rowIndex Col 3: '".($rowArr[3] ?? 'EMPTY')."' (Len: ".strlen((string) ($rowArr[3] ?? '')).")\n";
    }

    $curp = strtoupper(trim((string) ($rowArr[3] ?? '')));
    if (empty($curp)) {
        continue;
    }

    // More flexible validation for debug
    if (! isValidCurp($curp)) {
        if ($rowIndex <= 20) {
            echo "  Invalid CURP pattern: $curp\n";
        }
        // Let's be less strict if needed, but for now just count valid ones
        // continue;
    }

    $studentsInProd[$curp] = [
        'curp' => $curp,
        'name' => normalize($rowArr[0] ?? ''),
    ];
}

echo 'Total unique students in PROD: '.count($studentsInProd)."\n\n";

// Discrepancies
$missingInProd = [];
foreach ($studentsInCurpData as $curp => $student) {
    if (! isset($studentsInProd[$curp])) {
        $missingInProd[] = $student;
    }
}

echo "--- DISCREPANCIES (In CURP DATA but not in PROD) ---\n";
echo 'Total Missing in PROD: '.count($missingInProd)."\n";
foreach ($missingInProd as $s) {
    echo "  - {$s['curp']} | {$s['name']} [{$s['grade']}{$s['group']}]\n";
}
