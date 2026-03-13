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
$log = '';

function logger($msg)
{
    global $log;
    $log .= $msg."\n";
    echo $msg."\n";
}

foreach ($mismatches as $m) {
    logger("Processing {$m['name']}...");
    $fromPath = $baseDir.$m['from_file'];
    $toPath = $baseDir.$m['to_file'];

    if (! file_exists($fromPath)) {
        logger("  Source file missing: $fromPath");

        continue;
    }
    if (! file_exists($toPath)) {
        logger("  Target file missing: $toPath");

        continue;
    }

    try {
        // --- EXTRACTION FROM SOURCE ---
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

        if (! $sheetAlumnosFrom) {
            logger("  Source Alumnos sheet missing in {$m['from_file']}");

            continue;
        }

        $studentRowData = null;
        $studentId = null;
        for ($row = 2; $row <= $sheetAlumnosFrom->getHighestRow(); $row++) {
            $val = $sheetAlumnosFrom->getCellByColumnAndRow(1, $row)->getValue();
            if (trim(strtoupper((string) $val)) === trim(strtoupper($m['name']))) {
                $studentRowData = [];
                for ($col = 1; $col <= 10; $col++) {
                    $studentRowData[$col] = $sheetAlumnosFrom->getCellByColumnAndRow($col, $row)->getValue();
                }
                $studentId = (string) $sheetAlumnosFrom->getCellByColumnAndRow(4, $row)->getValue();
                logger("  Found student in row $row. ID: $studentId");
                $sheetAlumnosFrom->removeRow($row);
                break;
            }
        }

        if (! $studentRowData) {
            logger('  Student not found in source.');
        } else {
            // Find and Copy Parents
            $parentRecords = [];
            if ($sheetPadresFrom) {
                for ($row = $sheetPadresFrom->getHighestRow(); $row >= 2; $row--) {
                    $childEmail = (string) $sheetPadresFrom->getCellByColumnAndRow(6, $row)->getValue();
                    if ($childEmail === $studentId) {
                        $pData = [];
                        for ($col = 1; $col <= 10; $col++) {
                            $pData[$col] = $sheetPadresFrom->getCellByColumnAndRow($col, $row)->getValue();
                        }
                        $parentRecords[] = $pData;
                        $sheetPadresFrom->removeRow($row);
                    }
                }
                logger('  Found '.count($parentRecords).' parent records.');
            }

            // Save Source
            $writerFrom = IOFactory::createWriter($ssFrom, 'Xlsx');
            $writerFrom->save($fromPath);
            logger('  Saved source file.');
        }

        // --- ADDITION TO TARGET ---
        // Even if not found in source, we check target to be sure
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
            logger("  Target sheets missing in {$m['to_file']}");

            continue;
        }

        // Add Student
        if ($studentRowData) {
            $exists = false;
            for ($row = 2; $row <= $sheetAlumnosTo->getHighestRow(); $row++) {
                $val = $sheetAlumnosTo->getCellByColumnAndRow(1, $row)->getValue();
                if (trim(strtoupper((string) $val)) === trim(strtoupper($m['name']))) {
                    $exists = true;
                    break;
                }
            }
            if (! $exists) {
                $newRow = $sheetAlumnosTo->getHighestRow() + 1;
                foreach ($studentRowData as $col => $val) {
                    $sheetAlumnosTo->setCellValueByColumnAndRow($col, $newRow, $val);
                }
                logger("  Added student to target at row $newRow.");
            } else {
                logger('  Student already exists in target.');
            }

            // Add Parents
            foreach ($parentRecords as $p) {
                $pExists = false;
                for ($row = 2; $row <= $sheetPadresTo->getHighestRow(); $row++) {
                    if ((string) $sheetPadresTo->getCellByColumnAndRow(2, $row)->getValue() === (string) $p[2]) {
                        $pExists = true;
                        break;
                    }
                }
                if (! $pExists) {
                    $newPRow = $sheetPadresTo->getHighestRow() + 1;
                    foreach ($p as $col => $val) {
                        $sheetPadresTo->setCellValueByColumnAndRow($col, $newPRow, $val);
                    }
                    logger("  Added parent record to target at row $newPRow.");
                }
            }
        }

        // Save Target
        $writerTo = IOFactory::createWriter($ssTo, 'Xlsx');
        $writerTo->save($toPath);
        logger('  Saved target file.');

    } catch (Exception $e) {
        logger('  ERROR: '.$e->getMessage());
    }
}

file_put_contents('tmp/refine_groups_v2.log', $log);
echo "Done.\n";
