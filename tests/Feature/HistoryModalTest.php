<?php

declare(strict_types=1);

use App\Models\Student;
use App\Models\User;
use Livewire\Volt\Volt;

test('history-modal opens when view-history event is dispatched', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $student = Student::factory()->create(['name' => 'ALUMNO HISTORIAL']);

    Volt::actingAs($admin)
        ->test('students.history-modal')
        ->call('open', $student->id)
        ->assertSet('show', true)
        ->assertSet('studentName', 'ALUMNO HISTORIAL');
});

test('history-modal shows empty state when no records found', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $student = Student::factory()->create();

    Volt::actingAs($admin)
        ->test('students.history-modal')
        ->call('open', $student->id)
        ->assertSee('Sin historial');
});
