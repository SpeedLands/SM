<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__.'/../vendor/autoload.php';

$mismatches = [
    [
        'name' => 'PEÑA VARA PAULINA ALEJANDRA',
        'from_file' => 'DATOS_CORREGIDOS/1H.xlsx',
        'to_file' => 'DATOS_CORREGIDOS/1A.xlsx',
    ],
    [
        'name' => 'MARTINEZ HERNANDEZ YEI YOSHUA DOMINIC',
        'from_file' => 'DATOS_CORREGIDOS/2I.xlsx',
        'to_file' => 'DATOS_CORREGIDOS/2G.xlsx',
    ],
];

$baseDir = 'c:/Users/juanp/Desktop/Apps/sm/';

foreach ($mismatches as $m) {
    echo "Processing {$m['name']}...\n";
    $fromPath = $baseDir.$m['from_file'];
    $toPath = $baseDir.$m['to_file'];

    if (! file_exists($fromPath)) {
        echo "Source file missing: $fromPath\n";

        continue;
    }
    if (! file_exists($toPath)) {
        echo "Target file missing: $toPath\n";

        continue;
    }

    try {
        // LOAD SOURCE
        $ssFrom = IOFactory::load($fromPath);
        $sheetAlumnosFrom = null;
        $sheetPadresFrom = null;

        foreach ($ssFrom->getSheetNames() as $name) {
            if (str_starts_with($name, 'Alumnos')) {
                $sheetAlumnosFrom = $ssFrom->getSheetByName($name);
            }
            if (str_starts_with($name, 'Padres')) {
                $sheetPadresFrom = $ssFrom->getSheetByName($name);
            }
        }

        if (! $sheetAlumnosFrom || ! $sheetPadresFrom) {
            echo "Source sheets missing in {$m['from_file']}\n";

            continue;
        }

        $studentRowData = null;
        $studentId = null;

        // Find Student
        for ($row = 2; $row <= $sheetAlumnosFrom->getHighestRow(); $row++) {
            $val = $sheetAlumnosFrom->getCellByColumnAndRow(1, $row)->getValue();
            $n = is_string($val) ? $val : '';
            if (trim(strtoupper($n)) === trim(strtoupper($m['name']))) {
                $studentRowData = [];
                for ($col = 1; $col <= 10; $col++) {
                    $studentRowData[$col] = $sheetAlumnosFrom->getCellByColumnAndRow($col, $row)->getValue();
                }
                $studentId = $sheetAlumnosFrom->getCellByColumnAndRow(4, $row)->getValue(); // Column 4 is Email/ID
                $sheetAlumnosFrom->removeRow($row);
                break;
            }
        }

        if (! $studentRowData) {
            echo "Student not found in {$m['from_file']}\n";

            continue;
        }

        // Find and Copy Parents
        $parentRecords = [];
        for ($row = $sheetPadresFrom->getHighestRow(); $row >= 2; $row--) {
            $childEmail = $sheetPadresFrom->getCellByColumnAndRow(6, $row)->getValue(); // Assuming column 6 is child email
            if ($childEmail === $studentId) {
                $pData = [];
                for ($col = 1; $col <= 10; $col++) {
                    $pData[$col] = $sheetPadresFrom->getCellByColumnAndRow($col, $row)->getValue();
                }
                $parentRecords[] = $pData;
                $sheetPadresFrom->removeRow($row);
            }
        }

        // SAVE UPDATED SOURCE
        $writerFrom = IOFactory::createWriter($ssFrom, 'Xlsx');
        $writerFrom->save($fromPath);
        echo "Removed from {$m['from_file']}\n";

        // LOAD TARGET
        $ssTo = IOFactory::load($toPath);
        $sheetAlumnosTo = null;
        $sheetPadresTo = null;

        foreach ($ssTo->getSheetNames() as $name) {
            if (str_starts_with($name, 'Alumnos')) {
                $sheetAlumnosTo = $ssTo->getSheetByName($name);
            }
            if (str_starts_with($name, 'Padres')) {
                $sheetPadresTo = $ssTo->getSheetByName($name);
            }
        }

        if (! $sheetAlumnosTo || ! $sheetPadresTo) {
            echo "Target sheets missing in {$m['to_file']}\n";

            continue;
        }

        // Check if student exists in target
        $exists = false;
        for ($row = 2; $row <= $sheetAlumnosTo->getHighestRow(); $row++) {
            $val = $sheetAlumnosTo->getCellByColumnAndRow(1, $row)->getValue();
            $n = is_string($val) ? $val : '';
            if (trim(strtoupper($n)) === trim(strtoupper($m['name']))) {
                $exists = true;
                break;
            }
        }

        if (! $exists) {
            $newRow = $sheetAlumnosTo->getHighestRow() + 1;
            foreach ($studentRowData as $col => $val) {
                $sheetAlumnosTo->setCellValueByColumnAndRow($col, $newRow, $val);
            }
            echo "Added Student to {$m['to_file']}\n";
        } else {
            echo "Student already exists in {$m['to_file']}\n";
        }

        // Add Parents to target
        foreach ($parentRecords as $p) {
            // Simple check if parent exists by email (col 2)
            $pExists = false;
            for ($row = 2; $row <= $sheetPadresTo->getHighestRow(); $row++) {
                if ($sheetPadresTo->getCellByColumnAndRow(2, $row)->getValue() === $p[2]) {
                    $pExists = true;
                    break;
                }
            }
            if (! $pExists) {
                $newPRow = $sheetPadresTo->getHighestRow() + 1;
                foreach ($p as $col => $val) {
                    $sheetPadresTo->setCellValueByColumnAndRow($col, $newPRow, $val);
                }
                echo "Added Parent record.\n";
            }
        }

        // SAVE UPDATED TARGET
        $writerTo = IOFactory::createWriter($ssTo, 'Xlsx');
        $writerTo->save($toPath);
    } catch (Exception $e) {
        echo "ERROR processing {$m['name']}: ".$e->getMessage()."\n";
        echo $e->getTraceAsString()."\n";
    }
}

echo "Done.\n";
