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
    $str = trim(strtoupper($str));
    // Remove "PADRE DE ", "MADRE DE ", "TUTOR DE " prefixes if they exist
    $str = preg_replace('/^(PADRE|MADRE|TUTOR)\s+DE\s+/', '', $str);
    // Remove accents
    $str = strtr(utf8_decode($str), utf8_decode('ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ'), 'AAAAAAACEEEEIIIIDNOOOOOOUUUUYBsaaaaaaaceeeeiiiidnoooooouuuuyby');
    $str = preg_replace('/\s+/', ' ', $str);

    return trim($str);
}

$originalFile = 'ORIGINAL.xlsx';
$prodFile = 'alumnos_2026-03-10.xlsx';

echo "Reading NEW ORIGINAL.xlsx...\n";
$originalData = Excel::toCollection(new class {}, $originalFile);
$originalStudents = [];

foreach ($originalData as $sheetIndex => $sheet) {
    echo "Processing ORIGINAL Sheet $sheetIndex...\n";
    foreach ($sheet as $rowIndex => $row) {
        $row = $row->toArray();
        if ($rowIndex === 0 || empty($row[0])) {
            continue;
        }
        if (strtoupper($row[0]) === 'NOMBRE') {
            continue;
        }

        $fullName = normalize($row[0]);
        if (strlen($fullName) < 5) {
            continue;
        }

        $originalStudents[$fullName] = [
            'name' => $fullName,
            'row' => $row,
        ];
    }
}

echo 'Total unique students in NEW ORIGINAL: '.count($originalStudents)."\n\n";

echo "Reading $prodFile...\n";
$prodData = Excel::toCollection(new class {}, $prodFile);
$prodStudents = [];

foreach ($prodData as $sheetIndex => $sheet) {
    foreach ($sheet as $rowIndex => $row) {
        $row = $row->toArray();
        if ($rowIndex === 0 || empty($row[0])) {
            continue;
        } // Skip header

        $fullName = normalize($row[0]);
        if (strlen($fullName) < 5) {
            continue;
        }

        $prodStudents[$fullName] = [
            'name' => $fullName,
            'row' => $row,
        ];
    }
}

echo 'Total unique students in PROD: '.count($prodStudents)."\n\n";

$missingInProd = [];
foreach ($originalStudents as $name => $student) {
    if (! isset($prodStudents[$name])) {
        // Try word matching just in case
        $origWords = explode(' ', $name);
        sort($origWords);

        $found = false;
        foreach ($prodStudents as $p_name => $p_student) {
            $pWords = explode(' ', $p_name);
            sort($pWords);
            if (implode(' ', $origWords) === implode(' ', $pWords)) {
                $found = true;
                break;
            }
        }

        if (! $found) {
            $missingInProd[] = $student;
        }
    }
}

echo "--- COMPARISON RESULTS ---\n";
echo "Missing in $prodFile: ".count($missingInProd)." students\n";
foreach ($missingInProd as $s) {
    echo "  - {$s['name']}\n";
}
