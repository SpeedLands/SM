<?php

use App\Models\Cycle;
use App\Models\Student;
use App\Models\User;
use App\Services\ExcelImportService;
use Illuminate\Support\Facades\Hash;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->activeCycle = Cycle::factory()->create(['is_active' => true]);
    $this->service = new ExcelImportService;
});

it('handles parent with multiple children by concatenating names', function () {
    // 1. Setup Students
    $student1 = Student::factory()->create([
        'name' => 'JUAN PEREZ',
        'grade' => '3º',
        'group_name' => 'A',
    ]);

    $student2 = Student::factory()->create([
        'name' => 'MARIA PEREZ',
        'grade' => '3º',
        'group_name' => 'A',
    ]);

    // 2. Mock Excel Rows (2 rows for the same parent email)
    // Format: Nombre | Correo | Teléfono | Contraseña | Rol | Ocupación
    $rows = collect([
        ['Padre de JUAN PEREZ', 'padre@example.com', '1234567890', 'password123', 'PARENT', 'Ingeniero'],
        ['Padre de MARIA PEREZ', 'padre@example.com', '1234567890', 'password123', 'PARENT', 'Ingeniero'],
    ]);

    // 3. Execute Import
    $report = $this->service->importParents($rows, '3º', 'A');

    // 4. Assertions
    $user = User::where('email', 'padre@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Padre de JUAN PEREZ, MARIA PEREZ')
        ->and($report['summary']['parents']['total'])->toBe(1)
        ->and($report['summary']['parents']['with_multiple_children'])->toBe(1)
        ->and($report['summary']['links']['successful'])->toBe(2);

    // Verify relations
    expect($user->students)->toHaveCount(2)
        ->and($user->students->pluck('name')->toArray())->toContain('JUAN PEREZ', 'MARIA PEREZ');
});

it('handles fuzzy matching for student names', function () {
    // 1. Setup Student with specific name
    $student = Student::factory()->create([
        'name' => 'ALEJANDRO GONZALEZ',
        'grade' => '3º',
        'group_name' => 'A',
    ]);

    // 2. Mock Excel Row with slight typo (e.g., ALEJANDO)
    $rows = collect([
        ['Padre de ALEJANDO GONZALEZ', 'mama@example.com', '9876543210', 'secret123', 'PARENT', 'Doctora'],
    ]);

    // 3. Execute
    $report = $this->service->importParents($rows, '3º', 'A');

    // 4. Assertions
    $user = User::where('email', 'mama@example.com')->first();
    expect($user->students)->toHaveCount(1)
        ->and($user->students->first()->name)->toBe('ALEJANDRO GONZALEZ')
        ->and($report['notifications']['success'])->toHaveCount(1)
        ->and($report['notifications']['success'][0]['type'])->toBe('fuzzy_match');
});

it('reports warning when student is not found', function () {
    // 1. Mock Excel Row with non-existent student
    $rows = collect([
        ['Padre de ESTUDIANTE INEXISTENTE', 'error@example.com', '0000', '123', 'PARENT', 'N/A'],
    ]);

    // 2. Execute
    $report = $this->service->importParents($rows, '3º', 'A');

    // 3. Assertions
    expect($report['notifications']['warnings'])->toHaveCount(1)
        ->and($report['notifications']['warnings'][0]['type'])->toBe('student_not_found')
        ->and(User::where('email', 'error@example.com')->count())->toBe(0);
});

it('reports error for invalid email', function () {
    $rows = collect([
        ['Padre de ALUMNO', 'not-an-email', '123', '123', 'PARENT', 'N/A'],
    ]);

    $report = $this->service->importParents($rows, '3º', 'A');

    expect($report['notifications']['errors'])->toHaveCount(1)
        ->and($report['notifications']['errors'][0]['type'])->toBe('invalid_email');
});

it('always updates password if provided in excel', function () {
    // 1. Setup existing user
    $user = User::factory()->create([
        'email' => 'update@example.com',
        'password' => Hash::make('old-password'),
        'role' => 'PARENT',
    ]);

    $student = Student::factory()->create([
        'name' => 'HIJO',
        'grade' => '3º',
        'group_name' => 'A',
    ]);

    // 2. Mock Excel Row with NEW password
    $rows = collect([
        ['Padre de HIJO', 'update@example.com', '123', 'new-password-789', 'PARENT', 'N/A'],
    ]);

    // 3. Execute
    $this->service->importParents($rows, '3º', 'A');

    // 4. Verify password change
    $user->refresh();
    expect(Hash::check('new-password-789', $user->password))->toBeTrue();
});

it('silently skips header rows containing field names', function () {
    // Mock Excel Rows with a header row like "Nombre | Correo | ..."
    $rows = collect([
        ['Nombre', 'Correo', 'Teléfono', 'Contraseña', 'Rol', 'Ocupación'], // Header row
        ['Padre de HIJO', 'valid@example.com', '123', 'pass', 'PARENT', 'N/A'], // Data row
    ]);

    $student = Student::factory()->create([
        'name' => 'HIJO',
        'grade' => '3º',
        'group_name' => 'A',
    ]);

    $report = $this->service->importParents($rows, '3º', 'A');

    // Should only have 1 parent total (skipped the header)
    expect($report['summary']['parents']['total'])->toBe(1)
        ->and($report['notifications']['errors'])->toBeEmpty();
});

