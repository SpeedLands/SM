<?php

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'c:/Users/juanp/Desktop/Apps/sm/ORIGINAL.xlsx';
echo "Loading $file...\n";
try {
    $ss = IOFactory::load($file);
    echo 'Loaded. Highest row: '.$ss->getActiveSheet()->getHighestRow()."\n";
} catch (Exception $e) {
    echo 'ERROR: '.$e->getMessage()."\n";
    echo 'TRACE: '.$e->getTraceAsString()."\n";
}
