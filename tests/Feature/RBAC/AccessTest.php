<?php

use App\Models\User;
use App\Models\Student;
use Livewire\Volt\Volt;

test('it works', function () {
    expect(true)->toBeTrue();
});

test('admin can access users management', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertStatus(200);
});

test('parent cannot access users management', function () {
    $parent = User::factory()->parent()->create();

    $this->actingAs($parent)
        ->get(route('users.index'))
        ->assertStatus(403);
});

test('admin can access cycles management', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('cycles.index'))
        ->assertStatus(200);
});

test('parent cannot access cycles management', function () {
    $parent = User::factory()->parent()->create();

    $this->actingAs($parent)
        ->get(route('cycles.index'))
        ->assertStatus(403);
});

test('parent can only see their own students', function () {
    $parent = User::factory()->parent()->create();
    $child = Student::factory()->create();
    $parent->students()->attach($child->id, ['relationship' => 'PADRE']);
    
    $otherStudent = Student::factory()->create();

    Volt::actingAs($parent)
        ->test('students.index')
        ->assertSee($child->name)
        ->assertDontSee($otherStudent->name);
});

test('teacher can create reports', function () {
    $teacher = User::factory()->teacher()->create();
    
    $this->actingAs($teacher)
        ->get(route('reports.index'))
        ->assertSee('Nuevo Reporte');
});

test('parent cannot see create report button', function () {
    $parent = User::factory()->parent()->create();
    
    $this->actingAs($parent)
        ->get(route('reports.index'))
        ->assertDontSee('Nuevo Reporte');
});

test('parent cannot see register student button', function () {
    $parent = User::factory()->parent()->create();
    
    $this->actingAs($parent)
        ->get(route('students.index'))
        ->assertDontSee('Inscribir Alumno');
});

test('parent cannot open student registration modal', function () {
    $parent = User::factory()->parent()->create();
    
    Volt::actingAs($parent)
        ->test('students.index')
        ->call('openCreateModal')
        ->assertForbidden();
});