it('accumulates children names across different imports (cross-group)', function () {
    // 1. First import for child A
    $rows1 = collect([
        ['Padre de HIJO A', 'papa@test.com', '123', 'pass', 'PARENT', 'N/A'],
    ]);

    $studentA = Student::factory()->create(['name' => 'HIJO A', 'grade' => '3º', 'group_name' => 'A']);

    $this->service->importParents($rows1, '3º', 'A');

    $user = User::where('email', 'papa@test.com')->first();
    expect($user->name)->toBe('Padre de HIJO A');

    // 2. Second import for child B (different group)
    $rows2 = collect([
        ['Padre de HIJO B', 'papa@test.com', '123', 'pass', 'PARENT', 'N/A'],
    ]);

    $studentB = Student::factory()->create(['name' => 'HIJO B', 'grade' => '1º', 'group_name' => 'B']);

    $this->service->importParents($rows2, '1º', 'B');

    $user->refresh();
    // Names should be accumulated
    expect($user->name)->toContain('HIJO A')
        ->and($user->name)->toContain('HIJO B');
});

it('protects staff profiles (Admin/Teacher) during parent import', function () {
    // 1. Setup Admin User
    $admin = User::factory()->create([
        'name' => 'PROFESOR JUAN',
        'email' => 'juan@colegio.com',
        'role' => 'TEACHER',
        'password' => Hash::make('docente123'),
    ]);

    $student = Student::factory()->create(['name' => 'HIJO DEL PROFE', 'grade' => '3º', 'group_name' => 'A']);

    // 2. Import Excel row for same email as parent
    $rows = collect([
        ['Padre de HIJO DEL PROFE', 'juan@colegio.com', '555', 'pass_excel', 'PARENT', 'Profesor'],
    ]);

    $report = $this->service->importParents($rows, '3º', 'A');

    // 3. Assertions
    $admin->refresh();

    // Name and Role should NOT change
    expect($admin->name)->toBe('PROFESOR JUAN')
        ->and($admin->role)->toBe('TEACHER');

    // Password should NOT change
    expect(Hash::check('docente123', $admin->password))->toBeTrue();

    // Relation should STILL be created
    expect($admin->students)->toHaveCount(1)
        ->and($admin->students->first()->name)->toBe('HIJO DEL PROFE');

    // Notification should be present
    expect($report['notifications']['success'])->toHaveCount(1)
        ->and($report['notifications']['success'][0]['type'])->toBe('staff_parent')
        ->and($report['notifications']['success'][0]['user_name'])->toBe('PROFESOR JUAN')
        ->and($report['notifications']['success'][0]['children'])->toContain('HIJO DEL PROFE');
});

it('summarizes parent name when there are more than 3 children', function () {
    // 1. Setup 5 Students
    $students = [];
    for ($i = 1; $i <= 5; $i++) {
        $students[] = Student::factory()->create([
            'name' => "HIJO {$i}",
            'grade' => '3º',
            'group_name' => 'A',
        ]);
    }

    // 2. Mock Excel Rows (5 rows for the same parent)
    $rows = collect([]);
    foreach ($students as $student) {
        $rows->push(["Padre de {$student->name}", 'multihijos@example.com', '123', 'pass', 'PARENT', 'N/A']);
    }

    // 3. Execute
    $this->service->importParents($rows, '3º', 'A');

    // 4. Assertions
    $user = User::where('email', 'multihijos@example.com')->first();

    // Format: Padre de 5 alumnos (HIJO 1, HIJO 2, HIJO 3 y 2 más)
    expect($user->name)->toContain('Padre de 5 alumnos')
        ->and($user->name)->toContain('HIJO 1, HIJO 2, HIJO 3')
        ->and($user->name)->toContain('y 2 más');
});

it('ensures parent name never exceeds 255 characters', function () {
    // 1. Setup 3 Students with names at the database limit (100)
    $students = [];
    for ($i = 0; $i < 3; $i++) {
        $students[] = Student::factory()->create([
            'name' => str_repeat(chr(ord('A') + $i), 100),
            'grade' => '3º',
            'group_name' => 'A',
        ]);
    }

    // 2. Mock Excel Rows with correct format to exceed 255 chars in total
    $rows = collect([]);
    foreach ($students as $student) {
        $rows->push(["Padre de {$student->name}", 'longname@example.com', '123', 'pass', 'PARENT', 'N/A']);
    }

    // 3. Execute
    $this->service->importParents($rows, '3º', 'A');

    // 4. Assertions
    $user = User::where('email', 'longname@example.com')->first();
    expect($user)->not->toBeNull()
        ->and(strlen($user->name))->toBeLessThanOrEqual(255);
});
