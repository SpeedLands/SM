<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

function getSmartData($sheet, $row) {
    $data = ['email' => null, 'password' => null];
    for ($i = 1; $i <= 15; $i++) {
        $val = trim((string)$sheet->getCellByColumnAndRow($i, $row)->getValue());
        $lval = mb_strtolower($val, 'UTF-8');
        
        if (str_contains($lval, 'email')) {
            $nextVal = trim((string)$sheet->getCellByColumnAndRow($i + 1, $row)->getValue());
            echo "      Found label 'email' at Col $i ($val). Next value: [$nextVal]\n";
            if (str_contains($nextVal, '@')) {
                $data['email'] = mb_strtolower($nextVal, 'UTF-8');
            }
        }
        
        if (str_contains($lval, 'celular') || str_contains($lval, 'contrase') || str_contains($lval, 'pass')) {
            $nextVal = trim((string)$sheet->getCellByColumnAndRow($i + 1, $row)->getValue());
            echo "      Found label 'pass/cel' at Col $i ($val). Next value: [$nextVal]\n";
            if (strlen($nextVal) > 5) { // Passwords are usually long or same as phone
                $data['password'] = $nextVal;
            }
        }
    }
    return $data;
}

$file = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\2I.xlsx';
$ss = IOFactory::load($file);
$sheet = $ss->getSheet(0);

foreach ([7, 8] as $row) {
    echo "Processing Row $row:\n";
    $d = getSmartData($sheet, $row);
    var_dump($d);
}
