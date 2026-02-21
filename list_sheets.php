<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\2I.xlsx';
$reader = IOFactory::createReader('Xlsx');
$names = $reader->listWorksheetNames($file);

echo "Sheets in 2I.xlsx:\n";
print_r($names);
