<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\2I.xlsx';
$ss = IOFactory::load($file);
$sheet = $ss->getSheetByName('Padres 2I');

echo "FULL DUMP OF INTERESTING ROWS (65-75):\n";
for ($row = 65; $row <= 75; $row++) {
    echo "[$row] ";
    for ($col = 1; $col <= 11; $col++) {
        $val = (string)$sheet->getCellByColumnAndRow($col, $row)->getValue();
        echo "$col:[$val] ";
    }
    echo "\n";
}
