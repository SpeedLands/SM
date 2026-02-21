<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\1A.xlsx';
$ss = IOFactory::load($file);
$sheet = $ss->getSheet(0);

echo "Header of 1A.xlsx (Row 1):\n";
for ($col = 'A'; $col <= 'G'; $col++) {
    echo "$col: [" . $sheet->getCell($col . '1')->getValue() . "]\n";
}
