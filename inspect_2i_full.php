<?php

ini_set('memory_limit', '1024M');
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\2I.xlsx';
$ss = IOFactory::load($file);
$sheet = $ss->getSheet(0);

echo "INSPECCIÓN DE 2I.xlsx:\n";
$highestRow = $sheet->getHighestRow();

for ($row = 2; $row <= $highestRow; $row++) {
    $name = (string)$sheet->getCell('A' . $row)->getValue();
    $email = "";
    $pass = "";
    
    // Scan row for Email and Pass labels
    for ($col = 1; $col <= 15; $col++) {
        $val = trim((string)$sheet->getCellByColumnAndRow($col, $row)->getValue());
        if (mb_stripos($val, 'email') !== false) $email = trim((string)$sheet->getCellByColumnAndRow($col + 1, $row)->getValue());
        if (mb_stripos($val, 'celular') !== false || mb_stripos($val, 'contrase') !== false) $pass = trim((string)$sheet->getCellByColumnAndRow($col + 1, $row)->getValue());
    }
    
    if ($email || $name) {
        echo "Row $row: Alumno=[$name] | Email=[$email] | Pass=[$pass]\n";
    }
}
