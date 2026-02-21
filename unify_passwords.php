<?php

ini_set('memory_limit', '1024M');
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;

Calculation::getInstance()->setCalculationCacheEnabled(false);

$outputDir = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\';
$files = glob($outputDir . "*.xlsx");

$parentPasswords = []; 

function detectColumns($sheet) {
    $cols = ['email' => null, 'password' => null];
    $highestCol = $sheet->getHighestColumn();
    $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

    for ($i = 1; $i <= $highestColIndex; $i++) {
        $val = mb_strtolower(trim((string)$sheet->getCellByColumnAndRow($i, 1)->getValue()), 'UTF-8');
        if (str_contains($val, 'correo') || str_contains($val, 'email')) {
            $cols['email'] = $i;
        }
        if (str_contains($val, 'contrase') || str_contains($val, 'pass') || str_contains($val, 'celular')) {
            $cols['password'] = $i;
        }
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
        $valToken = mb_strtolower(trim((string)$sheet->getCellByColumnAndRow($i, $row)->getValue()), 'UTF-8');
        
        if (str_contains($valToken, '@') && !str_contains($valToken, 'email') && !str_contains($valToken, 'correo')) {
            if (!$data['email']) $data['email'] = $valToken;
        }

        if (str_contains($valToken, 'email') || str_contains($valToken, 'correo')) {
            $next = trim((string)$sheet->getCellByColumnAndRow($i + 1, $row)->getValue());
            if (str_contains($next, '@')) $data['email'] = mb_strtolower($next, 'UTF-8');
        }
        if (str_contains($valToken, 'contrase') || str_contains($valToken, 'pass') || str_contains($valToken, 'celular')) {
            $next = trim((string)$sheet->getCellByColumnAndRow($i + 1, $row)->getValue());
            if (strlen($next) >= 4) $data['password'] = $next;
        }
    }
    return $data;
}

echo "Fase 1: Escaneo (Maestros > 3 > 2 > 1)...\n";
foreach ($files as $file) {
    if (str_contains($file, '~') || str_contains($file, 'audit')) continue;
    $filename = basename($file);
    $priority = 0;
    if ($filename === 'Maestros.xlsx') $priority = 10;
    elseif (preg_match('/^([1-3])/', $filename, $m)) $priority = (int)$m[1];

    $reader = IOFactory::createReader('Xlsx');
    $reader->setReadDataOnly(true);
    $ss = $reader->load($file);
    foreach ($ss->getAllSheets() as $sheet) {
        $fixedCols = detectColumns($sheet);
        $highestRow = $sheet->getHighestRow();
        for ($row = 1; $row <= $highestRow; $row++) {
            $d = getRowData($sheet, $row, $fixedCols);
            if ($d['email'] && $d['password']) {
                $e = $d['email'];
                $p = $d['password'];
                if (!isset($parentPasswords[$e]) || $priority >= $parentPasswords[$e]['priority']) {
                    $parentPasswords[$e] = ['password' => $p, 'priority' => $priority];
                }
            }
        }
    }
    $ss->disconnectWorksheets(); unset($ss);
    gc_collect_cycles();
}

echo "Unificando " . count($parentPasswords) . " correos.\n";
echo "Fase 2: Unificación...\n";

foreach ($files as $file) {
    if (str_contains($file, '~') || str_contains($file, 'audit')) continue;
    $filename = basename($file);
    
    try {
        $ss = IOFactory::load($file);
        $modified = false;

        foreach ($ss->getAllSheets() as $sheet) {
            $fixedCols = detectColumns($sheet);
            $highestRow = $sheet->getHighestRow();
            $highestCol = $sheet->getHighestColumn();
            $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

            for ($row = 1; $row <= $highestRow; $row++) {
                $d = getRowData($sheet, $row, $fixedCols);
                if ($d['email'] && isset($parentPasswords[$d['email']])) {
                    $targetPass = $parentPasswords[$d['email']]['password'];
                    $e = $d['email'];
                    
                    if ($fixedCols['password']) {
                        $curr = trim((string)$sheet->getCellByColumnAndRow($fixedCols['password'], $row)->getValue());
                        if ($curr !== $targetPass) {
                            $sheet->setCellValueByColumnAndRow($fixedCols['password'], $row, $targetPass);
                            $modified = true;
                        }
                    }
                    
                    for ($i = 1; $i <= $highestColIndex; $i++) {
                        $valToken = mb_strtolower(trim((string)$sheet->getCellByColumnAndRow($i, $row)->getValue()), 'UTF-8');
                        if (str_contains($valToken, 'contrase') || str_contains($valToken, 'pass') || str_contains($valToken, 'celular')) {
                            $curr = trim((string)$sheet->getCellByColumnAndRow($i + 1, $row)->getValue());
                            if ($curr !== $targetPass) {
                                $sheet->setCellValueByColumnAndRow($i + 1, $row, $targetPass);
                                $modified = true;
                            }
                        }
                    }
                }
            }
        }
        
        if ($modified) {
            echo "  $filename: Guardando cambios.\n";
            $writer = IOFactory::createWriter($ss, 'Xlsx');
            $writer->setPreCalculateFormulas(false); // <--- THIS FIXES THE CRASH
            $writer->save($file);
        }
        $ss->disconnectWorksheets(); unset($ss);
    } catch (\Exception $e) {
        echo "ERROR en $filename: " . $e->getMessage() . "\n";
    }
    gc_collect_cycles();
}
echo "Fin.\n";
