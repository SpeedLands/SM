<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\2I.xlsx';
$ss = IOFactory::load($file);
$sheet = $ss->getSheet(0);

for ($row = 1; $row <= 20; $row++) {
    echo "Row $row: ";
    for ($col = 'A'; $col <= 'E'; $col++) {
        $val = $sheet->getCell($col . $row)->getValue();
        echo "[$val] ";
    }
    echo "\n";
}
