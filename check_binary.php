<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\2I.xlsx';
$ss = IOFactory::load($file);
$sheet = $ss->getSheetByName('Padres 2I');

echo "COMPROBACIÓN BINARIA (Row 6 vs 7):\n";
$r6e = $sheet->getCell('B6')->getValue();
$r6p = $sheet->getCell('C6')->getValue();
$r7e = $sheet->getCell('B7')->getValue();
$r7p = $sheet->getCell('C7')->getValue();

echo "Row 6: Email=[$r6e] hex=[".bin2hex($r6e)."] | Pass=[$r6p] hex=[".bin2hex($r6p)."]\n";
echo "Row 7: Email=[$r7e] hex=[".bin2hex($r7e)."] | Pass=[$r7p] hex=[".bin2hex($r7p)."]\n";

echo "\nCOMPROBACIÓN BINARIA (Row 67 vs 68):\n";
$r67e = $sheet->getCell('B67')->getValue();
$r67p = $sheet->getCell('C67')->getValue();
$r68e = $sheet->getCell('B68')->getValue();
$r68p = $sheet->getCell('C68')->getValue();

echo "Row 67: Email=[$r67e] hex=[".bin2hex($r67e)."] | Pass=[$r67p] hex=[".bin2hex($r67p)."]\n";
echo "Row 68: Email=[$r68e] hex=[".bin2hex($r68e)."] | Pass=[$r68p] hex=[".bin2hex($r68p)."]\n";
