<?php

use App\Models\ClassGroup;
use App\Models\Cycle;
use App\Models\User;
use Livewire\Volt\Volt;

test('admins can manage class groups for a cycle', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $teacher = User::factory()->create(['role' => 'TEACHER']);
    $cycle = Cycle::factory()->create();

    Volt::actingAs($admin)
        ->test('cycles.index')
        ->call('openGroupsModal', $cycle->id)
        ->set('grade', '1º')
        ->set('section', 'A')
        ->set('tutorId', $teacher->id)
        ->call('saveGroup')
        ->assertHasNoErrors()
        ->assertSee('1º A')
        ->assertSee($teacher->name);

    expect(ClassGroup::where('cycle_id', $cycle->id)->count())->toBe(1);

    $group = ClassGroup::where('cycle_id', $cycle->id)->first();
    expect($group->tutor_teacher_id)->toBe($teacher->id);

    // Test deletion
    Volt::actingAs($admin)
        ->test('cycles.index')
        ->call('openGroupsModal', $cycle->id)
        ->call('deleteGroup', $group->id)
        ->assertHasNoErrors()
        ->assertDontSee('1º A');

    expect(ClassGroup::where('cycle_id', $cycle->id)->count())->toBe(0);
});

test('enrollment button is disabled if no groups exist', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $cycle = Cycle::factory()->create(['is_active' => true]);
    // No groups created

    Volt::actingAs($admin)
        ->test('students.index')
        ->assertSee('Faltan Grupos Académicos')
        ->assertSee('disabled', false);
});
