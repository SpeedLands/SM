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
    $str = strtr(utf8_decode($str), utf8_decode('ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ'), 'AAAAAAACEEEEIIIIDNOOOOOOUUUUYBsaaaaaaaceeeeiiiidnoooooouuuuyby');

    return preg_replace('/\s+/', ' ', $str);
}

$toFind = [
    'CRUZ GARCIA CRISTIAN ALEJANDRO' => 'CUGC111109HNERRRA3',
    'MONCADA RAMIREZ ALFREDO' => 'MORA110726HCLNMLA1',
    'PIZARRO LUCIO JESUS MANUEL' => 'PILJ110104HCLZCSA0',
    'ESCOBEDO VILLAZANA ANGELA YANET' => 'EOVA120111MCLSLNA7',
    'VASQUEZ MENDEZ NAOMI ELIZABETH' => 'VAMN120314MCLSNMA9',
];

$dir = 'DATOS_CORREGIDOS';
$files = array_diff(scandir($dir), ['.', '..']);

echo "Searching in $dir...\n";

$results = [];
foreach ($toFind as $name => $curp) {
    $results[$name] = ['found' => false, 'file' => null, 'curp_match' => false, 'found_curp' => null];
}

foreach ($files as $file) {
    if (! str_ends_with($file, '.xlsx')) {
        continue;
    }

    try {
        $sheets = Excel::toCollection(new class {}, "$dir/$file");
        $sheet = $sheets->first();

        foreach ($sheet as $row) {
            $rowArr = $row->toArray();
            $rowName = normalize($rowArr[0] ?? '');

            foreach ($toFind as $name => $targetCurp) {
                if (normalize($name) === $rowName) {
                    $results[$name]['found'] = true;
                    $results[$name]['file'] = $file;
                    $foundCurp = strtoupper(trim((string) ($rowArr[6] ?? '')));
                    $results[$name]['found_curp'] = $foundCurp;
                    $results[$name]['curp_match'] = ($foundCurp === $targetCurp);
                }
            }
        }
    } catch (\Exception $e) {
        // Skip files that can't be read
    }
}

echo "Verification Results:\n";
foreach ($results as $name => $res) {
    if ($res['found']) {
        $curpStatus = $res['curp_match'] ? 'CORRECTO' : "INCORRECTO (Encontrado: {$res['found_curp']})";
        echo "✅ $name: Encontrado en [{$res['file']}]. CURP: $curpStatus\n";
    } else {
        echo "❌ $name: NO ENCONTRADO en la carpeta DATOS_CORREGIDOS.\n";
    }
}
