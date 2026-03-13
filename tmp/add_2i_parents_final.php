<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$dir = 'c:/Users/juanp/Desktop/Apps/sm/DATOS_CORREGIDOS/';
$prodPhoneMap = json_decode(file_get_contents('c:/Users/juanp/Desktop/Apps/sm/tmp/name_phone_map.json'), true);

$studentsIn2I = [
    'ROJAS LANDER SANDOVAL',
    'RANGEL MEGAN ALEXIA RODARTE',
    'ZAMORANO IVANA GEORGINA MONSIVAIS',
    'ACOSTA RODRIGO GONZALEZ',
    'MEDINA JESUS ALEJANDRO CRUZ',
    'VENEGAS HERNANDEZ ANA FERNANDA',
    'VAZQUEZ HINOSTROSA BRIANNA JAZMIN',
];

$filePath = $dir.'2I.xlsx';
$spreadsheet = IOFactory::load($filePath);
$sheetP = $spreadsheet->getSheetByName('Padres 2I');

if (! $sheetP) {
    echo "Sheet Padres 2I not found!\n";
    exit;
}

$highestRow = $sheetP->getHighestRow();
$row = $highestRow + 1;

foreach ($studentsIn2I as $name) {
    $parentName = 'Padre de '.$name;
    $email = str_replace(' ', '', strtolower($name)).'@escuela.edu.mx';
    $pass = $prodPhoneMap[strtoupper($name)] ?? 'password';

    // Add Mama too for consistency as other files have it
    $sheetP->setCellValueByColumnAndRow(1, $row, "Padre de $name");
    $sheetP->setCellValueByColumnAndRow(2, $row, $email);
    $sheetP->setCellValueByColumnAndRow(3, $row, $pass);
    $row++;

    $sheetP->setCellValueByColumnAndRow(1, $row, "Madre de $name");
    $sheetP->setCellValueByColumnAndRow(2, $row, 'm.'.$email); // slight variation to avoid conflict if needed, or same email if that's the pattern
    $sheetP->setCellValueByColumnAndRow(3, $row, $pass);
    $row++;
}

$writer = new Xlsx($spreadsheet);
$writer->save($filePath);

echo 'Added '.(count($studentsIn2I) * 2)." specialized parent records to 2I.xlsx.\n";
