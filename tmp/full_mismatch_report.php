<?php

require __DIR__.'/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

function getParts($name)
{
    if (! $name) {
        return [];
    }

    return array_filter(explode(' ', strtoupper(trim($name))));
}

function matchParts($parts1, $parts2)
{
    if (empty($parts1) || empty($parts2)) {
        return false;
    }
    if (count($parts1) !== count($parts2)) {
        return false;
    }
    sort($parts1);
    sort($parts2);

    return $parts1 === $parts2;
}

function normalizeGroup($g)
{
    $g = strtoupper(trim($g));
    if (preg_match('/^([123])[º°]?\s*([A-I])$/u', $g, $matches)) {
        return $matches[1].$matches[2];
    }

    return $g;
}

// 1. Load Original
$spreadsheetOrig = IOFactory::load('ORIGINAL.xlsx');
$sheetOrig = $spreadsheetOrig->getActiveSheet();
$origData = [];
for ($row = 2; $row <= $sheetOrig->getHighestRow(); $row++) {
    $name = $sheetOrig->getCellByColumnAndRow(1, $row)->getValue();
    $parts = getParts($name);
    if (empty($parts)) {
        continue;
    }

    // Find group in col 5 or 6
    $g5 = normalizeGroup($sheetOrig->getCellByColumnAndRow(5, $row)->getValue());
    $g6 = normalizeGroup($sheetOrig->getCellByColumnAndRow(6, $row)->getValue());
    $group = preg_match('/^[123][A-I]$/', $g5) ? $g5 : (preg_match('/^[123][A-I]$/', $g6) ? $g6 : $g5);

    $key = implode(' ', $parts);
    $origData[$key] = [
        'name' => $name,
        'group' => $group,
        'row' => $row,
    ];
}

// 2. Load Prod
$spreadsheetProd = IOFactory::load('alumnos_2026-03-10.xlsx');
$sheetProd = $spreadsheetProd->getSheetByName('Alumnos');
$mismatches = [];

for ($row = 2; $row <= $sheetProd->getHighestRow(); $row++) {
    $name = $sheetProd->getCellByColumnAndRow(1, $row)->getValue();
    $parts = getParts($name);
    if (empty($parts)) {
        continue;
    }
    $key = implode(' ', $parts);

    $prodGroup = normalizeGroup($sheetProd->getCellByColumnAndRow(3, $row)->getValue());

    if (isset($origData[$key])) {
        $orig = $origData[$key];
        if ($prodGroup !== $orig['group'] && $prodGroup && $orig['group']) {
            $mismatches[] = [
                'name' => $name,
                'prod_group' => $prodGroup,
                'orig_group' => $orig['group'],
                'orig_row' => $orig['row'],
                'prod_row' => $row,
            ];
        }
    }
}

echo json_encode($mismatches, JSON_PRETTY_PRINT);
