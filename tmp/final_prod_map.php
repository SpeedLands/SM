<?php

use Illuminate\Contracts\Console\Kernel;
use Maatwebsite\Excel\Facades\Excel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$prodFile = 'alumnos_2026-03-10.xlsx';
$data = Excel::toCollection(new class {}, $prodFile);
$sheet = $data->first();

foreach ($sheet->take(5) as $rowIndex => $row) {
    $rowArr = $row->toArray();
    echo "Row $rowIndex:\n";
    foreach ($rowArr as $colIndex => $val) {
        echo "  [$colIndex]: ".json_encode($val)."\n";
    }
    echo "---\n";
}
