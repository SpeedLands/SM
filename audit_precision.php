<?php

ini_set('memory_limit', '1024M');
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$dir = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\';
$files = glob($dir . "*.xlsx");

$emails = []; 

function detectColumns($sheet) {
    $cols = ['email' => null, 'password' => null];
    $highestCol = $sheet->getHighestColumn();
    $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
    for ($i = 1; $i <= $highestColIndex; $i++) {
        $val = mb_strtolower(trim((string)$sheet->getCellByColumnAndRow($i, 1)->getValue()), 'UTF-8');
        if (str_contains($val, 'correo') || str_contains($val, 'email')) $cols['email'] = $i;
        if (str_contains($val, 'contrase') || str_contains($val, 'pass') || str_contains($val, 'celular')) $cols['password'] = $i;
    }
    return $cols;
}

function getRowData($sheet, $row, $fixedCols) {
    $data = ['email' => null, 'password' => null];
    $highestCol = $sheet->getHighestColumn();
    $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
    if ($fixedCols['email']) {
        $val = trim((string)$sheet->getCellByColumnAndRow($fixedCols['email'], $row)->getValue());
        if (str_contains($val, '@')) $data['email'] = mb_strtolower($val, 'UTF-8');
    }
    if ($fixedCols['password']) {
        $val = trim((string)$sheet->getCellByColumnAndRow($fixedCols['password'], $row)->getValue());
        if (strlen($val) >= 4) $data['password'] = $val;
    }
    for ($i = 1; $i <= $highestColIndex; $i++) {
        $val = mb_strtolower(trim((string)$sheet->getCellByColumnAndRow($i, $row)->getValue()), 'UTF-8');
        if (str_contains($val, '@') && !str_contains($val, 'email')) {
             if (!$data['email']) $data['email'] = $val;
        }
        if (str_contains($val, 'email') || str_contains($val, 'correo')) {
            $next = trim((string)$sheet->getCellByColumnAndRow($i + 1, $row)->getValue());
            if (str_contains($next, '@')) $data['email'] = mb_strtolower($next, 'UTF-8');
        }
        if (str_contains($val, 'contrase') || str_contains($val, 'pass') || str_contains($val, 'celular')) {
            $next = trim((string)$sheet->getCellByColumnAndRow($i + 1, $row)->getValue());
            if (strlen($next) >= 4) $data['password'] = $next;
        }
    }
    return $data;
}

echo "Auditoría de PRECISIÓN FINAL...\n";

foreach ($files as $file) {
    if (str_contains($file, '~') || str_contains($file, 'audit')) continue;
    $filename = basename($file);
    try {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $ss = $reader->load($file);
        foreach ($ss->getAllSheets() as $sheet) {
            $fixedCols = detectColumns($sheet);
            $highestRow = $sheet->getHighestRow();
            for ($row = 1; $row <= $highestRow; $row++) {
                $d = getRowData($sheet, $row, $fixedCols);
                if ($d['email'] && $d['password']) {
                    $emails[$d['email']][$d['password']][] = "$filename (".$sheet->getTitle()."):$row";
                }
            }
        }
        $ss->disconnectWorksheets(); unset($ss);
    } catch (\Exception $ex) {}
}

$conflicts = 0;
foreach ($emails as $email => $passwords) {
    if (count($passwords) > 1) {
        $conflicts++;
        echo "\nCONFLICTO: [$email]\n";
        foreach ($passwords as $pass => $locs) {
            echo "  [$pass] -> " . implode(", ", $locs) . "\n";
        }
    }
}

if ($conflicts === 0) echo "\n¡AUDITORÍA EXITOSA! Cero conflictos.\n";
else echo "\nTotal: $conflicts conflictos.\n";
