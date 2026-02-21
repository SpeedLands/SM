<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\2I.xlsx';
$ss = IOFactory::load($file);
$sheet = $ss->getSheet(0);

$rows = [7, 8, 68, 69];

echo "DEBUG 2I.xlsx:\n";
foreach ($rows as $row) {
    $name = (string)$sheet->getCell('A' . $row)->getValue();
    $email = (string)$sheet->getCell('B' . $row)->getValue();
    $pass = (string)$sheet->getCell('D' . $row)->getValue();
    
    echo "ROW $row:\n";
    echo "  NAME : [" . $name . "]\n";
    echo "  EMAIL: [" . $email . "]\n";
    echo "  PASS : [" . $pass . "]\n";
}
