<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$dir = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS\\';
$files = glob($dir . "*.xlsx");

$targetEmails = ['8787014347@escuela.edu.mx', '8781179264@escuela.edu.mx'];

echo "BÚSQUEDA GLOBAL EN DATOS/ (Originales):\n";

foreach ($files as $file) {
    if (str_contains($file, '~')) continue;
    $filename = basename($file);
    
    try {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $ss = $reader->load($file);
        
        foreach ($ss->getAllSheets() as $sheet) {
            $sheetName = $sheet->getTitle();
            $highestRow = $sheet->getHighestRow();
            $highestCol = $sheet->getHighestColumn();
            $highestColIdx = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
            
            for ($row = 1; $row <= $highestRow; $row++) {
                $found = false;
                $rowVals = [];
                for ($col = 1; $col <= $highestColIdx; $col++) {
                    $val = (string)$sheet->getCellByColumnAndRow($col, $row)->getValue();
                    $rowVals[] = "[$val]";
                    foreach ($targetEmails as $target) {
                        if (mb_stripos($val, $target) !== false) $found = true;
                    }
                }
                if ($found) {
                    echo "File: $filename | Sheet: $sheetName | Row: $row\n";
                    echo "  Data: " . implode(" ", $rowVals) . "\n";
                }
            }
        }
        $ss->disconnectWorksheets();
        unset($ss);
    } catch (\Exception $e) {
        // Skip errors
    }
}
