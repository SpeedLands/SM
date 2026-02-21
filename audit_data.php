<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$originalFile = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\ORIGINAL.xlsx';
$datosDir = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS\\';

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
    return strlen($phone) >= 8 ? $phone : null; // Lower threshold for international/inner phones
}

// 1. Load ORIGINAL.xlsx
echo "Cargando ORIGINAL.xlsx...\n";
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
    $studentName = normalizeName($studentNameRaw);

    $originalMap[$studentName] = [
        'raw_name' => $studentNameRaw,
        'mama_raw' => $cells[1] ?? null,
        'papa_raw' => $cells[2] ?? null,
        'mama_phone' => extractPhone($cells[1] ?? ''),
        'papa_phone' => extractPhone($cells[2] ?? ''),
    ];
}

$files = glob($datosDir . "[1-3][A-I].xlsx");
$discrepancies = [];

foreach ($files as $file) {
    $filename = basename($file);
    echo "Auditando $filename...\n";
    
    try {
        $ss = IOFactory::load($file);
        $groups = ['A','B','C','D','G','H','I'];
        $foundSheet = null;
        foreach ($groups as $g) {
            foreach (['Padres ', 'Alumnos '] as $prefix) {
                if ($sheet = $ss->getSheetByName($prefix . str_replace('.xlsx', '', $filename))) {
                    if ($prefix === 'Padres ') {
                        $foundSheet = $sheet;
                        break 2;
                    }
                }
            }
        }
        
        if (!$foundSheet) {
            $foundSheet = $ss->getSheet(0);
        }

        foreach ($foundSheet->getRowIterator(2) as $row) {
            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                $cells[] = $cell->getValue();
            }
            
            $parentNameRaw = $cells[0] ?? '';
            $email = $cells[1] ?? '';
            $phone = (string)($cells[2] ?? '');

            if (preg_match('/^(Padre|Madre|Tutor|Tutora)\s+de\s+(.+)$/i', $parentNameRaw, $m)) {
                $type = strtoupper($m[1]); 
                $studentNameRawInFile = trim($m[2]);
                $studentName = normalizeName($studentNameRawInFile);

                if (!isset($originalMap[$studentName])) {
                    $discrepancies[] = "[$filename] Alumno '$studentNameRawInFile' no encontrado en ORIGINAL.xlsx";
                    continue;
                }

                $orig = $originalMap[$studentName];
                $expectedEmail = null;
                $expectedPhone = null;

                if ($type === 'MADRE' || $type === 'MAMA') {
                    $expectedPhone = $orig['mama_phone'];
                } else if ($type === 'PADRE' || $type === 'PAPA' || $type === 'TUTOR' || $type === 'TUTORA') {
                    $expectedPhone = $orig['papa_phone'] ?? $orig['mama_phone']; // Fallback if PAPA empty
                }

                if ($expectedPhone) {
                    $expectedEmail = $expectedPhone . "@escuela.edu.mx";
                } else {
                    $expectedEmail = sanitizeForEmail($studentNameRawInFile) . "@escuela.edu.mx";
                }

                // Check discrepancies
                if ($email !== $expectedEmail) {
                    $discrepancies[] = "[$filename] Alumno '$studentNameRawInFile': Email esperado '$expectedEmail' para $type, obtenido '$email'";
                }
                
                if ($expectedPhone && (string)$phone !== (string)$expectedPhone) {
                    $discrepancies[] = "[$filename] Alumno '$studentNameRawInFile': Teléfono esperado '$expectedPhone' para $type, obtenido '$phone'";
                }
            }
        }
    } catch (\Exception $e) {
        $discrepancies[] = "[$filename] Error procesando archivo: " . $e->getMessage();
    }
}

file_put_contents('audit_results.txt', implode("\n", $discrepancies));
echo "Auditoría finalizada. Resultados en audit_results.txt\n";
