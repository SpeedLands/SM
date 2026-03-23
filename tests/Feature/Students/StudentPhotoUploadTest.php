<?php

declare(strict_types=1);

use App\Models\ClassGroup;
use App\Models\Cycle;
use App\Models\Student;
use App\Models\StudentCycleAssociation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

test('admin can upload a photo when creating a student', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'ADMIN', 'status' => 'ACTIVE']);
    $cycle = Cycle::factory()->create(['is_active' => true]);
    $group = ClassGroup::factory()->create(['cycle_id' => $cycle->id]);

    $file = UploadedFile::fake()->image('avatar.jpg');

    $this->actingAs($admin);

    Volt::test('students.student-form')
        ->call('openCreate')
        ->set('name', 'TEST STUDENT')
        ->set('curp', 'TESTCURP1234567890')
        ->set('turn', 'MATUTINO')
        ->set('classGroupId', $group->id)
        ->set('photo', $file)
        ->call('save')
        ->assertHasNoErrors();

    $student = Student::where('name', 'TEST STUDENT')->first();
    expect($student->photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($student->photo_path);
});

test('admin can update a student photo', function () {
    Storage::fake('public');
    $admin = User::factory()->create(['role' => 'ADMIN', 'status' => 'ACTIVE']);
    $cycle = Cycle::factory()->create(['is_active' => true]);
    $group = ClassGroup::factory()->create(['cycle_id' => $cycle->id]);

    $student = Student::factory()->create([
        'name' => 'ORIGINAL NAME',
        'curp' => 'ORIGINALCURP123456',
        'turn' => 'MATUTINO',
        'photo_path' => 'old_path.jpg',
    ]);

    StudentCycleAssociation::create([
        'student_id' => $student->id,
        'cycle_id' => $cycle->id,
        'class_group_id' => $group->id,
        'status' => 'ACTIVE',
    ]);

    Storage::disk('public')->put('old_path.jpg', 'fake content');

    $file = UploadedFile::fake()->image('new_avatar.jpg');

    $this->actingAs($admin);

    $test = Volt::test('students.student-form')
        ->call('openEdit', $student->id)
        ->set('photo', $file)
        ->call('save');

    $test->assertHasNoErrors();

    $student->refresh();
    expect($student->photo_path)->not->toBe('old_path.jpg');
    expect($student->photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($student->photo_path);
    Storage::disk('public')->assertMissing('old_path.jpg');
});
