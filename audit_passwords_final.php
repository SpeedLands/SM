<?php

ini_set('memory_limit', '1024M');
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$dir = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\';
$files = glob($dir . "*.xlsx");

$emails = []; 

function getSmartData($sheet, $row) {
    $data = ['email' => null, 'password' => null];
    $highestCol = $sheet->getHighestColumn();
    $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
    for ($i = 1; $i <= $highestColIndex; $i++) {
        $val = mb_strtolower(trim((string)$sheet->getCellByColumnAndRow($i, $row)->getValue()), 'UTF-8');
        if (str_contains($val, 'email') || str_contains($val, 'correo')) {
            $nextVal = trim((string)$sheet->getCellByColumnAndRow($i + 1, $row)->getValue());
            if (str_contains($nextVal, '@')) $data['email'] = mb_strtolower($nextVal, 'UTF-8');
        }
        if (str_contains($val, 'celular') || str_contains($val, 'contrase') || str_contains($val, 'pass')) {
            $nextVal = trim((string)$sheet->getCellByColumnAndRow($i + 1, $row)->getValue());
            if ($nextVal && strlen($nextVal) >= 4) $data['password'] = $nextVal;
        }
    }
    return $data;
}

echo "Auditoría UNIVERSAL de contraseñas...\n";

foreach ($files as $file) {
    if (str_starts_with(basename($file), '~')) continue;
    
    try {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $ss = $reader->load($file);
        
        foreach ($ss->getAllSheets() as $sheet) {
            $highestRow = $sheet->getHighestRow();
            for ($row = 1; $row <= $highestRow; $row++) {
                $d = getSmartData($sheet, $row);
                if ($d['email'] && $d['password']) {
                    $e = $d['email'];
                    $p = $d['password'];
                    $emails[$e][$p][] = basename($file) . " (" . $sheet->getTitle() . "):$row";
                }
            }
        }
        $ss->disconnectWorksheets();
        unset($ss);
    } catch (\Exception $e) {
        echo "Error en " . basename($file) . ": " . $e->getMessage() . "\n";
    }
    gc_collect_cycles();
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

if ($conflicts === 0) echo "\n¡AUDITORÍA EXITOSA! No hay conflictos de contraseñas.\n";
else echo "\nTotal conflictos: $conflicts\n";
