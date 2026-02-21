<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS\\2I.xlsx';
$ss = IOFactory::load($file);

$targets = ['8787014347@escuela.edu.mx', '8781179264@escuela.edu.mx'];

foreach ($ss->getAllSheets() as $sheet) {
    echo "\nSheet: " . $sheet->getTitle() . "\n";
    $highestRow = $sheet->getHighestRow();
    $highestCol = $sheet->getHighestColumn();
    $highestColIdx = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
    
    for ($row = 1; $row <= $highestRow; $row++) {
        for ($col = 1; $col <= $highestColIdx; $col++) {
             $val = (string)$sheet->getCellByColumnAndRow($col, $row)->getValue();
             foreach ($targets as $target) {
                 if (mb_stripos($val, $target) !== false) {
                     echo "Match FOUND at Row $row, Col $col: [$val]\n";
                     // Print the whole row
                     echo "  ROW DUMP: ";
                     for ($c = 1; $c <= $highestColIdx; $c++) {
                         echo "[$c:".$sheet->getCellByColumnAndRow($c, $row)->getValue()."] ";
                     }
                     echo "\n";
                 }
             }
        }
    }
}
