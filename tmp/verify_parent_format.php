<?php

require __DIR__.'/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'DATOS_CORREGIDOS/1I.xlsx';
$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getSheetByName('Padres 1I');
$row = $sheet->getHighestRow();

echo "Verifying last parent record in $file (Row $row):\n";
echo '  Nombre: '.$sheet->getCellByColumnAndRow(1, $row)->getValue()."\n";
echo '  Correo: '.$sheet->getCellByColumnAndRow(2, $row)->getValue()."\n";
echo '  Rol: '.$sheet->getCellByColumnAndRow(5, $row)->getValue()."\n";
