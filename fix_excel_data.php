<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$originalFile = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\ORIGINAL.xlsx';
$datosDir = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS\\';
$outputDir = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\';

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

function normalizeName($text) {
    if (!$text) return '';
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
        ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
        $text
    );
    return preg_replace('/\s+/', ' ', $text);
}

function sanitizeForEmail($name) {
    if (!$name) return '';
    $normalized = normalizeName($name);
    return preg_replace('/[^a-z0-9]/', '', str_replace(' ', '', $normalized));
}

function extractPhone($value) {
    if (!$value) return null;
    preg_match_all('/\d+/', (string)$value, $matches);
    $phone = implode('', $matches[0]);
    return strlen($phone) >= 8 ? $phone : null;
}

// 1. Map ORIGINAL.xlsx
echo "Mapeando ORIGINAL.xlsx...\n";
$spreadsheet = IOFactory::load($originalFile);
$sheet = $spreadsheet->getActiveSheet();
$originalMap = [];
foreach ($sheet->getRowIterator(2) as $row) {
    $cells = [];
    foreach ($row->getCellIterator() as $cell) {
        $cells[] = $cell->getValue();
    }
    $studentNameRaw = trim($cells[0] ?? '');
    if (!$studentNameRaw) continue;
    $studentNameEnc = normalizeName($studentNameRaw);

    $originalMap[$studentNameEnc] = [
        'mama_phone' => extractPhone($cells[1] ?? ''),
        'papa_phone' => extractPhone($cells[2] ?? ''),
        'student_raw' => $studentNameRaw
    ];
}

$files = glob($datosDir . "[1-3][A-I].xlsx");

