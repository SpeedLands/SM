<?php

use App\Models\Regulation;
use App\Models\User;
use Livewire\Volt\Volt;

beforeEach(function () {
    // Create/ensure the regulation exists
    Regulation::firstOrCreate(
        ['id' => 1],
        [
            'title' => 'Test Regulation',
            'content' => '<p>Test Content</p>',
            'last_updated' => now(),
        ]
    );
});

test('guest cannot see regulations', function () {
    $this->get(route('regulations.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can see regulations', function () {
    $user = User::factory()->create(['role' => 'PARENT']);

    $this->actingAs($user)
        ->get(route('regulations.index'))
        ->assertStatus(200)
        ->assertSee('Test Regulation')
        ->assertSee('Test Content');
});

test('admin can see edit button', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);

    $this->actingAs($admin)
        ->get(route('regulations.index'))
        ->assertStatus(200)
        ->assertSee('Editar Reglamento');
});

test('parent cannot see edit button', function () {
    $parent = User::factory()->create(['role' => 'PARENT']);

    $this->actingAs($parent)
        ->get(route('regulations.index'))
        ->assertStatus(200)
        ->assertDontSee('Editar Reglamento');
});

test('admin can update regulations via volt component', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);

    Volt::actingAs($admin)
        ->test('regulations.index')
        ->set('title', 'Updated Regulation Title')
        ->set('content', '<p>Updated Content</p>')
        ->call('save')
        ->assertSet('isEditing', false);

    $this->assertDatabaseHas('regulations', [
        'id' => 1,
        'title' => 'Updated Regulation Title',
        'content' => '<p>Updated Content</p>',
    ]);
});

test('parent cannot update regulations via volt component', function () {
    $parent = User::factory()->create(['role' => 'PARENT']);

    Volt::actingAs($parent)
        ->test('regulations.index')
        ->set('title', 'Malicious Title')
        ->call('save');

    $this->assertDatabaseMissing('regulations', [
        'title' => 'Malicious Title',
    ]);
});
