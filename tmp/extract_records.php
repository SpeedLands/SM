<?php

use Maatwebsite\Excel\Facades\Excel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$toFind = [
    'CRUZ GARCIA CRISTIAN ALEJANDRO',
    'MONCADA RAMIREZ ALFREDO',
    'PIZARRO LUCIO JESUS MANUEL',
    'ESCOBEDO VILLAZANA ANGELA YANET',
    'VASQUEZ MENDEZ NAOMI ELIZABETH',
];

$file = 'ORIGINAL.xlsx';
echo "Extracting from $file...\n";

try {
    $sheets = Excel::toCollection(new class {}, $file);
    $sheet = $sheets->first();

    $found = [];
    foreach ($sheet as $row) {
        $rowArr = $row->toArray();
        $name = strtoupper(trim((string) ($rowArr[0] ?? '')));

        foreach ($toFind as $target) {
            if ($name === $target) {
                $found[$target] = $rowArr;
                echo "Found: $target\n";
            }
        }
    }

    file_put_contents('tmp/extracted_students.json', json_encode($found));
    echo "Saved to tmp/extracted_students.json\n";
} catch (\Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
}
