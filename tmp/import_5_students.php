<?php

use App\Models\Cycle;
use App\Models\Student;
use App\Models\StudentCycleAssociation;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$students = [
    [
        'name' => 'CRUZ GARCIA CRISTIAN ALEJANDRO',
        'curp' => 'CUGC111109HNERRRA3',
        'grade' => 3,
        'group_name' => 'G',
        'turn' => 'VESPERTINO',
    ],
    [
        'name' => 'MONCADA RAMIREZ ALFREDO',
        'curp' => 'MORA110726HCLNMLA1',
        'grade' => 3,
        'group_name' => 'G',
        'turn' => 'VESPERTINO',
    ],
    [
        'name' => 'PIZARRO LUCIO JESUS MANUEL',
        'curp' => 'PILJ110104HCLZCSA0',
        'grade' => 3,
        'group_name' => 'G',
        'turn' => 'VESPERTINO',
    ],
    [
        'name' => 'ESCOBEDO VILLAZANA ANGELA YANET',
        'curp' => 'EOVA120111MCLSLNA7',
        'grade' => 2,
        'group_name' => 'G',
        'turn' => 'VESPERTINO',
    ],
    [
        'name' => 'VASQUEZ MENDEZ NAOMI ELIZABETH',
        'curp' => 'VAMN120314MCLSNMA9',
        'grade' => 2,
        'group_name' => 'G',
        'turn' => 'VESPERTINO',
    ],
];

$activeCycle = Cycle::where('is_active', true)->first();

foreach ($students as $data) {
    if (Student::where('curp', $data['curp'])->exists()) {
        echo "Skip: {$data['name']} (CURP ya existe)\n";

        continue;
    }

    $student = Student::create([
        'name' => $data['name'],
        'curp' => $data['curp'],
        'grade' => $data['grade'],
        'group_name' => $data['group_name'],
        'turn' => $data['turn'],
    ]);

    if ($activeCycle) {
        StudentCycleAssociation::create([
            'student_id' => $student->id,
            'cycle_id' => $activeCycle->id,
            'grade' => $data['grade'],
            'group_name' => $data['group_name'],
        ]);
    }

    echo "Creado: {$data['name']}\n";
}
