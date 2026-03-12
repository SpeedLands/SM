<?php

require __DIR__.'/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$prodPath = 'c:/Users/juanp/Desktop/Apps/sm/alumnos_2026-03-10.xlsx';
$origPath = 'c:/Users/juanp/Desktop/Apps/sm/ORIGINAL.xlsx';

function normalizeName($name)
{
    return strtoupper(trim($name));
}

function normalizeGroup($group)
{
    // Standardize "1º A" vs "1A" etc
    $group = strtoupper(trim($group));
    if (preg_match('/^([123])[º°]?\s*([A-I])$/u', $group, $matches)) {
        return $matches[1].$matches[2];
    }

    return $group;
}

// 1. Load Original
$spreadsheetOrig = IOFactory::load($origPath);
$sheetOrig = $spreadsheetOrig->getActiveSheet();
$origData = [];
for ($row = 2; $row <= $sheetOrig->getHighestRow(); $row++) {
    $name = normalizeName($sheetOrig->getCellByColumnAndRow(1, $row)->getValue());
    $group = normalizeGroup($sheetOrig->getCellByColumnAndRow(5, $row)->getValue());
    if ($name) {
        $origData[$name] = $group;
    }
}

// 2. Load Prod
$spreadsheetProd = IOFactory::load($prodPath);
$sheetProd = $spreadsheetProd->getSheetByName('Alumnos');
$mismatches = [];

for ($row = 2; $row <= $sheetProd->getHighestRow(); $row++) {
    $name = normalizeName($sheetProd->getCellByColumnAndRow(1, $row)->getValue());
    $prodGroup = normalizeGroup($sheetProd->getCellByColumnAndRow(3, $row)->getValue());

    if (isset($origData[$name])) {
        $origGroup = $origData[$name];
        if ($prodGroup !== $origGroup) {
            $mismatches[] = [
                'name' => $name,
                'prod' => $prodGroup,
                'orig' => $origGroup,
            ];
        }
    }
}

echo json_encode($mismatches, JSON_PRETTY_PRINT);
