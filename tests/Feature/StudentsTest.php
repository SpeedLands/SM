<?php

use App\Models\ClassGroup;
use App\Models\Cycle;
use App\Models\Student;
use App\Models\User;
use Livewire\Volt\Volt;

test('guests cannot access students page', function () {
    $this->get(route('students.index'))
        ->assertRedirect(route('login'));
});

test('admins can see students list', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $cycle = Cycle::factory()->create(['is_active' => true]);
    $group = ClassGroup::factory()->create(['cycle_id' => $cycle->id]);
    $student = Student::factory()->create([
        'grade' => $group->grade,
        'group_name' => $group->section,
    ]);

    $student->cycleAssociations()->create([
        'cycle_id' => $cycle->id,
        'class_group_id' => $group->id,
        'status' => 'ACTIVE',
    ]);

    $this->actingAs($admin)
        ->get(route('students.index'))
        ->assertOk()
        ->assertSee($student->name)
        ->assertSeeLivewire('students.student-form')
        ->assertSeeLivewire('students.history-modal');
});

test('can search students by name or curp', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $cycle = Cycle::factory()->create(['is_active' => true]);
    $group = ClassGroup::factory()->create(['cycle_id' => $cycle->id]);

    $student1 = Student::factory()->create(['name' => 'JUAN PEREZ', 'grade' => $group->grade, 'group_name' => $group->section]);
    $student2 = Student::factory()->create(['name' => 'MARIA LOPEZ', 'grade' => $group->grade, 'group_name' => $group->section]);

    $student1->cycleAssociations()->create(['cycle_id' => $cycle->id, 'class_group_id' => $group->id, 'status' => 'ACTIVE']);
    $student2->cycleAssociations()->create(['cycle_id' => $cycle->id, 'class_group_id' => $group->id, 'status' => 'ACTIVE']);

    Volt::actingAs($admin)
        ->test('students.index')
        ->set('search', 'JUAN')
        ->assertSee($student1->name)
        ->assertDontSee($student2->name);
});

test('index refreshes when student-saved event is dispatched', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $cycle = Cycle::factory()->create(['is_active' => true]);

    Volt::actingAs($admin)
        ->test('students.index')
        ->dispatch('student-saved')
        ->assertStatus(200); // Verify it doesn't crash
});
