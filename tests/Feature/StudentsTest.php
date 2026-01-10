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

    $this->actingAs($admin)
        ->get(route('students.index'))
        ->assertOk()
        ->assertSee($student->name)
        ->assertSee($student->curp);
});

test('can search students by name or curp', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $cycle = Cycle::factory()->create(['is_active' => true]);
    $group = ClassGroup::factory()->create(['cycle_id' => $cycle->id]);

    $student1 = Student::factory()->create(['name' => 'JUAN PEREZ', 'curp' => 'PERE800101HDFRRR01', 'grade' => $group->grade, 'group_name' => $group->section]);
    $student2 = Student::factory()->create(['name' => 'MARIA LOPEZ', 'curp' => 'LOPE900202MDFRRR02', 'grade' => $group->grade, 'group_name' => $group->section]);

    Volt::actingAs($admin)
        ->test('students.index')
        ->set('search', 'JUAN')
        ->assertSee($student1->name)
        ->assertDontSee($student2->name)
        ->set('search', 'LOPE900')
        ->assertSee($student2->name)
        ->assertDontSee($student1->name);
});

test('admins can enroll a new student', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $cycle = Cycle::factory()->create(['is_active' => true]);
    $group = ClassGroup::factory()->create(['cycle_id' => $cycle->id, 'grade' => '2º', 'section' => 'B']);

    Volt::actingAs($admin)
        ->test('students.index')
        ->call('openCreateModal')
        ->set('curp', 'TEST123456HDFRRR01')
        ->set('name', 'ALUMNO NUEVO')
        ->set('birthDate', '2010-05-15')
        ->set('turn', 'MATUTINO')
        ->set('classGroupId', $group->id)
        ->set('address', 'Calle Falsa 123')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showStudentModal', false);

    $student = Student::where('curp', 'TEST123456HDFRRR01')->first();
    expect($student)->not->toBeNull();
    expect($student->name)->toBe('ALUMNO NUEVO');
    expect($student->grade)->toBe('2º');
    expect($student->group_name)->toBe('B');

    // Verify PII was saved
    expect($student->pii)->not->toBeNull();
    expect($student->pii->address_encrypted)->toBe('Calle Falsa 123');

    // Verify Cycle Association
    expect($student->cycleAssociations()->where('cycle_id', $cycle->id)->exists())->toBeTrue();
});

test('admins can edit a student', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $cycle = Cycle::factory()->create(['is_active' => true]);
    $group1 = ClassGroup::factory()->create(['cycle_id' => $cycle->id, 'grade' => '1º', 'section' => 'A']);
    $group2 = ClassGroup::factory()->create(['cycle_id' => $cycle->id, 'grade' => '1º', 'section' => 'B']);

    $student = Student::factory()->create([
        'grade' => $group1->grade,
        'group_name' => $group1->section,
    ]);

    Volt::actingAs($admin)
        ->test('students.index')
        ->call('editStudent', $student->id)
        ->assertSet('name', $student->name)
        ->set('name', 'NOMBRE ACTUALIZADO')
        ->set('classGroupId', $group2->id)
        ->call('save')
        ->assertHasNoErrors();

    $student->refresh();
    expect($student->name)->toBe('NOMBRE ACTUALIZADO');
    expect($student->group_name)->toBe('B');
});

test('admins can associate parents to a student', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $cycle = Cycle::factory()->create(['is_active' => true]);
    $group = ClassGroup::factory()->create(['cycle_id' => $cycle->id]);
    $student = Student::factory()->create(['grade' => $group->grade, 'group_name' => $group->section]);
    $parent = User::factory()->create(['role' => 'PARENT', 'name' => 'PADRE PRUEBA']);

    Volt::actingAs($admin)
        ->test('students.index')
        ->call('editStudent', $student->id)
        ->set('parentSearch', 'PADRE')
        ->assertSee('PADRE PRUEBA')
        ->set('selectedParentId', $parent->id)
        ->set('parentRelationship', 'MADRE')
        ->call('addParent')
        ->assertHasNoErrors()
        ->assertSee('MADRE');

    expect($student->parents()->where('parent_id', $parent->id)->exists())->toBeTrue();
    expect($student->parents()->first()->pivot->relationship)->toBe('MADRE');

    // Test removal
    Volt::actingAs($admin)
        ->test('students.index')
        ->call('editStudent', $student->id)
        ->call('removeParent', $parent->id)
        ->assertHasNoErrors()
        ->assertDontSee('PADRE PRUEBA');

    expect($student->parents()->where('parent_id', $parent->id)->exists())->toBeFalse();
});
