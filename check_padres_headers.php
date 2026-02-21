<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\2I.xlsx';
$ss = IOFactory::load($file);
$sheet = $ss->getSheetByName('Padres 2I');

echo "Headings in Padres 2I (Row 1):\n";
for ($col = 1; $col <= 15; $col++) {
    $val = (string)$sheet->getCellByColumnAndRow($col, 1)->getValue();
    echo "C$col: [$val] ";
}
echo "\n";
