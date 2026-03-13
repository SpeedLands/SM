<?php

use Maatwebsite\Excel\Facades\Excel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function normalize($str)
{
    if (! $str) {
        return '';
    }
    $str = strtoupper(trim((string) $str));
    // Very aggressive normalization
    $str = strtr(utf8_decode($str), utf8_decode('ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ'), 'AAAAAAACEEEEIIIIDNOOOOOOUUUUYBsaaaaaaaceeeeiiiidnoooooouuuuyby');

    return preg_replace('/[^A-Z]/', '', $str);
}

$toFind = [
    'CRUZ GARCIA CRISTIAN ALEJANDRO',
    'MONCADA RAMIREZ ALFREDO',
    'PIZARRO LUCIO JESUS MANUEL',
    'ESCOBEDO VILLAZANA ANGELA YANET',
    'VASQUEZ MENDEZ NAOMI ELIZABETH',
];

$normalizedToFind = array_map('normalize', $toFind);

$filesToCheck = ['ORIGINAL.xlsx', 'CURP DATA.xlsx'];

foreach ($filesToCheck as $file) {
    echo "--- File: $file ---\n";
    try {
        $sheets = Excel::toCollection(new class {}, $file);
        foreach ($sheets as $sheetName => $sheet) {
            foreach ($sheet as $rowIndex => $row) {
                $rowArr = $row->toArray();
                $rowName = normalize($rowArr[0] ?? '');

                foreach ($normalizedToFind as $index => $normTarget) {
                    if (str_contains($rowName, $normTarget) || ($rowName !== '' && str_contains($normTarget, $rowName))) {
                        echo "Found [{$toFind[$index]}] as \"{$rowArr[0]}\" in sheet \"$sheetName\" row $rowIndex. CURP: ".($rowArr[6] ?? 'N/A')."\n";
                    }
                }
            }
        }
    } catch (\Exception $e) {
        echo "Error reading $file: ".$e->getMessage()."\n";
    }
}
