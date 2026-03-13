<?php

use Maatwebsite\Excel\Facades\Excel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$files = ['2G.xlsx', '3G.xlsx'];
$dir = 'DATOS_CORREGIDOS';

foreach ($files as $file) {
    echo "--- File: $file ---\n";
    try {
        $sheets = Excel::toCollection(new class {}, "$dir/$file");
        $sheet = $sheets->first();

        $count = 0;
        foreach ($sheet as $row) {
            if ($count++ > 10) {
                break;
            } // Just first 10 rows
            print_r($row->toArray());
        }
    } catch (\Exception $e) {
        echo "Error reading $file: ".$e->getMessage()."\n";
    }
    echo "\n";
}
