<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$students = json_decode(file_get_contents('tmp/extracted_students.json'), true);

$groups = [
    '2G' => ['ESCOBEDO VILLAZANA ANGELA YANET', 'VASQUEZ MENDEZ NAOMI ELIZABETH'],
    '3G' => ['CRUZ GARCIA CRISTIAN ALEJANDRO', 'MONCADA RAMIREZ ALFREDO', 'PIZARRO LUCIO JESUS MANUEL'],
];

function getPhone($str)
{
    if (! $str) {
        return null;
    }
    preg_match_all('/\d{10}/', $str, $matches);

    return ! empty($matches[0]) ? $matches[0][0] : null;
}

foreach ($groups as $group => $studentNames) {
    $file = "tmp/{$group}_tmp.xlsx";
    echo "Updating Padres sheet in $file...\n";

    try {
        $spreadsheet = IOFactory::load($file);

        // Find sheet that contains "Padres"
        $parentSheet = null;
        foreach ($spreadsheet->getSheetNames() as $name) {
            if (stripos($name, 'Padres') !== false) {
                $parentSheet = $spreadsheet->getSheetByName($name);
                break;
            }
        }

        if (! $parentSheet) {
            echo "Error: Padre sheet not found in $file.\n";

            continue;
        }

        $nextRow = $parentSheet->getHighestRow() + 1;

        foreach ($studentNames as $name) {
            $data = $students[$name] ?? null;
            if (! $data) {
                continue;
            }

            // Item 1: Mama (data[1])
            $phone1 = getPhone($data[1] ?? '');
            if ($phone1) {
                $row = [
                    "Madre de $name",
                    "{$phone1}@escuela.edu.mx",
                    $phone1,
                    bin2hex(random_bytes(5)),
                    'Padre',
                    '',
                ];
                foreach ($row as $col => $val) {
                    $parentSheet->setCellValueByColumnAndRow($col + 1, $nextRow, $val);
                }
                echo "  Added Madre for $name at row $nextRow\n";
                $nextRow++;
            }

            // Item 2: Papa (data[2])
            $phone2 = getPhone($data[2] ?? '');
            if ($phone2) {
                $row = [
                    "Padre de $name",
                    "{$phone2}@escuela.edu.mx",
                    $phone2,
                    bin2hex(random_bytes(5)),
                    'Padre',
                    '',
                ];
                foreach ($row as $col => $val) {
                    $parentSheet->setCellValueByColumnAndRow($col + 1, $nextRow, $val);
                }
                echo "  Added Padre for $name at row $nextRow\n";
                $nextRow++;
            }
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($file);
        echo "Saved $file\n";
    } catch (\Exception $e) {
        echo 'Error: '.$e->getMessage()."\n";
    }
    echo "\n";
}
