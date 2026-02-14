<?php

declare(strict_types=1);

use App\Models\CommunityService;
use App\Models\Cycle;
use App\Models\Infraction;
use App\Models\Report;
use App\Models\Student;
use App\Models\User;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'ADMIN', 'status' => 'ACTIVE']);
    $this->cycle = Cycle::factory()->create(['is_active' => true]);
});

test('admin can delete an infraction with no reports', function () {
    $infraction = Infraction::create([
        'description' => 'Test Infraction',
        'severity' => 'NORMAL',
    ]);

    $this->actingAs($this->admin);

    Volt::test('infractions.index')
        ->set('infractionToDelete', $infraction)
        ->call('delete')
        ->assertHasNoErrors();

    expect(Infraction::where('id', $infraction->id)->exists())->toBeFalse();
});

test('admin cannot delete an infraction with reports', function () {
    $infraction = Infraction::create([
        'description' => 'Used Infraction',
        'severity' => 'NORMAL',
    ]);

    $student = Student::factory()->create();

    Report::create([
        'cycle_id' => $this->cycle->id,
        'student_id' => $student->id,
        'teacher_id' => $this->admin->id,
        'infraction_id' => $infraction->id,
        'subject' => 'Test',
        'description' => 'Test',
        'date' => now(),
        'status' => 'PENDING_SIGNATURE',
    ]);

    $this->actingAs($this->admin);

    Volt::test('infractions.index')
        ->call('confirmDelete', $infraction)
        ->call('delete')
        ->assertDispatched('notify');

    $this->assertDatabaseHas('infractions', ['id' => $infraction->id]);
});

test('admin can delete a community service', function () {
    $student = Student::factory()->create();
    $service = CommunityService::create([
        'cycle_id' => $this->cycle->id,
        'student_id' => $student->id,
        'assigned_by_id' => $this->admin->id,
        'activity' => 'Test Activity',
        'description' => 'Test Description',
        'scheduled_date' => now(),
        'status' => 'PENDING',
    ]);

    $this->actingAs($this->admin);

    Volt::test('community-services.index')
        ->call('confirmDelete', $service->id)
        ->assertSet('showDeleteModal', true)
        ->assertSet('idToDelete', $service->id)
        ->call('delete')
        ->assertSet('showDeleteModal', false)
        ->assertHasNoErrors();

    expect(CommunityService::where('id', $service->id)->exists())->toBeFalse();
});

test('admin can delete a report using confirmation modal', function () {
    $student = Student::factory()->create();
    $infraction = Infraction::create(['description' => 'Test', 'severity' => 'NORMAL']);
    $report = Report::create([
        'cycle_id' => $this->cycle->id,
        'student_id' => $student->id,
        'teacher_id' => $this->admin->id,
        'infraction_id' => $infraction->id,
        'subject' => 'Test',
        'description' => 'Test',
        'date' => now(),
        'status' => 'PENDING_SIGNATURE',
    ]);

    $this->actingAs($this->admin);

    Volt::test('reports.index')
        ->call('confirmDelete', $report->id)
        ->assertSet('showDeleteModal', true)
        ->assertSet('reportIdToDelete', $report->id)
        ->call('deleteReport')
        ->assertSet('showDeleteModal', false)
        ->assertHasNoErrors();

    expect(Report::where('id', $report->id)->exists())->toBeFalse();
});
