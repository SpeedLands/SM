<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\User;
use Livewire\Volt\Volt;

test('it nullifies groupCycle after deleting the active groupCycle', function () {
    $admin = User::factory()->create(['role' => 'ADMIN', 'status' => 'ACTIVE']);
    $cycleA = Cycle::factory()->create(['name' => '2024-2025', 'is_active' => false]);
    $cycleB = Cycle::factory()->create(['name' => '2025-2026', 'is_active' => false]);

    $this->actingAs($admin);

    $component = Volt::test('cycles.index')
        // 1. Open groups for Cycle A
        ->call('openGroupsModal', $cycleA->id)
        ->assertSet('groupCycle.id', $cycleA->id)
        // 2. Mock closing or just proceed to delete
        ->call('confirmDelete', $cycleA->id)
        ->call('delete')
        ->assertSet('cycleToDelete', null)
        ->assertSet('groupCycle', null); // This is what my fix added

    // If it's null, the next action shouldn't 404
    $component->call('edit', $cycleB->id)
        ->assertSet('editing.id', $cycleB->id);
});

test('it nullifies editing after deleting the cycle being edited', function () {
    $admin = User::factory()->create(['role' => 'ADMIN', 'status' => 'ACTIVE']);
    $cycleA = Cycle::factory()->create(['name' => '2024-2025', 'is_active' => false]);

    $this->actingAs($admin);

    Volt::test('cycles.index')
        ->call('edit', $cycleA->id)
        ->assertSet('editing.id', $cycleA->id)
        ->call('confirmDelete', $cycleA->id)
        ->call('delete')
        ->assertSet('editing', null)
        ->assertSet('name', '')
        ->assertSet('start_date', '')
        ->assertSet('end_date', '');
});
