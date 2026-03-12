<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$dir = 'c:/Users/juanp/Desktop/Apps/sm/DATOS_CORREGIDOS/';
$prodPhoneMap = json_decode(file_get_contents('c:/Users/juanp/Desktop/Apps/sm/tmp/name_phone_map.json'), true);

$files = array_diff(scandir($dir), ['.', '..']);

// Students specifically from 2I that should NOT be in 2G
$studentsIn2I = [
    'ROJAS LANDER SANDOVAL',
    'RANGEL MEGAN ALEXIA RODARTE',
    'ZAMORANO IVANA GEORGINA MONSIVAIS',
    'ACOSTA RODRIGO GONZALEZ',
    'MEDINA JESUS ALEJANDRO CRUZ',
    'VENEGAS HERNANDEZ ANA FERNANDA',
    'VAZQUEZ HINOSTROSA BRIANNA JAZMIN',
];

foreach ($files as $file) {
    if (! str_ends_with($file, '.xlsx')) {
        continue;
    }
    $filePath = $dir.$file;
    echo "Processing $file...\n";

    $spreadsheet = IOFactory::load($filePath);

    // 1. Process Alumnos sheet (cleanup only)
    $sheetAName = '';
    foreach ($spreadsheet->getSheetNames() as $sn) {
        if (stripos($sn, 'Alumno') !== false) {
            $sheetAName = $sn;
            break;
        }
    }

    if ($sheetAName && $file === '2G.xlsx') {
        $sheetA = $spreadsheet->getSheetByName($sheetAName);
        $highestRow = $sheetA->getHighestRow();
        for ($i = $highestRow; $i >= 2; $i--) {
            $name = strtoupper(trim((string) $sheetA->getCellByColumnAndRow(1, $i)->getValue()));
            foreach ($studentsIn2I as $s2i) {
                if (strpos($name, $s2i) !== false) {
                    echo "  Removing student $name from 2G Alumnos at row $i\n";
                    $sheetA->removeRow($i);
                    break;
                }
            }
        }
    }

    // 2. Process Padres sheet
    $sheetPName = '';
    foreach ($spreadsheet->getSheetNames() as $sn) {
        if (stripos($sn, 'Padre') !== false) {
            $sheetPName = $sn;
            break;
        }
    }

    if ($sheetPName) {
        $sheetP = $spreadsheet->getSheetByName($sheetPName);
        $highestRow = $sheetP->getHighestRow();
        $parentData = [];

        for ($i = 2; $i <= $highestRow; $i++) {
            $name = trim((string) $sheetP->getCellByColumnAndRow(1, $i)->getValue());
            $email = trim(strtolower((string) $sheetP->getCellByColumnAndRow(2, $i)->getValue()));
            $pass = trim((string) $sheetP->getCellByColumnAndRow(3, $i)->getValue());

            if (! $email && ! $name) {
                continue;
            } // Empty row

            // Special cleanup for 2G: remove 2I parents
            if ($file === '2G.xlsx') {
                $is2I = false;
                foreach ($studentsIn2I as $s2i) {
                    if (stripos($name, $s2i) !== false) {
                        $is2I = true;
                        break;
                    }
                }
                if ($is2I) {
                    echo "  Skipping 2I parent row for $name from 2G\n";

                    continue;
                }
            }

            // Extract student name from "Padre de ..."
            $studentName = '';
            if (preg_match('/(?:Padre|Madre) de (.*)/i', $name, $matches)) {
                $studentName = strtoupper(trim($matches[1]));
                // Clean up suffixes like " MAMA" or " PAPA" added by our previous script
                $studentName = preg_replace('/ (MAMA|PAPA|PADRE|MADRE)$/', '', $studentName);
            }

            // Determine best password
            $bestPass = $pass;
            if ($bestPass === 'password' || $bestPass === '12345678' || $bestPass === '') {
                if ($studentName && isset($prodPhoneMap[$studentName])) {
                    $bestPass = $prodPhoneMap[$studentName];
                }
            }

            // If still no pass, but email starts with 10 digits...
            if (($bestPass === 'password' || $bestPass === '') && preg_match('/^(\d{10})@/', $email, $m)) {
                $bestPass = $m[1];
            }

            $parentData[] = [
                'name' => $name,
                'email' => $email,
                'pass' => $bestPass,
            ];
        }

        // De-duplicate and harmonize passwords by email
        $finalParents = [];
        $emailToBestPass = [];

        // 1st pass: find best pass for each email
        foreach ($parentData as $pd) {
            $e = $pd['email'];
            $p = $pd['pass'];
            if (! isset($emailToBestPass[$e])) {
                $emailToBestPass[$e] = $p;
            } else {
                // Prioritize numeric 10-digit passwords
                if (preg_match('/^\d{10}$/', $p) && ! preg_match('/^\d{10}$/', $emailToBestPass[$e])) {
                    $emailToBestPass[$e] = $p;
                } elseif ($p !== '' && $p !== 'password' && ($emailToBestPass[$e] === '' || $emailToBestPass[$e] === 'password')) {
                    $emailToBestPass[$e] = $p;
                }
            }
        }

        // 2nd pass: apply best pass and de-duplicate pairs
        $uniquePairs = [];
        foreach ($parentData as $pd) {
            $e = $pd['email'];
            $bestP = $emailToBestPass[$e] ?? $pd['pass'];
            $key = $pd['name'].'|'.$e;
            if (! isset($uniquePairs[$key])) {
                $uniquePairs[$key] = [
                    'name' => $pd['name'],
                    'email' => $e,
                    'pass' => $bestP,
                ];
            }
        }

        // Refill the sheet
        $sheetP->removeRow(2, $highestRow);
        $row = 2;
        foreach ($uniquePairs as $up) {
            $sheetP->setCellValueByColumnAndRow(1, $row, $up['name']);
            $sheetP->setCellValueByColumnAndRow(2, $row, $up['email']);
            $sheetP->setCellValueByColumnAndRow(3, $row, $up['pass']);
            $row++;
        }
        echo "  Updated Padres sheet: went from $highestRow rows to ".($row - 1)." rows.\n";
    }

    $writer = new Xlsx($spreadsheet);
    $writer->save($filePath);
}

echo "Done.\n";
