<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS\\2I.xlsx';
$ss = IOFactory::load($file);
$sheet = $ss->getSheetByName('Padres 2I');

echo "CONTENIDO FILAS 6,7, 67,68 en DATOS/2I.xlsx (Original):\n";
foreach ([6, 7, 67, 68] as $row) {
    echo "[$row] ";
    for ($col = 1; $col <= 10; $col++) {
        $val = (string)$sheet->getCellByColumnAndRow($col, $row)->getValue();
        echo "C$col:[$val] ";
    }
    echo "\n";
}
