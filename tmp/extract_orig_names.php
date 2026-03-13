<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__.'/../vendor/autoload.php';

$s = IOFactory::load('ORIGINAL.xlsx')->getActiveSheet();
$out = '';
for ($i = 2; $i <= $s->getHighestRow(); $i++) {
    $out .= $s->getCellByColumnAndRow(1, $i)->getValue().PHP_EOL;
}
file_put_contents('tmp/orig_names.txt', $out);
echo 'Extracted '.$s->getHighestRow()." names.\n";
