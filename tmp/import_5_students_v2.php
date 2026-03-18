<?php

use App\Models\Cycle;
use App\Models\Student;
use App\Models\StudentCycleAssociation;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function getBirthDateFromCurp($curp)
{
    // Chars 4-5: YY, 6-7: MM, 8-9: DD
    $yy = substr($curp, 4, 2);
    $mm = substr($curp, 6, 2);
    $dd = substr($curp, 8, 2);

    // Assume 20xx for now since they are students
    $year = (int) $yy < 30 ? "20$yy" : "19$yy";

    try {
        return Carbon::createFromFormat('Y-m-d', "$year-$mm-$dd");
    } catch (Exception $e) {
        return Carbon::now(); // Fallback
    }
}

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
        'birth_date' => getBirthDateFromCurp($data['curp']),
        'grade' => $data['grade'],
        'group_name' => $data['group_name'],
        'turn' => $data['turn'],
    ]);

    if ($activeCycle) {
        StudentCycleAssociation::updateOrCreate([
            'student_id' => $student->id,
            'cycle_id' => $activeCycle->id,
        ], [
            'grade' => $data['grade'],
            'group_name' => $data['group_name'],
        ]);
    }

    echo "Creado: {$data['name']}\n";
}
