<?php

require __DIR__.'/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

function getKey($name)
{
    if (! $name) {
        return '';
    }
    $parts = array_filter(explode(' ', strtoupper(trim($name))));
    sort($parts);

    return implode(' ', $parts);
}

function normalizeGroup($g)
{
    $g = strtoupper(trim($g));
    // Match patterns like "3º A", "3-A", "3 A", "3A"
    if (preg_match('/^([123])[º°\-\s]*([A-I])$/u', $g, $matches)) {
        return $matches[1].$matches[2];
    }

    return null;
}

// 1. Load Original
$spreadsheetOrig = IOFactory::load('ORIGINAL.xlsx');
$sheetOrig = $spreadsheetOrig->getActiveSheet();
$origData = [];
for ($row = 2; $row <= $sheetOrig->getHighestRow(); $row++) {
    $name = $sheetOrig->getCellByColumnAndRow(1, $row)->getValue();
    $key = getKey($name);
    if (! $key) {
        continue;
    }

    $g5 = normalizeGroup($sheetOrig->getCellByColumnAndRow(5, $row)->getValue());
    $g6 = normalizeGroup($sheetOrig->getCellByColumnAndRow(6, $row)->getValue());
    $group = $g5 ?: $g6;

    if (! $group) {
        continue;
    }

    // Store all group occurrences for this name
    $origData[$key][] = [
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
    $key = getKey($name);
    if (! $key || ! isset($origData[$key])) {
        continue;
    }

    $prodGroup = normalizeGroup($sheetProd->getCellByColumnAndRow(3, $row)->getValue());
    if (! $prodGroup) {
        continue;
    }

    $foundMatchInOrig = false;
    $origGroups = [];
    foreach ($origData[$key] as $orig) {
        $origGroups[] = $orig['group'];
        if ($prodGroup === $orig['group']) {
            $foundMatchInOrig = true;
            break;
        }
    }

    if (! $foundMatchInOrig) {
        $mismatches[] = [
            'name' => $name,
            'prod_group' => $prodGroup,
            'orig_groups' => array_unique($origGroups),
            'prod_row' => $row,
        ];
    }
}

echo json_encode($mismatches, JSON_PRETTY_PRINT);
