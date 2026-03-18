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
    $str = strtr(utf8_decode($str), utf8_decode('ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ'), 'AAAAAAACEEEEIIIIDNOOOOOOUUUUYBsaaaaaaaceeeeiiiidnoooooouuuuyby');
    $str = preg_replace('/\s+/', ' ', $str);

    return trim($str);
}

function isValidCurp($curp)
{
    return preg_match('/^[A-Z]{4}[0-9]{6}[A-Z]{6}[0-9A-Z][0-9]$/', strtoupper(trim((string) $curp)));
}

$curpDataFile = 'CURP DATA.xlsx';
$prodFile = 'alumnos_2026-03-10.xlsx';

echo "Processing $curpDataFile...\n";
$curpCollection = Excel::toCollection(new class {}, $curpDataFile)->first();
$curpMap = [];
foreach ($curpCollection as $index => $row) {
    if ($index === 0) {
        continue;
    }
    $rowArr = $row->toArray();
    $curp = strtoupper(trim((string) ($rowArr[0] ?? '')));
    if (isValidCurp($curp)) {
        $nombre = trim((string) ($rowArr[1] ?? ''));
        $paterno = trim((string) ($rowArr[2] ?? ''));
        $materno = trim((string) ($rowArr[3] ?? ''));
        $fullName = normalize("$paterno $materno $nombre");
        $curpMap[$curp] = [
            'name' => $fullName,
            'grade' => $rowArr[4] ?? '',
            'group' => $rowArr[5] ?? '',
            'raw' => $rowArr,
        ];
    }
}

echo "Processing $prodFile...\n";
$prodCollection = Excel::toCollection(new class {}, $prodFile)->first();
$prodMap = [];
foreach ($prodCollection as $index => $row) {
    if ($index === 0) {
        continue;
    }
    $rowArr = $row->toArray();
    $curp = strtoupper(trim((string) ($rowArr[6] ?? ''))); // Based on final_prod_map_out.txt
    if (isValidCurp($curp)) {
        $prodMap[$curp] = [
            'name' => normalize($rowArr[0] ?? ''),
            'raw' => $rowArr,
        ];
    }
}

echo "\n--- SUMMARY ---\n";
echo 'Students in CURP DATA: '.count($curpMap)."\n";
echo 'Students in PROD: '.count($prodMap)."\n";

$missingInProd = [];
foreach ($curpMap as $curp => $data) {
    if (! isset($prodMap[$curp])) {
        $missingInProd[] = $data;
    }
}

echo "\n--- MISSING IN PRODUCTION (".count($missingInProd).") ---\n";
foreach ($missingInProd as $s) {
    echo "{$s['raw'][0]} | {$s['name']} [{$s['grade']}{$s['group']}]\n";
}

// Extract info for the 5 students to save
$toSaveNames = [
    'CRUZ GARCIA CRISTIAN ALEJANDRO',
    'MONCADA RAMIREZ ALFREDO',
    'PIZARRO LUCIO JESUS MANUEL',
    'ESCOBEDO VILLAZANA ANGELA YANET',
    'VASQUEZ MENDEZ NAOMI ELIZABETH',
];

echo "\n--- DETAILS OF STUDENTS TO SAVE ---\n";
foreach ($toSaveNames as $name) {
    $norm = normalize($name);
    $found = false;
    foreach ($curpMap as $curp => $data) {
        if ($data['name'] === $norm) {
            echo "MATCH: $curp | $name | Grade: {$data['grade']} | Group: {$data['group']}\n";
            $found = true;
            break;
        }
    }
    if (! $found) {
        echo "NOT FOUND: $name\n";
    }
}
