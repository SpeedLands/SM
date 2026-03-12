<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$dir = 'c:/Users/juanp/Desktop/Apps/sm/DATOS_CORREGIDOS/';
$prodPhoneMap = json_decode(file_get_contents('c:/Users/juanp/Desktop/Apps/sm/tmp/name_phone_map.json'), true);

$orphans = [
    '1H' => [
        'BARRIENTOS ROMERO KARLA KARINA',
        'BARRON VILLASEÑOR IKER ALEJANDRO',
        'CANIZALES QUINTANILLA MOISES SEBASTIAN',
        'CERVANTES RODRIGUEZ JENNIFER',
        'DE LA CRUZ HERNANDEZ NICOL',
        'DOMINGUEZ TURRUBIATE ITZEL KAILANY',
        'FRAIRE NAVARRO DEREK CRISTOPHER',
        'GARDEA ZUÑIGA LENIN JAFET',
        'GARZA HERNANDEZ JESUS NEFTALI',
        'GUADALAJARA RODRIGUEZ JUAN ERNESTO',
        'GUERRA GONZALEZ KIMBERLY',
        'HERNANDEZ CALVILLO EMILY ITZEL',
        'LARA FLORES RICARDO ISAU',
        'LOMBRAÑA CENTENO FRANCISCO DANIEL',
        'LOPEZ CORONADO MELANY JANEY',
        'LOPEZ POSADAS MAURICIO',
        'LURIA TORRES ALLISON ELIZA',
        'MENCHACA CAZARES VANESSA',
        'MONTALVO GOMEZ DIEGO ISRAEL',
        'MUÑOZ ANTONIO SANTIAGO',
        'ONTIVEROS RIVAS DAYSHA NIKOLE',
        'PALACIOS RODRIGUEZ ABRIL AIDE',
        'RODRIGUEZ ALVAREZ MIGUEL ANGEL',
        'RODRIGUEZ SOLAR ANGEL ALEXIS',
        'ROSALES GONZALEZ LUIS FERNANDO',
        'SANCHEZ GARZA JONATHAN ALEJANDRO',
        'SERRANO PEREZ AMERICA JIMENA',
        'SERRANO SOLIS DAYANNA JAQUELINE',
        'VAZQUEZ CORONADO MARIA JOSE',
        'VELASQUEZ REYNA IAN EDUARDO',
        'VILLANUEVA MARTINEZ VIRGINIA EVANGELIQUE',
    ],
    '2I' => [
        'ACOSTA RODRIGO GONZALEZ',
        'GALLEGOS MEDINA ROGELIO ALBERTO',
        'MEDINA JESUS ALEJANDRO CRUZ',
        'RANGEL MEGAN ALEXIA RODARTE',
        'ROJAS LANDER SANDOVAL',
        'VAZQUEZ HINOSTROSA BRIANNA JAZMIN',
        'VENEGAS HERNANDEZ ANA FERNANDA',
        'ZAMORANO IVANA GEORGINA MONSIVAIS',
    ],
];

foreach ($orphans as $group => $names) {
    $file = $group.'.xlsx';
    $filePath = $dir.$file;
    if (! file_exists($filePath)) {
        echo "File $filePath not found!\n";

        continue;
    }

    echo "Fixing orphans for $group...\n";
    $spreadsheet = IOFactory::load($filePath);
    $sheetPName = 'Padres '.$group;
    $sheetP = $spreadsheet->getSheetByName($sheetPName);
    if (! $sheetP) {
        $sheetP = $spreadsheet->createSheet();
        $sheetP->setTitle($sheetPName);
        $sheetP->setCellValue('A1', 'Padre de Alumno');
        $sheetP->setCellValue('B1', 'Correo');
        $sheetP->setCellValue('C1', 'Contraseña');
    }

    $existingEmails = [];
    $highestRow = $sheetP->getHighestRow();
    for ($i = 2; $i <= $highestRow; $i++) {
        $email = trim(strtolower((string) $sheetP->getCell('B'.$i)->getValue()));
        if ($email) {
            $existingEmails[] = $email;
        }
    }

    $row = $highestRow + 1;
    foreach ($names as $name) {
        // Check if already in production map or generate
        $baseName = str_replace(' ', '', strtolower($name));
        $emailP = $baseName.'papa@escuela.edu.mx';
        $emailM = $baseName.'mama@escuela.edu.mx';

        $pass = $prodPhoneMap[strtoupper($name)] ?? '12345678';

        // Add Papa
        if (! in_array($emailP, $existingEmails)) {
            $sheetP->setCellValue('A'.$row, "Padre de $name PAPA");
            $sheetP->setCellValue('B'.$row, $emailP);
            $sheetP->setCellValue('C'.$row, $pass);
            $row++;
        }

        // Add Mama
        if (! in_array($emailM, $existingEmails)) {
            $sheetP->setCellValue('A'.$row, "Madre de $name MAMA");
            $sheetP->setCellValue('B'.$row, $emailM);
            $sheetP->setCellValue('C'.$row, $pass);
            $row++;
        }
    }

    $writer = new Xlsx($spreadsheet);
    $writer->save($filePath);
    echo '  Processed '.count($names).' students. Sheet has '.($sheetP->getHighestRow())." rows.\n";
}

echo "Done fixing orphan parents.\n";
