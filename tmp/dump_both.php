<?php

use Illuminate\Contracts\Console\Kernel;
use Maatwebsite\Excel\Facades\Excel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function dumpFile($filePath)
{
    echo "\n--- File: $filePath ---\n";
    $data = Excel::toCollection(new class {}, $filePath);
    foreach ($data as $sheet) {
        foreach ($sheet->take(5) as $rowIndex => $row) {
            $rowArr = $row->toArray();
            echo "Row $rowIndex: ".json_encode($rowArr)."\n";
        }
    }
}

dumpFile('CURP DATA.xlsx');
dumpFile('alumnos_2026-03-10.xlsx');
