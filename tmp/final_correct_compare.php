<?php

require __DIR__.'/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

function getKey($name)
{
    if (! $name) {
        return '';
    }
    // Normalize spaces and case
    $name = preg_replace('/\s+/', ' ', strtoupper(trim($name)));
    $parts = array_filter(explode(' ', $name));
    sort($parts);

    return implode(' ', $parts);
}

function normalizeGroup($g)
{
    if (! $g) {
        return null;
    }
    $g = strtoupper(trim($g));
    // Match patterns like "3º A", "3-A", "3 A", "3A"
    if (preg_match('/^([123])[º°\-\s]*([A-I])$/u', $g, $matches)) {
        return $matches[1].$matches[2];
    }

    return null;
}

$origPath = 'c:/Users/juanp/Desktop/Apps/sm/ORIGINAL.xlsx';
$prodPath = 'c:/Users/juanp/Desktop/Apps/sm/alumnos_2026-03-10.xlsx';

$origData = [];
$spreadsheetOrig = IOFactory::load($origPath);
$sheetOrig = $spreadsheetOrig->getActiveSheet();
for ($i = 2; $i <= $sheetOrig->getHighestRow(); $i++) {
    $n = $sheetOrig->getCellByColumnAndRow(1, $i)->getValue();
    $key = getKey($n);
    if (! $key) {
        continue;
    }

    $g = normalizeGroup($sheetOrig->getCellByColumnAndRow(4, $i)->getValue());
    if ($g) {
        $origData[$key][] = ['name' => $n, 'group' => $g, 'row' => $i];
    }
}

$mismatches = [];
$spreadsheetProd = IOFactory::load($prodPath);
$sheetProd = $spreadsheetProd->getSheetByName('Alumnos');
for ($i = 2; $i <= $sheetProd->getHighestRow(); $i++) {
    $n = $sheetProd->getCellByColumnAndRow(1, $i)->getValue();
    $key = getKey($n);
    if (! $key || ! isset($origData[$key])) {
        continue;
    }

    $prodGroup = normalizeGroup($sheetProd->getCellByColumnAndRow(3, $i)->getValue());
    if (! $prodGroup) {
        continue;
    }

    $foundMatch = false;
    $origGroups = [];
    foreach ($origData[$key] as $o) {
        $origGroups[] = $o['group'];
        if ($prodGroup === $o['group']) {
            $foundMatch = true;
            break;
        }
    }

    if (! $foundMatch) {
        $mismatches[] = [
            'name' => $n,
            'prod_row' => $i,
            'prod_group' => $prodGroup,
            'orig_name' => $origData[$key][0]['name'],
            'orig_groups' => array_unique($origGroups),
        ];
    }
}

echo json_encode($mismatches, JSON_PRETTY_PRINT);
file_put_contents('tmp/final_mismatch_report_v2.json', json_encode($mismatches, JSON_PRETTY_PRINT));
