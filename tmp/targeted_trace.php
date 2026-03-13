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

$targetKey = getKey('VAZQUEZ ROMAN AARON');

$origData = [];
$s1 = IOFactory::load('ORIGINAL.xlsx')->getActiveSheet();
for ($i = 2; $i <= $s1->getHighestRow(); $i++) {
    $n = $s1->getCellByColumnAndRow(1, $i)->getValue();
    $key = getKey($n);
    if (! $key) {
        continue;
    }

    $g5Str = $s1->getCellByColumnAndRow(5, $i)->getValue();
    $g6Str = $s1->getCellByColumnAndRow(6, $i)->getValue();
    $g5 = normalizeGroup($g5Str);
    $g6 = normalizeGroup($g6Str);
    $group = $g5 ?: $g6;

    if ($key === $targetKey) {
        echo "ORIGINAL Row $i: '$n' | G5: '$g5Str' -> $g5 | G6: '$g6Str' -> $g6 | Final: $group\n";
    }

    if ($group) {
        $origData[$key][] = ['name' => $n, 'group' => $group];
    }
}

$spreadsheetProd = IOFactory::load('alumnos_2026-03-10.xlsx');
$s2 = $spreadsheetProd->getSheetByName('Alumnos');

for ($i = 2; $i <= $s2->getHighestRow(); $i++) {
    $n = $s2->getCellByColumnAndRow(1, $i)->getValue();
    $key = getKey($n);
    if ($key === $targetKey) {
        $g3Str = $s2->getCellByColumnAndRow(3, $i)->getValue();
        $group = normalizeGroup($g3Str);
        echo "PROD Row $i: '$n' | G3: '$g3Str' -> $group\n";

        if (isset($origData[$key])) {
            echo 'MATCH FOUND in origData. Orig groups: '.implode(', ', array_column($origData[$key], 'group'))."\n";
            if (in_array($group, array_column($origData[$key], 'group'))) {
                echo "Result: MATCHED\n";
            } else {
                echo "Result: MISMATCH!\n";
            }
        }
    }
}
