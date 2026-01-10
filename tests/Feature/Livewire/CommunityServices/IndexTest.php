<?php

use Livewire\Volt\Volt;

it('can render', function () {
    $component = Volt::test('community-services.index');

    $component->assertSee('');
});