foreach ($files as $file) {
    $filename = basename($file);
    echo "Procesando y Limpiando $filename...\n";
    
    $ss = IOFactory::load($file);
    
    // ---------------------------------------------------------
    // CLEANUP & FIX: Padres Sheet
    // ---------------------------------------------------------
    $sheetNamePadres = 'Padres ' . str_replace('.xlsx', '', $filename);
    $padresSheet = $ss->getSheetByName($sheetNamePadres);
    if (!$padresSheet) $padresSheet = $ss->getSheet(0);

    $highestRowPadres = $padresSheet->getHighestRow();
    
    $studentRowsMapPadres = [];
    $rowsToDelete = [];
    $rowUpdates = [];

    // PASS 1: Group rows by student name
    for ($row = 2; $row <= $highestRowPadres; $row++) {
        $parentNameRaw = trim((string)$padresSheet->getCell('A' . $row)->getValue());
        if (!$parentNameRaw) continue;

        if (preg_match('/^(Padre|Madre|Tutor|Tutora|Abuelo|Abuela|Tio|Tia)\s+de\s+(.+)$/i', $parentNameRaw, $m)) {
            $type = strtoupper($m[1]); 
            $studentNameEnc = normalizeName(trim($m[2]));
            
            if (!isset($studentRowsMapPadres[$studentNameEnc])) {
                $studentRowsMapPadres[$studentNameEnc] = [];
            }
            $studentRowsMapPadres[$studentNameEnc][] = [
                'row' => $row,
                'type' => $type
            ];
        } else {
            // Not matching standard format, maybe header artifact, mark for deletion just in case
            $rowsToDelete[] = $row;
        }
    }

    // PASS 2: Determine which rows to keep per student
    foreach ($studentRowsMapPadres as $studentNameEnc => $rows) {
        if (!isset($originalMap[$studentNameEnc])) {
            // Student not in ORIGINAL, delete all rows
            foreach ($rows as $r) {
                $rowsToDelete[] = $r['row'];
            }
            continue;
        }

        $orig = $originalMap[$studentNameEnc];
        $phones = [];
        if (!empty($orig['mama_phone'])) {
            $phones['M'] = $orig['mama_phone'];
        }
        if (!empty($orig['papa_phone']) && $orig['papa_phone'] !== ($orig['mama_phone'] ?? '')) {
            $phones['P'] = $orig['papa_phone'];
        }

        if (empty($phones)) {
            // NO PHONES: Keep ONE row only.
            $keptRow = $rows[0];
            $expectedEmail = sanitizeForEmail($orig['student_raw']) . "@escuela.edu.mx";
            $rowUpdates[$keptRow['row']] = ['email' => $expectedEmail, 'phone' => ''];
            
            // Delete the rest
            for ($i = 1; $i < count($rows); $i++) {
                $rowsToDelete[] = $rows[$i]['row'];
            }
        } else {
            // HAS PHONES: Keep at most count($phones) rows.
            $matchedPhones = [];
            $unmatchedRows = [];
            
            foreach ($rows as $r) {
                $type = $r['type'];
                if (($type === 'MAMA' || $type === 'MADRE') && isset($phones['M']) && !isset($matchedPhones['M'])) {
                    $matchedPhones['M'] = $r;
                } elseif (($type === 'PAPA' || $type === 'PADRE') && isset($phones['P']) && !isset($matchedPhones['P'])) {
                    $matchedPhones['P'] = $r;
                } else {
                    $unmatchedRows[] = $r;
                }
            }
            
            // Unused phones
            $unusedPhones = [];
            foreach (['M', 'P'] as $k) {
                if (isset($phones[$k]) && !isset($matchedPhones[$k])) {
                    $unusedPhones[] = $phones[$k];
                }
            }
            
            $keptRowsForStudent = [];
            foreach ($matchedPhones as $k => $r) {
                $keptRowsForStudent[] = ['row' => $r['row'], 'phone' => $phones[$k]];
            }
            foreach ($unusedPhones as $p) {
                if (count($unmatchedRows) > 0) {
                    $r = array_shift($unmatchedRows);
                    $keptRowsForStudent[] = ['row' => $r['row'], 'phone' => $p];
                }
            }
            
            // Mark remaining unmatched rows to delete
            foreach ($unmatchedRows as $r) {
                $rowsToDelete[] = $r['row'];
            }
            
            // Prepare updates for kept rows
            foreach ($keptRowsForStudent as $kr) {
                $rowUpdates[$kr['row']] = [
                    'email' => $kr['phone'] . "@escuela.edu.mx",
                    'phone' => $kr['phone']
                ];
            }
        }
    }

    // APPLY UPDATES AND DELETIONS
    foreach ($rowUpdates as $rowIdx => $data) {
        $padresSheet->setCellValue('B' . $rowIdx, $data['email']);
        $padresSheet->setCellValue('C' . $rowIdx, $data['phone']);
        $padresSheet->setCellValue('E' . $rowIdx, 'Padre');
    }

    rsort($rowsToDelete);
    $rowsToDelete = array_unique($rowsToDelete);
    foreach ($rowsToDelete as $rowIdx) {
        if ($rowIdx >= 2) {
            $padresSheet->removeRow((int)$rowIdx);
        }
    }

    // ---------------------------------------------------------
    // CLEANUP: Alumnos Sheet
    // ---------------------------------------------------------
    $sheetNameAlumnos = 'Alumnos ' . str_replace('.xlsx', '', $filename);
    $alumnosSheet = $ss->getSheetByName($sheetNameAlumnos);
    if ($alumnosSheet) {
        $highestRowAlumnos = $alumnosSheet->getHighestRow();
        for ($row = $highestRowAlumnos; $row >= 2; $row--) {
            $studentNameRaw = $alumnosSheet->getCell('A' . $row)->getValue();
            if (!$studentNameRaw) continue;
            
            $studentNameEnc = normalizeName($studentNameRaw);
            if (!isset($originalMap[$studentNameEnc])) {
                $alumnosSheet->removeRow($row);
            }
        }
    }

    $writer = IOFactory::createWriter($ss, 'Xlsx');
    $writer->save($outputDir . $filename);
}

echo "Limpieza finalizada. Archivos depurados en DATOS_CORREGIDOS/\n";
