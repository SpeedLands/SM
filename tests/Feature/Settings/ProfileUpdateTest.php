<?php

use App\Models\User;
use Livewire\Volt\Volt;

test('profile page is displayed for admin', function () {
    $this->actingAs($user = User::factory()->create(['role' => 'ADMIN']));

    $this->get(route('profile.edit'))->assertOk();
});

test('profile page is forbidden for non-admin', function () {
    $this->actingAs($user = User::factory()->create(['role' => 'TEACHER']));

    $this->get(route('profile.edit'))->assertForbidden();
});

test('profile information can be updated by admin', function () {
    $user = User::factory()->create(['role' => 'ADMIN']);

    $this->actingAs($user);

    $response = Volt::test('settings.profile')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('profile information cannot be updated by non-admin', function () {
    $user = User::factory()->create(['role' => 'TEACHER']);

    $this->actingAs($user);

    Volt::test('settings.profile')
        ->set('name', 'Test User')
        ->call('updateProfileInformation')
        ->assertForbidden();
});

test('email verification status is unchanged when email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Volt::test('settings.profile')
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Volt::test('settings.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser');

    $response
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($user->fresh())->toBeNull();
    expect(auth()->check())->toBeFalse();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Volt::test('settings.delete-user-form')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $response->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});