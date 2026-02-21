<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\2I.xlsx';
$ss = IOFactory::load($file);
$sheet = $ss->getSheet(0);

echo "HEX DUMP of 2I.xlsx Row 7, Col 4:\n";
$val = $sheet->getCellByColumnAndRow(4, 7)->getValue();
echo "Value: [$val]\n";
echo "Hex: " . bin2hex($val) . "\n";
echo "Lower: [" . mb_strtolower($val, 'UTF-8') . "]\n";

echo "\nFase de prueba rápida:\n";
if (str_contains(mb_strtolower($val, 'UTF-8'), "email")) {
    echo "MATCH FOUND\n";
} else {
    echo "NO MATCH\n";
}
