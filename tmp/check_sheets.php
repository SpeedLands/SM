<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__.'/../vendor/autoload.php';

$files = ['DATOS_CORREGIDOS/2G.xlsx', 'DATOS_CORREGIDOS/3G.xlsx'];

foreach ($files as $file) {
    echo "--- File: $file ---\n";
    try {
        $spreadsheet = IOFactory::load($file);
        $sheetNames = $spreadsheet->getSheetNames();
        echo 'Sheets: '.implode(', ', $sheetNames)."\n";

        foreach ($sheetNames as $name) {
            $sheet = $spreadsheet->getSheetByName($name);
            $highestRow = $sheet->getHighestRow();
            echo "  Sheet '$name': $highestRow rows\n";

            // Check headers
            $headers = [];
            for ($i = 1; $i <= 6; $i++) {
                $headers[] = $sheet->getCellByColumnAndRow($i, 1)->getValue();
            }
            echo '    Headers: '.implode(' | ', $headers)."\n";
        }
    } catch (Exception $e) {
        echo 'Error: '.$e->getMessage()."\n";
    }
    echo "\n";
}
