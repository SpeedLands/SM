<?php

use Illuminate\Contracts\Console\Kernel;
use Maatwebsite\Excel\Facades\Excel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$files = ['2G.xlsx', '3G.xlsx'];
$dir = 'DATOS_CORREGIDOS';

foreach ($files as $file) {
    try {
        $sheets = Excel::toCollection(new class {}, "$dir/$file");
        $sheet = $sheets->first();
        $count = $sheet->count() - 1; // Subtract header
        echo "$file has $count rows (excluding header).\n";

        // Print first 5 names found there just to be sure what IS there
        echo "First names in $file:\n";
        $i = 0;
        foreach ($sheet as $row) {
            $rowArr = $row->toArray();
            if (($rowArr[0] ?? '') === 'Nombre') {
                continue;
            }
            if ($i++ >= 5) {
                break;
            }
            echo '- '.($rowArr[0] ?? '[EMPTY]')."\n";
        }
    } catch (Exception $e) {
        echo "Error reading $file: ".$e->getMessage()."\n";
    }
    echo "\n";
}
