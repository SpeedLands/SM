<?php

use App\Models\Cycle;
use App\Models\Student;
use App\Services\ExcelImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('stundent import normalizes turn casing to satisfy database constraints', function () {
    // Create an active cycle
    $cycle = Cycle::factory()->create(['is_active' => true]);

    $service = new ExcelImportService;

    // Row format: Nombre | Turno | Grado / Grupo | Dirección | Teléfono | Otro Contacto | CURP
    $rows = collect([
        ['AGUILAR RAMIREZ XIMENA GUADALUPE', 'Matutino', '3A', 'Calle Falsa 123', '12345678', 'N/A', 'abcd010101hgrrrr01'],
        ['MORALES LOPEZ JUAN', 'vespertino', '3A', 'Av. Siempre Viva 742', '87654321', 'N/A', null],
    ]);

    $result = $service->importStudents($rows, null, '3º', 'A');

    expect($result['summary']['students']['total'])->toBe(2);

    // Verify first student
    $student1 = Student::where('name', 'AGUILAR RAMIREZ XIMENA GUADALUPE')->first();
    expect($student1->turn)->toBe('MATUTINO');
    expect($student1->curp)->toBe('ABCD010101HGRRRR01');

    // Verify second student
    $student2 = Student::where('name', 'MORALES LOPEZ JUAN')->first();
    expect($student2->turn)->toBe('VESPERTINO');
    expect($student2->curp)->toBeNull();
});
