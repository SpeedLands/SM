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

    echo "Adding required prefixes to names in $file...\n";
    $spreadsheet = IOFactory::load($filePath);
    $sheetName = 'Padres '.str_replace('.xlsx', '', $file);
    $sheet = $spreadsheet->getSheetByName($sheetName);

    if (! $sheet) {
        continue;
    }

    $highestRow = $sheet->getHighestRow();
    // Assuming parents are added in pairs: Papa then Mama
    for ($i = 2; $i <= $highestRow; $i++) {
        $val = (string) $sheet->getCell('A'.$i)->getValue();

        // Ensure no leftover suffixes
        $clean = preg_replace('/ (PAPA|MAMA)$/i', '', $val);
        $clean = preg_replace('/^(Padre de |Madre de )/i', '', $clean);

        // Read corresponding email to decide if it's Papa or Mama (using the odd/even logic since I added them in pairs)
        // Or check the email content (papa vs mama in string)
        $email = (string) $sheet->getCell('B'.$i)->getValue();

        if (str_contains($email, 'papa')) {
            $newVal = 'Padre de '.trim($clean);
        } elseif (str_contains($email, 'mama')) {
            $newVal = 'Madre de '.trim($clean);
        } else {
            // Fallback to alternating if email doesn't have it
            $newVal = ($i % 2 == 0) ? 'Padre de '.trim($clean) : 'Madre de '.trim($clean);
        }

        $sheet->setCellValue('A'.$i, $newVal);
    }

    $writer = new Xlsx($spreadsheet);
    $writer->save($filePath);
    echo "  Done.\n";
}

echo "All names prefixed correctly.\n";
