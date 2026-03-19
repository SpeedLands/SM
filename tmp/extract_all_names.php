<?php

use Illuminate\Contracts\Console\Kernel;
use Maatwebsite\Excel\Facades\Excel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$dir = 'DATOS_CORREGIDOS';
$files = array_diff(scandir($dir), ['.', '..']);

$outputFile = 'tmp/all_corrected_names.txt';
$handle = fopen($outputFile, 'w');

foreach ($files as $file) {
    if (! str_ends_with($file, '.xlsx')) {
        continue;
    }

    try {
        $sheets = Excel::toCollection(new class {}, "$dir/$file");
        foreach ($sheets as $sheet) {
            foreach ($sheet as $row) {
                $rowArr = $row->toArray();
                $name = trim((string) ($rowArr[0] ?? ''));
                $curp = trim((string) ($rowArr[6] ?? ''));
                if ($name && $name !== 'Nombre') {
                    fwrite($handle, "$name | $curp | $file\n");
                }
            }
        }
    } catch (Exception $e) {
        // Skip
    }
}
fclose($handle);
echo "Extracted all names to $outputFile\n";
