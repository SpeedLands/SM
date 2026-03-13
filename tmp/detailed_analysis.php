<?php

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$base = 'c:/Users/juanp/Desktop/Apps/sm/';
$alumnosPath = $base.'alumnos_2026-03-11.xlsx';
$padresPath = $base.'padres_2026-03-11.xlsx';
$originalMap = json_decode(file_get_contents($base.'tmp/original_phone_map.json'), true);

function cleanPhone($phone)
{
    if (! $phone) {
        return '';
    }

    return preg_replace('/[^0-9]/', '', (string) $phone);
}

function isIrregular($phone)
{
    $clean = cleanPhone($phone);
    if (empty($clean)) {
        return true;
    }
    if (strlen($clean) < 10) {
        return true;
    }
    if (preg_match('/^(0|1|2|3|4|5|6|7|8|9)\1+$/', $clean)) {
        return true;
    }
    if (in_array($clean, ['1234567', '12345678', '123456789', '1234567890'])) {
        return true;
    }
    if (strtolower((string) $phone) === 'password') {
        return true;
    }

    return false;
}

$analysis = [
    'padres' => [
        'total' => 0,
        'irregular' => 0,
        'fixable' => 0,
        'examples' => [],
    ],
    'alumnos' => [
        'total' => 0,
        'irregular' => 0,
        'fixable' => 0,
        'examples' => [],
    ],
];

// Analyze Alumnos
$ssA = IOFactory::load($alumnosPath);
$sheetA = $ssA->getActiveSheet();
$rowsA = $sheetA->getHighestRow();
for ($i = 2; $i <= $rowsA; $i++) {
    $analysis['alumnos']['total']++;
    $nombre = strtoupper(trim((string) $sheetA->getCell('A'.$i)->getValue()));
    $tel = $sheetA->getCell('E'.$i)->getValue();

    if (isIrregular($tel)) {
        $analysis['alumnos']['irregular']++;
        if (isset($originalMap[$nombre])) {
            $analysis['alumnos']['fixable']++;
            if (count($analysis['alumnos']['examples']) < 5) {
                $analysis['alumnos']['examples'][] = [
                    'nombre' => $nombre,
                    'current_tel' => $tel,
                    'orig_mama' => $originalMap[$nombre]['mama'],
                    'orig_papa' => $originalMap[$nombre]['papa'],
                ];
            }
        }
    }
}

// Analyze Padres
$ssP = IOFactory::load($padresPath);
$sheetP = $ssP->getActiveSheet();
$rowsP = $sheetP->getHighestRow();
for ($i = 2; $i <= $rowsP; $i++) {
    $analysis['padres']['total']++;
    $nombreP = strtoupper(trim((string) $sheetP->getCell('A'.$i)->getValue()));
    // Extract student name from "Madre de ..." or "Padre de ..."
    $studentName = preg_replace('/^(MADRE DE |PADRE DE )/', '', $nombreP);
    $tel = $sheetP->getCell('C'.$i)->getValue();
    $pass = $sheetP->getCell('D'.$i)->getValue();

    $irrTel = isIrregular($tel);
    $irrPass = (strtolower((string) $pass) === 'password' || (string) $pass === '1234567');

    if ($irrTel || $irrPass) {
        $analysis['padres']['irregular']++;
        if (isset($originalMap[$studentName])) {
            // Check if we have the corresponding parent phone
            $type = str_contains($nombreP, 'MADRE') ? 'mama' : 'papa';
            if ($originalMap[$studentName][$type]) {
                $analysis['padres']['fixable']++;
                if (count($analysis['padres']['examples']) < 5) {
                    $analysis['padres']['examples'][] = [
                        'nombre_padre' => $nombreP,
                        'student' => $studentName,
                        'current_tel' => $tel,
                        'current_pass' => $pass,
                        'orig_val' => $originalMap[$studentName][$type],
                    ];
                }
            }
        }
    }
}

file_put_contents($base.'tmp/detailed_analysis.json', json_encode($analysis, JSON_PRETTY_PRINT));
echo "Detailed analysis complete.\n";
