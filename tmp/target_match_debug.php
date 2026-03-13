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
    echo "  Normalizing: '$g'\n";
    $g = strtoupper(trim($g));
    if (preg_match('/^([123])[º°\-\s]*([A-I])$/u', $g, $matches)) {
        return $matches[1].$matches[2];
    }

    return null;
}

$target = 'VAZQUEZ ROMAN AARON';
$targetKey = getKey($target);
echo "Target Key: $targetKey\n\n";

// ORIGINAL
$s1 = IOFactory::load('ORIGINAL.xlsx')->getActiveSheet();
for ($i = 2; $i <= $s1->getHighestRow(); $i++) {
    $n = $s1->getCellByColumnAndRow(1, $i)->getValue();
    if (getKey($n) === $targetKey) {
        echo "Found in ORIGINAL row $i: $n\n";
        $v5 = $s1->getCellByColumnAndRow(5, $i)->getValue();
        $v6 = $s1->getCellByColumnAndRow(6, $i)->getValue();
        echo '  Col 5: '.normalizeGroup($v5)."\n";
        echo '  Col 6: '.normalizeGroup($v6)."\n";
    }
}

// PROD
$s2 = IOFactory::load('alumnos_2026-03-10.xlsx')->getSheetByName('Alumnos');
for ($i = 2; $i <= $s2->getHighestRow(); $i++) {
    $n = $s2->getCellByColumnAndRow(1, $i)->getValue();
    if (getKey($n) === $targetKey) {
        echo "\nFound in PROD row $i: $n\n";
        $v3 = $s2->getCellByColumnAndRow(3, $i)->getValue();
        echo '  Col 3: '.normalizeGroup($v3)."\n";
    }
}
