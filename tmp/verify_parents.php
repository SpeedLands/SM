<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__.'/../vendor/autoload.php';

$groups = ['2G', '3G'];
$dir = 'DATOS_CORREGIDOS';

foreach ($groups as $group) {
    $file = "$dir/$group.xlsx";
    echo "Verifying $file...\n";
    try {
        $spreadsheet = IOFactory::load($file);

        $parentSheet = null;
        foreach ($spreadsheet->getSheetNames() as $name) {
            if (stripos($name, 'Padres') !== false) {
                $parentSheet = $spreadsheet->getSheetByName($name);
                break;
            }
        }

        if (! $parentSheet) {
            echo "  [FAIL] Padres sheet not found.\n";

            continue;
        }

        $highestRow = $parentSheet->getHighestRow();
        echo "  Sheet '{$parentSheet->getTitle()}': $highestRow rows\n";

        // Check the last few rows
        $start = max(1, $highestRow - 5);
        for ($row = $start; $row <= $highestRow; $row++) {
            $name = $parentSheet->getCellByColumnAndRow(1, $row)->getValue();
            $phone = $parentSheet->getCellByColumnAndRow(3, $row)->getValue();
            echo "    Row $row: $name | Phone: $phone\n";
        }
    } catch (Exception $e) {
        echo '  Error: '.$e->getMessage()."\n";
    }
    echo "\n";
}
