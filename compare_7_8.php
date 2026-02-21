<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\2I.xlsx';
$ss = IOFactory::load($file);
$sheet = $ss->getSheet(0);

$r7e = (string)$sheet->getCell('C7')->getValue();
$r7p = (string)$sheet->getCell('E7')->getValue();

$r8e = (string)$sheet->getCell('C8')->getValue();
$r8p = (string)$sheet->getCell('E8')->getValue();

echo "COMPARE 2I.xlsx Rows 7 & 8:\n";
echo "Row 7: Email=[$r7e], Pass=[$r7p]\n";
echo "Row 8: Email=[$r8e], Pass=[$r8p]\n";

if (trim(mb_strtolower($r7e, 'UTF-8')) === trim(mb_strtolower($r8e, 'UTF-8'))) {
    echo "Lógica: Los correos SON IGUALES.\n";
} else {
    echo "Lógica: Los correos SON DIFERENTES.\n";
}

if ($r7p === $r8p) {
    echo "Lógica: Los passwords SON IGUALES.\n";
} else {
    echo "Lógica: Los passwords SON DIFERENTES.\n";
}
