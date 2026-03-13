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
    if (! $g) {
        return null;
    }
    $g = strtoupper(trim($g));
    if (preg_match('/^([123])[º°\-\s]*([A-I])$/u', $g, $matches)) {
        return $matches[1].$matches[2];
    }

    return null;
}

$origData = [];
echo "Loading ORIGINAL.xlsx...\n";
$s1 = IOFactory::load('ORIGINAL.xlsx')->getActiveSheet();
$origCount = 0;
for ($i = 2; $i <= $s1->getHighestRow(); $i++) {
    $n = $s1->getCellByColumnAndRow(1, $i)->getValue();
    $key = getKey($n);
    if (! $key) {
        continue;
    }

    $g5 = normalizeGroup($s1->getCellByColumnAndRow(5, $i)->getValue());
    $g6 = normalizeGroup($s1->getCellByColumnAndRow(6, $i)->getValue());
    $group = $g5 ?: $g6;

    if ($group) {
        $origData[$key][] = ['name' => $n, 'group' => $group, 'row' => $i];
        $origCount++;
    }
}
echo "Loaded $origCount students with groups from ORIGINAL.\n";

$prodData = [];
echo "Loading alumnos_2026-03-10.xlsx...\n";
$spreadsheetProd = IOFactory::load('alumnos_2026-03-10.xlsx');
$sheetNames = $spreadsheetProd->getSheetNames();
echo 'Sheet names: '.implode(', ', $sheetNames)."\n";

$s2 = $spreadsheetProd->getSheetByName('Alumnos');
if (! $s2) {
    echo "ERROR: Sheet 'Alumnos' not found!\n";
    exit;
}

$prodCount = 0;
for ($i = 2; $i <= $s2->getHighestRow(); $i++) {
    $n = $s2->getCellByColumnAndRow(1, $i)->getValue();
    $key = getKey($n);
    if (! $key) {
        continue;
    }

    $group = normalizeGroup($s2->getCellByColumnAndRow(3, $i)->getValue());
    if ($group) {
        $prodData[$key][] = ['name' => $n, 'group' => $group, 'row' => $i];
        $prodCount++;
    }
}
echo "Loaded $prodCount students with groups from PROD.\n";

echo "Comparing...\n";
$mismatches = [];
$matchedKeys = 0;
foreach ($prodData as $key => $pRecords) {
    if (isset($origData[$key])) {
        $matchedKeys++;
        foreach ($pRecords as $p) {
            $foundMatch = false;
            $origGroups = [];
            foreach ($origData[$key] as $o) {
                $origGroups[] = $o['group'];
                if ($p['group'] === $o['group']) {
                    $foundMatch = true;
                    break;
                }
            }
            if (! $foundMatch) {
                $mismatches[] = [
                    'name' => $p['name'],
                    'prod_group' => $p['group'],
                    'orig_groups' => array_unique($origGroups),
                    'prod_row' => $p['row'],
                ];
            }
        }
    }
}

echo "Matched Keys: $matchedKeys\n";
echo 'Total Mismatches: '.count($mismatches)."\n";
if (count($mismatches) > 0) {
    echo json_encode($mismatches, JSON_PRETTY_PRINT);
}
