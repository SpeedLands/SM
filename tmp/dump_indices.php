<?php

use Illuminate\Contracts\Console\Kernel;
use Maatwebsite\Excel\Facades\Excel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function dumpFileIndices($filePath)
{
    echo "\n--- File: $filePath ---\n";
    $data = Excel::toCollection(new class {}, $filePath);
    $sheet = $data->first();
    foreach ($sheet->take(3) as $rowIndex => $row) {
        $rowArr = $row->toArray();
        echo "Row $rowIndex:\n";
        foreach ($rowArr as $colIndex => $val) {
            echo "  [$colIndex]: ".(is_null($val) ? 'NULL' : (string) $val)."\n";
        }
    }
}

dumpFileIndices('CURP DATA.xlsx');
dumpFileIndices('alumnos_2026-03-10.xlsx');
