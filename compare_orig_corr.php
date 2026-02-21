<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$fileOrig = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS\\2I.xlsx';
$fileCorr = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\2I.xlsx';

echo "COMPARACIÓN DE ORIGEN VS CORREGIDO (2I.xlsx - Padres 2I):\n";

function getRows($file, $emails) {
    if (!file_exists($file)) return [];
    $ss = IOFactory::load($file);
    $sheet = $ss->getSheetByName('Padres 2I');
    if (!$sheet) return [];
    
    $found = [];
    foreach ($sheet->getRowIterator() as $row) {
        $cellB = (string)$sheet->getCell('B' . $row->getRowIndex())->getValue();
        $cellC = (string)$sheet->getCell('C' . $row->getRowIndex())->getValue();
        foreach ($emails as $target) {
            if (mb_stripos($cellB, $target) !== false) {
                $found[] = "Row " . $row->getRowIndex() . ": Email=[$cellB] Pass=[$cellC]";
            }
        }
    }
    return $found;
}

$emails = ['8787014347@escuela.edu.mx', '8781179264@escuela.edu.mx'];

echo "\nORIGINAL (DATOS/):\n";
print_r(getRows($fileOrig, $emails));

echo "\nCORREGIDO (DATOS_CORREGIDOS/):\n";
print_r(getRows($fileCorr, $emails));
