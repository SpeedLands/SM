<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$dir = 'c:/Users/juanp/Desktop/Apps/sm/DATOS_CORREGIDOS/';

foreach (['1H.xlsx', '2I.xlsx'] as $file) {
    $filePath = $dir.$file;
    if (! file_exists($filePath)) {
        continue;
    }

    echo "Refining names in $file...\n";
    $spreadsheet = IOFactory::load($filePath);
    $sheetName = 'Padres '.str_replace('.xlsx', '', $file);
    $sheet = $spreadsheet->getSheetByName($sheetName);

    if (! $sheet) {
        continue;
    }

    $highestRow = $sheet->getHighestRow();
    for ($i = 2; $i <= $highestRow; $i++) {
        $val = (string) $sheet->getCell('A'.$i)->getValue();
        // Remove "Padre de " / "Madre de " and the " PAPA" / " MAMA" suffix
        $clean = preg_replace('/^(Padre de |Madre de )/i', '', $val);
        $clean = preg_replace('/ (PAPA|MAMA)$/i', '', $clean);

        $sheet->setCellValue('A'.$i, trim($clean));
    }

    $writer = new Xlsx($spreadsheet);
    $writer->save($filePath);
    echo "  Done.\n";
}

echo "All names refined.\n";
