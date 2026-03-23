<?php

use App\Models\ClassGroup;
use App\Models\Cycle;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'ADMIN']);
    $this->cycle = Cycle::factory()->create(['is_active' => true]);
    $this->group = ClassGroup::factory()->create(['cycle_id' => $this->cycle->id]);
});

test('it can save a student with a CURP', function () {
    $curp = 'ABCD010101HGRRRR01';

    Volt::actingAs($this->admin)
        ->test('students.student-form')
        ->set('name', 'TEST STUDENT')
        ->set('curp', $curp)
        ->set('turn', 'MATUTINO')
        ->set('classGroupId', (string) $this->group->id)
        ->call('save')
        ->assertHasNoErrors();

    $student = Student::where('name', 'TEST STUDENT')->first();
    expect($student->curp)->toBe($curp);
});

test('it can save a foreign student with a 16-character CURP', function () {
    $curp = 'ABCD010101HGRRR0'; // 16 characters

    Volt::actingAs($this->admin)
        ->test('students.student-form')
        ->set('name', 'FOREIGN STUDENT')
        ->set('curp', $curp)
        ->set('turn', 'MATUTINO')
        ->set('classGroupId', (string) $this->group->id)
        ->call('save')
        ->assertHasNoErrors();

    $student = Student::where('name', 'FOREIGN STUDENT')->first();
    expect($student->curp)->toBe($curp);
});

test('it can save a student without a CURP', function () {
    Volt::actingAs($this->admin)
        ->test('students.student-form')
        ->set('name', 'TEST STUDENT NO CURP')
        ->set('curp', '')
        ->set('turn', 'MATUTINO')
        ->set('classGroupId', (string) $this->group->id)
        ->call('save')
        ->assertHasNoErrors();

    $student = Student::where('name', 'TEST STUDENT NO CURP')->first();
    expect($student->curp)->toBeNull();
});

test('it validates CURP length if provided', function () {
    Volt::actingAs($this->admin)
        ->test('students.student-form')
        ->set('name', 'TEST STUDENT')
        ->set('curp', 'TOO_SHORT')
        ->set('turn', 'MATUTINO')
        ->set('classGroupId', (string) $this->group->id)
        ->call('save')
        ->assertHasErrors(['curp']);
});
