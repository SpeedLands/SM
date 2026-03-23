<?php

declare(strict_types=1);

use App\Models\ClassGroup;
use App\Models\Cycle;
use App\Models\Student;
use App\Models\User;
use Livewire\Volt\Volt;

test('admins can enroll a new student via student-form', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $cycle = Cycle::factory()->create(['is_active' => true]);
    $group = ClassGroup::factory()->create(['cycle_id' => $cycle->id, 'grade' => '2º', 'section' => 'B']);

    Volt::actingAs($admin)
        ->test('students.student-form')
        ->call('openCreate')
        ->assertSet('show', true)
        ->set('name', 'ALUMNO NUEVO')
        ->set('turn', 'MATUTINO')
        ->set('classGroupId', $group->id)
        ->set('address', 'Calle Falsa 123')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('show', false)
        ->assertDispatched('student-saved');

    $student = Student::where('name', 'ALUMNO NUEVO')->first();
    expect($student)->not->toBeNull();
    expect($student->name)->toBe('ALUMNO NUEVO');

    // Verify PII
    expect($student->pii->address_encrypted)->toBe('Calle Falsa 123');

    // Verify Cycle Association
    expect($student->cycleAssociations()->where('cycle_id', $cycle->id)->exists())->toBeTrue();
});

test('admins can edit an existing student via student-form', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $cycle = Cycle::factory()->create(['is_active' => true]);
    $group = ClassGroup::factory()->create(['cycle_id' => $cycle->id]);
    $student = Student::factory()->create(['grade' => $group->grade, 'group_name' => $group->section]);

    Volt::actingAs($admin)
        ->test('students.student-form')
        ->call('openEdit', $student->id)
        ->assertSet('show', true)
        ->assertSet('name', $student->name)
        ->set('classGroupId', $group->id)
        ->set('name', 'NOMBRE ACTUALIZADO')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('student-saved');

    $student->refresh();
    expect($student->name)->toBe('NOMBRE ACTUALIZADO');
});

test('admins can manage parents in student-form', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $cycle = Cycle::factory()->create(['is_active' => true]);
    $group = ClassGroup::factory()->create(['cycle_id' => $cycle->id]);
    $student = Student::factory()->create(['grade' => $group->grade, 'group_name' => $group->section]);
    $parent = User::factory()->create(['role' => 'PARENT', 'name' => 'PADRE PRUEBA']);

    Volt::actingAs($admin)
        ->test('students.student-form')
        ->call('openEdit', $student->id)
        ->set('parentSearch', 'PADRE')
        ->assertSet('parentSearchResults', function ($results) use ($parent) {
            return $results->contains('id', $parent->id);
        })
        ->set('selectedParentId', $parent->id)
        ->set('parentRelationship', 'PADRE')
        ->call('addParent')
        ->assertSet('currentParents', function ($parents) use ($parent) {
            return $parents->contains('id', $parent->id);
        });

    expect($student->parents()->wherePivot('parent_id', $parent->id)->exists())->toBeTrue();

    // Test removal
    Volt::actingAs($admin)
        ->test('students.student-form')
        ->call('openEdit', $student->id)
        ->call('removeParent', $parent->id)
        ->assertSet('currentParents', function ($parents) use ($parent) {
            return ! $parents->contains('id', $parent->id);
        });

    expect($student->parents()->wherePivot('parent_id', $parent->id)->exists())->toBeFalse();
});
