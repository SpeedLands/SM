<?php

use App\Models\Citation;
use App\Models\CommunityService;
use App\Models\Cycle;
use App\Models\Infraction;
use App\Models\Notice;
use App\Models\Report;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->cycle = Cycle::factory()->create(['is_active' => true]);
    $this->parent = User::factory()->create(['role' => 'PARENT']);
    $this->student = Student::factory()->create();
    $this->parent->students()->attach($this->student->id, ['relationship' => 'PADRE']);
});

test('parent has zero notifications when nothing is pending', function () {
    expect($this->parent->getPendingNotificationsCount())->toBe(0);
});

test('parent sees pending notices count', function () {
    Notice::create([
        'cycle_id' => $this->cycle->id,
        'author_id' => User::factory()->create(['role' => 'ADMIN'])->id,
        'title' => 'Test Notice',
        'content' => 'Content',
        'type' => 'GENERAL',
        'target_audience' => 'PARENTS',
        'date' => now(),
    ]);

    expect($this->parent->getUnsignedNoticesCount())->toBe(1);
    expect($this->parent->getPendingNotificationsCount())->toBe(1);
});

test('parent sees pending reports count', function () {
    $infraction = Infraction::create(['description' => 'Test', 'severity' => 'NORMAL']);

    Report::create([
        'cycle_id' => $this->cycle->id,
        'student_id' => $this->student->id,
        'teacher_id' => User::factory()->create(['role' => 'TEACHER'])->id,
        'infraction_id' => $infraction->id,
        'description' => 'Disruptive',
        'date' => now(),
        'status' => 'PENDING_SIGNATURE',
    ]);

    expect($this->parent->getUnsignedReportsCount())->toBe(1);
    expect($this->parent->getPendingNotificationsCount())->toBe(1);
});

test('parent sees pending community services count', function () {
    CommunityService::create([
        'cycle_id' => $this->cycle->id,
        'student_id' => $this->student->id,
        'assigned_by_id' => User::factory()->create(['role' => 'ADMIN'])->id,
        'activity' => 'Cleaning',
        'scheduled_date' => now()->addDay(),
        'status' => 'PENDING',
        'parent_signature' => false,
    ]);

    expect($this->parent->getUnsignedCommunityServicesCount())->toBe(1);
    expect($this->parent->getPendingNotificationsCount())->toBe(1);
});

test('parent sees pending citations count', function () {
    Citation::create([
        'cycle_id' => $this->cycle->id,
        'student_id' => $this->student->id,
        'teacher_id' => User::factory()->create(['role' => 'TEACHER'])->id,
        'reason' => 'Meeting',
        'citation_date' => now()->addDay(),
        'status' => 'PENDING',
        'parent_signature' => false,
    ]);

    expect($this->parent->getUnsignedCitationsCount())->toBe(1);
    expect($this->parent->getPendingNotificationsCount())->toBe(1);
});

test('total count combines all pending items', function () {
    // Notice
    Notice::create([
        'cycle_id' => $this->cycle->id,
        'author_id' => User::factory()->create(['role' => 'ADMIN'])->id,
        'title' => 'Notice',
        'content' => 'Content',
        'type' => 'GENERAL',
        'target_audience' => 'ALL',
        'date' => now(),
    ]);

    // Report
    $infraction = Infraction::create(['description' => 'Test', 'severity' => 'NORMAL']);
    Report::create([
        'cycle_id' => $this->cycle->id,
        'student_id' => $this->student->id,
        'teacher_id' => User::factory()->create(['role' => 'TEACHER'])->id,
        'infraction_id' => $infraction->id,
        'description' => 'Test',
        'date' => now(),
        'status' => 'PENDING_SIGNATURE',
    ]);

    expect($this->parent->getPendingNotificationsCount())->toBe(2);
});

test('parent can filter pending counts by student', function () {
    $student2 = Student::factory()->create(['grade' => '2º']);
    $this->parent->students()->attach($student2->id, ['relationship' => 'PADRE']);

    // Notice for student 1 MUST target student 1
    Notice::create([
        'cycle_id' => $this->cycle->id,
        'author_id' => User::factory()->create(['role' => 'ADMIN'])->id,
        'title' => 'Notice 1',
        'content' => 'Content',
        'type' => 'GENERAL',
        'target_audience' => 'PARENTS',
        'target_grades' => [$this->student->grade],
        'date' => now(),
    ]);

    // Report for student 2 MUST be for student 2
    $infraction = Infraction::create(['description' => 'Test', 'severity' => 'NORMAL']);
    Report::create([
        'cycle_id' => $this->cycle->id,
        'student_id' => $student2->id,
        'teacher_id' => User::factory()->create(['role' => 'TEACHER'])->id,
        'infraction_id' => $infraction->id,
        'description' => 'Test',
        'date' => now(),
        'status' => 'PENDING_SIGNATURE',
    ]);

    // Totals
    expect($this->parent->getPendingNotificationsCount())->toBe(2);

    // Filtered by student 1
    expect($this->parent->getPendingNotificationsCount($this->student->id))->toBe(1);

    // Filtered by student 2
    expect($this->parent->getPendingNotificationsCount($student2->id))->toBe(1);
});
