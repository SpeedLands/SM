<?php

use App\Models\User;
use App\Models\Cycle;
use App\Models\Student;
use App\Models\CommunityService;
use App\Models\Report;
use App\Models\Infraction;
use Livewire\Volt\Volt;

test('admins can assign community service', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $student = Student::factory()->create();
    $cycle = Cycle::factory()->create(['is_active' => true]);

    Volt::actingAs($admin)
        ->test('community-services.index')
        ->set('studentSearch', substr($student->name, 0, 5))
        ->assertSee($student->name)
        ->call('selectStudent', $student->id)
        ->set('activity', 'Limpieza de patio')
        ->set('scheduledDate', now()->addDay()->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Limpieza de patio');

    expect(CommunityService::count())->toBe(1);
    $service = CommunityService::first();
    expect($service->student_id)->toBe($student->id);
    expect($service->assigned_by_id)->toBe($admin->id);
});

test('status transitions work correctly', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $student = Student::factory()->create();
    $cycle = Cycle::factory()->create(['is_active' => true]);
    $service = CommunityService::create([
        'cycle_id' => $cycle->id,
        'student_id' => $student->id,
        'assigned_by_id' => $admin->id,
        'activity' => 'Test activity',
        'scheduled_date' => now()->addDay(),
        'status' => 'PENDING'
    ]);

    Volt::actingAs($admin)
        ->test('community-services.index')
        ->assertSee('Test activity')
        ->call('updateStatus', $service->id, 'COMPLETED')
        ->assertHasNoErrors();

    $service->refresh();
    expect($service->status)->toBe('COMPLETED');
    expect($service->completed_at)->not->toBeNull();
    expect($service->authority_signature_id)->toBe($admin->id);
});

test('it suggests students with 3 reports', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $student = Student::factory()->create();
    $cycle = Cycle::factory()->create(['is_active' => true]);
    $infraction = Infraction::create(['description' => 'Falta', 'severity' => 'NORMAL']);

    // Create 3 reports for the student
    Report::factory()->count(3)->create([
        'student_id' => $student->id,
        'cycle_id' => $cycle->id,
        'teacher_id' => $admin->id,
        'infraction_id' => $infraction->id,
    ]);

    Volt::actingAs($admin)
        ->test('community-services.index')
        ->assertSee('Sugerencias de Asignación')
        ->assertSee($student->name);

    // After assigning one service, it should no longer suggest (since 3/3 = 1)
    CommunityService::create([
        'cycle_id' => $cycle->id,
        'student_id' => $student->id,
        'assigned_by_id' => $admin->id,
        'activity' => 'Reparación',
        'scheduled_date' => now()->addDay(),
        'status' => 'PENDING'
    ]);

    Volt::actingAs($admin)
        ->test('community-services.index')
        ->assertDontSee('Sugerencias de Asignación');
});
