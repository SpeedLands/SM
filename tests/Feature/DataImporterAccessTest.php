<?php

use App\Models\User;

test('administrators can access the data importer', function () {
    $admin = User::factory()->create(['role' => 'ADMIN', 'status' => 'ACTIVE']);

    $this->actingAs($admin);
    session(['active_view' => 'staff']);

    $this->get(route('data-importer'))
        ->assertStatus(200)
        ->assertSee('Importar Datos');
});

test('teachers cannot access the data importer', function () {
    $teacher = User::factory()->create(['role' => 'TEACHER', 'status' => 'ACTIVE']);

    $this->actingAs($teacher);
    session(['active_view' => 'staff']);

    $this->get(route('data-importer'))
        ->assertStatus(403);
});

test('parents cannot access the data importer', function () {
    $parent = User::factory()->create(['role' => 'PARENT', 'status' => 'ACTIVE']);

    $this->actingAs($parent);
    session(['active_view' => 'parent']);

    $this->get(route('data-importer'))
        ->assertStatus(403);
});
