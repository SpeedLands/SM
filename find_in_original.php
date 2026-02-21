<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\ORIGINAL.xlsx';
$ss = IOFactory::load($file);
$sheet = $ss->getSheet(0);

$studentName = 'ALVARADO ZARAGOZA CESAR ALEJANDRO';

echo "Buscando '$studentName' en ORIGINAL.xlsx...\n";
$highestRow = $sheet->getHighestRow();

for ($row = 1; $row <= $highestRow; $row++) {
    $val = (string)$sheet->getCell('A' . $row)->getValue();
    if (mb_stripos($val, $studentName) !== false) {
        echo "Row $row: ";
        for ($col = 1; $col <= 10; $col++) {
             echo "C$col:[ " . $sheet->getCellByColumnAndRow($col, $row)->getValue() . "] ";
        }
        echo "\n";
    }
}
