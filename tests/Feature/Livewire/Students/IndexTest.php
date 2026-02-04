<?php

use App\Models\User;
use Livewire\Volt\Volt;

it('can render', function () {
    $this->actingAs(User::factory()->admin()->create());

    $component = Volt::test('students.index');

    $component->assertSee('');
});
