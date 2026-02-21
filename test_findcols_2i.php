<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

function findCols($sheet) {
    $cols = ['email' => null, 'password' => null];
    $highestCol = $sheet->getHighestColumn();
    $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

    for ($i = 1; $i <= $highestColIndex; $i++) {
        $cellValue = mb_strtolower((string)$sheet->getCellByColumnAndRow($i, 1)->getValue(), 'UTF-8');
        echo "Col $i: [$cellValue] ";
        
        if (str_contains($cellValue, 'correo') || str_contains($cellValue, 'email')) {
            echo "-> MATCH EMAIL ";
            $cols['email'] = $i;
        }
        if (str_contains($cellValue, 'contrase') || str_contains($cellValue, 'password') || str_contains($cellValue, 'pass')) {
            echo "-> MATCH PASS ";
            $cols['password'] = $i;
        }
        echo "\n";
    }
    return $cols;
}

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\2I.xlsx';
$ss = IOFactory::load($file);
$sheet = $ss->getSheet(0);

echo "Testing findCols for 2I.xlsx:\n";
$cols = findCols($sheet);
var_dump($cols);
