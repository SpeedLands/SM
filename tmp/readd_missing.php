<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function getEmail($name)
{
    if (! $name) {
        return '';
    }
    $name = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ'], ['A', 'E', 'I', 'O', 'U', 'U', 'N'], strtoupper(trim($name)));

    return strtolower(str_replace(' ', '', $name)).'@escuela.edu.mx';
}

$readds = [
    [
        'name' => 'PEÑA VARA PAULINA ALEJANDRA',
        'target_file' => 'DATOS_CORREGIDOS/1A.xlsx',
        'parents' => [
            ['name' => 'JAVIER', 'phone' => '86-16-74-7144'],
        ],
    ],
    [
        'name' => 'MARTINEZ HERNANDEZ YEI YOSHUA DOMINIC',
        'target_file' => 'DATOS_CORREGIDOS/2G.xlsx',
        'parents' => [
            ['name' => 'MAMA', 'phone' => '878-163-10-38'],
            ['name' => 'PAPA', 'phone' => '878-159-55-90'],
        ],
    ],
];

$base = 'c:/Users/juanp/Desktop/Apps/sm/';

foreach ($readds as $r) {
    echo "Re-adding {$r['name']} to {$r['target_file']}...\n";
    $path = $base.$r['target_file'];

    if (! file_exists($path)) {
        echo "File missing: $path\n";

        continue;
    }

    $ss = IOFactory::load($path);
    $sheetAlumnos = null;
    $sheetPadres = null;

    foreach ($ss->getSheetNames() as $sn) {
        if (str_starts_with($sn, 'Alumnos')) {
            $sheetAlumnos = $ss->getSheetByName($sn);
        }
        if (str_starts_with($sn, 'Padres')) {
            $sheetPadres = $ss->getSheetByName($sn);
        }
    }

    if (! $sheetAlumnos || ! $sheetPadres) {
        echo "Sheets missing in $path\n";

        continue;
    }

    $studentEmail = getEmail($r['name']);

    // Check if student exists
    $found = false;
    for ($i = 2; $i <= $sheetAlumnos->getHighestRow(); $i++) {
        if (trim(strtoupper((string) $sheetAlumnos->getCellByColumnAndRow(1, $i)->getValue())) === strtoupper($r['name'])) {
            $found = true;
            break;
        }
    }

    if (! $found) {
        $newRow = $sheetAlumnos->getHighestRow() + 1;
        $sheetAlumnos->setCellValueByColumnAndRow(1, $newRow, strtoupper($r['name']));
        $sheetAlumnos->setCellValueByColumnAndRow(2, $newRow, 'Vespertino');
        $sheetAlumnos->setCellValueByColumnAndRow(4, $newRow, $studentEmail);
        echo "  Added Student.\n";
    } else {
        echo "  Student already exists.\n";
    }

    // Add Parents
    foreach ($r['parents'] as $p) {
        $pEmail = getEmail('Padre de '.$r['name'].' '.$p['name']);

        $pFound = false;
        for ($i = 2; $i <= $sheetPadres->getHighestRow(); $i++) {
            if ($sheetPadres->getCellByColumnAndRow(2, $i)->getValue() === $pEmail) {
                $pFound = true;
                break;
            }
        }

        if (! $pFound) {
            $newPRow = $sheetPadres->getHighestRow() + 1;
            $sheetPadres->setCellValueByColumnAndRow(1, $newPRow, 'Padre de '.$r['name']);
            $sheetPadres->setCellValueByColumnAndRow(2, $newPRow, $pEmail);
            $sheetPadres->setCellValueByColumnAndRow(3, $newPRow, 'password');
            $sheetPadres->setCellValueByColumnAndRow(4, $newPRow, $p['phone']);
            $sheetPadres->setCellValueByColumnAndRow(6, $newPRow, $studentEmail);
            echo "  Added Parent: {$p['name']}\n";
        } else {
            echo "  Parent already exists: {$p['name']}\n";
        }
    }

    $writer = IOFactory::createWriter($ss, 'Xlsx');
    $writer->save($path);
}

echo "Done.\n";
