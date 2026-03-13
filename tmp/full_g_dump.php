<?php

use Maatwebsite\Excel\Facades\Excel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$files = ['2G.xlsx', '3G.xlsx'];
$dir = 'DATOS_CORREGIDOS';

foreach ($files as $file) {
    echo "--- $file ---\n";
    try {
        $sheets = Excel::toCollection(new class {}, "$dir/$file");
        $sheet = $sheets->first();
        foreach ($sheet as $row) {
            $rowArr = $row->toArray();
            if (($rowArr[0] ?? '') === 'Nombre') {
                continue;
            }
            echo ($rowArr[0] ?? '').' | '.($rowArr[6] ?? '')."\n";
        }
    } catch (\Exception $e) {
        echo 'Error: '.$e->getMessage()."\n";
    }
    echo "\n";
}
