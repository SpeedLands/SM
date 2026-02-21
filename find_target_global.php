<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$dir = 'c:\\Users\\juanp\\Desktop\\Apps\\sm\\DATOS_CORREGIDOS\\';
$files = glob($dir . "*.xlsx");

$targetEmail = '8787014347@escuela.edu.mx';

echo "BÚSQUEDA GLOBAL DE '$targetEmail' EN DATOS_CORREGIDOS/:\n";

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
                    if (mb_stripos($val, $targetEmail) !== false) $found = true;
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
    }
}
