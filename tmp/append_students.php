<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$finalData = json_decode(file_get_contents('tmp/final_student_records.json'), true);

$groups = [
    '2G' => ['ESCOBEDO VILLAZANA ANGELA YANET', 'VASQUEZ MENDEZ NAOMI ELIZABETH'],
    '3G' => ['CRUZ GARCIA CRISTIAN ALEJANDRO', 'MONCADA RAMIREZ ALFREDO', 'PIZARRO LUCIO JESUS MANUEL'],
];

$dir = 'DATOS_CORREGIDOS';

foreach ($groups as $group => $studentNames) {
    $file = "$dir/$group.xlsx";
    echo "Updating $file...\n";

    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();

        $highestRow = $sheet->getHighestRow();
        $nextRow = $highestRow + 1;

        foreach ($studentNames as $name) {
            if (isset($finalData[$name])) {
                $rowData = $finalData[$name];
                foreach ($rowData as $colIndex => $value) {
                    $sheet->setCellValueByColumnAndRow($colIndex + 1, $nextRow, $value);
                }
                echo "Added $name to row $nextRow\n";
                $nextRow++;
            }
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($file);
        echo "Saved $file\n";
    } catch (\Exception $e) {
        echo 'Error: '.$e->getMessage()."\n";
    }
    echo "\n";
}
