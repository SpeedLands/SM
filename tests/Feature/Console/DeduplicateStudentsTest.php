<?php

declare(strict_types=1);

use App\Models\Student;
use App\Models\User;

test('detecta duplicados por curp en dry-run y no elimina nada', function () {
    $curp = 'AAAA000101HABCDE01';

    Student::factory()->create(['name' => 'ALUMNO UNO', 'curp' => $curp]);
    Student::factory()->create(['name' => 'ALUMNO UNO', 'curp' => $curp]);

    $this->artisan('students:deduplicate')
        ->expectsOutputToContain('DRY-RUN')
        ->expectsOutputToContain('CONSERVAR')
        ->expectsOutputToContain('ELIMINAR')
        ->assertSuccessful();

    expect(Student::where('curp', $curp)->count())->toBe(2); // dry-run: no borró
});

test('conserva al alumno con mas relaciones', function () {
    $curp = 'BBBB000101HABCDE02';

    $richStudent = Student::factory()->create(['name' => 'ALUMNO RICO', 'curp' => $curp]);
    $poorStudent = Student::factory()->create(['name' => 'ALUMNO POBRE', 'curp' => $curp]);

    // Attach a parent only to the "rich" student
    $parent = User::factory()->create(['role' => 'PARENT']);
    $richStudent->parents()->attach($parent->id, ['relationship' => 'PADRE']);

    $this->artisan('students:deduplicate --force')
        ->assertSuccessful();

    // The rich student (with relations) should survive
    expect(Student::find($richStudent->id))->not->toBeNull();
    expect(Student::find($poorStudent->id))->toBeNull();
});

test('elimina copias y transfiere padres al original', function () {
    $curp = 'CCCC000101HABCDE03';

    $original = Student::factory()->create(['name' => 'ORIGINAL', 'curp' => $curp]);
    $copy = Student::factory()->create(['name' => 'COPIA', 'curp' => $curp]);

    // Give relations to the one we want to keep (ORIGINAL)
    $parent = User::factory()->create(['role' => 'PARENT']);
    $original->parents()->attach($parent->id, ['relationship' => 'MADRE']);

    $this->artisan('students:deduplicate --force')
        ->assertSuccessful();

    // Copy is deleted
    expect(Student::find($copy->id))->toBeNull();

    // Original is kept and has the parent
    $original->refresh();
    expect($original->parents()->where('parent_id', $parent->id)->exists())->toBeTrue();
});

test('no hace nada cuando no hay duplicados', function () {
    Student::factory()->create(['curp' => 'DDDD000101HABCDE04']);
    Student::factory()->create(['curp' => 'EEEE000101HABCDE05']);

    $this->artisan('students:deduplicate')
        ->expectsOutputToContain('No se encontraron alumnos duplicados por CURP')
        ->assertSuccessful();
});

test('con --by-name encuentra duplicados de mismo nombre sin curp', function () {
    // Two students with same name, no CURP
    Student::factory()->create(['name' => 'JUAN PEREZ GARCIA', 'curp' => null, 'grade' => '1º']);
    Student::factory()->create(['name' => 'JUAN PEREZ GARCIA', 'curp' => null, 'grade' => '1º']);

    $this->artisan('students:deduplicate --by-name')
        ->expectsOutputToContain('JUAN PEREZ GARCIA')
        ->assertSuccessful();
});
