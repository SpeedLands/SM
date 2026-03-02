<?php

declare(strict_types=1);

use App\Models\Student;
use App\Models\User;

test('reports students without parents', function () {
    $orphan = Student::factory()->create(['name' => 'ALUMNO SIN PADRE']);
    $withParent = Student::factory()->create(['name' => 'ALUMNO CON PADRE']);
    $parent = User::factory()->create(['role' => 'PARENT']);
    $withParent->parents()->attach($parent->id, ['relationship' => 'PADRE']);

    $this->artisan('app:check-orphans')
        ->expectsOutputToContain('ALUMNO SIN PADRE')
        ->doesntExpectOutputToContain('ALUMNO CON PADRE')
        ->assertSuccessful();
});

test('reports no orphaned students when all have parents', function () {
    $student = Student::factory()->create();
    $parent = User::factory()->create(['role' => 'PARENT']);
    $student->parents()->attach($parent->id, ['relationship' => 'PADRE']);

    $this->artisan('app:check-orphans')
        ->expectsOutputToContain('Todos los alumnos tienen al menos un padre/tutor asociado.')
        ->assertSuccessful();
});

test('reports parents without students when using --parents option', function () {
    $orphanParent = User::factory()->create(['role' => 'PARENT', 'name' => 'PADRE SIN HIJO']);
    $parentWithKid = User::factory()->create(['role' => 'PARENT', 'name' => 'PADRE CON HIJO']);
    $student = Student::factory()->create();
    $student->parents()->attach($parentWithKid->id, ['relationship' => 'PADRE']);

    $this->artisan('app:check-orphans --parents')
        ->expectsOutputToContain('PADRE SIN HIJO')
        ->doesntExpectOutputToContain('PADRE CON HIJO')
        ->assertSuccessful();
});

test('reports no orphaned parents when all parents have students', function () {
    $parent = User::factory()->create(['role' => 'PARENT']);
    $student = Student::factory()->create();
    $student->parents()->attach($parent->id, ['relationship' => 'PADRE']);

    $this->artisan('app:check-orphans --parents')
        ->expectsOutputToContain('Todos los padres/tutores tienen al menos un alumno asociado.')
        ->assertSuccessful();
});

test('non-parent users are not included in parents without students check', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $teacher = User::factory()->create(['role' => 'TEACHER']);

    $this->artisan('app:check-orphans --parents')
        ->expectsOutputToContain('Todos los padres/tutores tienen al menos un alumno asociado.')
        ->assertSuccessful();
});

