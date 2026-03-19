<?php

use Illuminate\Contracts\Console\Kernel;
use Maatwebsite\Excel\Facades\Excel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$filePath = 'CURP DATA.xlsx';
if (! file_exists($filePath)) {
    exit("File not found: $filePath");
}

$data = Excel::toCollection(new class {}, $filePath);

echo 'Total Sheets: '.count($data)."\n";
foreach ($data as $index => $sheet) {
    echo "Sheet $index: ".count($sheet)." rows\n";
    foreach ($sheet->take(5) as $rowIndex => $row) {
        $rowArr = $row->toArray();
        echo "  Row $rowIndex: ".json_encode($rowArr)."\n";
    }
}
