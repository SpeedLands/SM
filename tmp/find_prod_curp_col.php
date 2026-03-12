<?php

use Maatwebsite\Excel\Facades\Excel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function isValidCurp($curp)
{
    return preg_match('/^[A-Z]{4}[0-9]{6}[A-Z]{6}[0-9A-Z][0-9]$/', strtoupper(trim((string) $curp)));
}

$prodFile = 'alumnos_2026-03-10.xlsx';

echo "Scanning $prodFile for CURP column...\n";
$prodDataCollection = Excel::toCollection(new class {}, $prodFile);
$prodSheet = $prodDataCollection->first();

$colStats = [];

foreach ($prodSheet as $rowIndex => $row) {
    if ($rowIndex === 0) {
        continue;
    }
    $rowArr = $row->toArray();
    foreach ($rowArr as $colIndex => $val) {
        if (isValidCurp($val)) {
            $colStats[$colIndex] = ($colStats[$colIndex] ?? 0) + 1;
        }
    }
    if ($rowIndex > 100) {
        break;
    } // Check first 100 rows
}

echo "CURP matches found per column index (first 100 rows):\n";
foreach ($colStats as $colIndex => $count) {
    echo "  Column [$colIndex]: $count matches\n";
}

if (! empty($colStats)) {
    arsort($colStats);
    $bestCol = key($colStats);
    echo "\nBest candidate for CURP: Column [$bestCol]\n";
} else {
    echo "\nNo CURP matches found in the first 100 rows.\n";
}
