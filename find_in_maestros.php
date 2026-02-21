<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\Maestros.xlsx';
if (!file_exists($file)) {
    echo "Maestros.xlsx no existe.\n";
    exit;
}

$ss = IOFactory::load($file);
$sheet = $ss->getSheet(0);

$targetEmails = ['8787014347@escuela.edu.mx', '8781179264@escuela.edu.mx'];

echo "Buscando en Maestros.xlsx...\n";
$highestRow = $sheet->getHighestRow();
$highestCol = $sheet->getHighestColumn();
$highestColIdx = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

for ($row = 1; $row <= $highestRow; $row++) {
    $found = false;
    $rowStr = "";
    for ($col = 1; $col <= $highestColIdx; $col++) {
        $val = (string)$sheet->getCellByColumnAndRow($col, $row)->getValue();
        $rowStr .= "C$col:[$val] ";
        foreach ($targetEmails as $target) {
            if (mb_stripos($val, $target) !== false) $found = true;
        }
    }
    if ($found) {
        echo "  Row $row: $rowStr\n";
    }
}
