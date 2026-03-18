<?php

use App\Models\ClassGroup;
use App\Models\Cycle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

it('cannot create a duplicate group in the same cycle', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $cycle = Cycle::factory()->create(['is_active' => true]);

    ClassGroup::create([
        'id' => (string) Str::uuid(),
        'cycle_id' => $cycle->id,
        'grade' => '1º',
        'section' => 'A',
    ]);

    Volt::actingAs($admin)
        ->test('cycles.index')
        ->set('groupCycle', $cycle)
        ->set('grade', '1º')
        ->set('section', 'A')
        ->call('saveGroup')
        ->assertHasErrors(['grade' => 'unique']);
});

it('can update a group without changing grade/section', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $cycle = Cycle::factory()->create(['is_active' => true]);

    $group = ClassGroup::create([
        'id' => (string) Str::uuid(),
        'cycle_id' => $cycle->id,
        'grade' => '1º',
        'section' => 'A',
    ]);

    Volt::actingAs($admin)
        ->test('cycles.index')
        ->set('groupCycle', $cycle)
        ->set('editingGroup', $group)
        ->set('grade', '1º')
        ->set('section', 'A')
        ->call('saveGroup')
        ->assertHasNoErrors();
});

it('cannot update a group to a duplicate grade/section', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $cycle = Cycle::factory()->create(['is_active' => true]);

    ClassGroup::create([
        'id' => (string) Str::uuid(),
        'cycle_id' => $cycle->id,
        'grade' => '1º',
        'section' => 'A',
    ]);

    $group2 = ClassGroup::create([
        'id' => (string) Str::uuid(),
        'cycle_id' => $cycle->id,
        'grade' => '2º',
        'section' => 'B',
    ]);

    Volt::actingAs($admin)
        ->test('cycles.index')
        ->set('groupCycle', $cycle)
        ->set('editingGroup', $group2)
        ->set('grade', '1º')
        ->set('section', 'A')
        ->call('saveGroup')
        ->assertHasErrors(['grade' => 'unique']);
});
