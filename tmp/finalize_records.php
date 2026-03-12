<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$toFind = [
    'CRUZ GARCIA CRISTIAN ALEJANDRO' => 'CUGC111109HNERRRA3',
    'MONCADA RAMIREZ ALFREDO' => 'MORA110726HCLNMLA1',
    'PIZARRO LUCIO JESUS MANUEL' => 'PILJ110104HCLZCSA0',
    'ESCOBEDO VILLAZANA ANGELA YANET' => 'EOVA120111MCLSLNA7',
    'VASQUEZ MENDEZ NAOMI ELIZABETH' => 'VAMN120314MCLSNMA9',
];

$originalData = json_decode(file_get_contents('tmp/extracted_students.json'), true);

$finalData = [];

foreach ($toFind as $name => $curp) {
    if (isset($originalData[$name])) {
        $row = $originalData[$name];
        // Map to final format:
        // 0: Nombre
        // 1: Turno
        // 2: Grado/Grupo
        // 3: Dirección
        // 4: Teléfono
        // 5: Otro Contacto
        // 6: CURP

        $finalData[$name] = [
            $name,                       // 0
            $row[4] ?? 'VESPERTINO',     // 1
            $row[3] ?? '',               // 2
            '',                          // 3 (Address not found in master)
            $row[1] ?? '',               // 4 (Mama)
            $row[2] ?? '',               // 5 (Papa)
            $curp,                        // 6
        ];
    }
}

file_put_contents('tmp/final_student_records.json', json_encode($finalData));
echo "Finalized records in tmp/final_student_records.json\n";
