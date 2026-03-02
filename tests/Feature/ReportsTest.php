<?php

use App\Models\Cycle;
use App\Models\Infraction;
use App\Models\Report;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Volt\Volt;

test('admins and teachers can create disciplinary reports', function () {
    Carbon::setTestNow(Carbon::create(2026, 2, 20, 10, 0, 0)); // A Friday
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $student = Student::factory()->create();
    $cycle = Cycle::factory()->create(['is_active' => true]);
    $infraction = Infraction::create(['description' => 'Falta de respeto', 'severity' => 'NORMAL']);

    Volt::actingAs($admin)
        ->test('reports.index')
        ->set('studentSearch', substr($student->name, 0, 5))
        ->assertSee($student->name)
        ->call('selectStudent', $student->id)
        ->set('infractionId', $infraction->id)
        ->set('description', 'Test description for the report')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Test description for the report');

    expect(Report::count())->toBe(1);
    $report = Report::first();
    expect($report->student_id)->toBe($student->id);
    expect($report->teacher_id)->toBe($admin->id);
    expect($report->cycle_id)->toBe($cycle->id);
    Carbon::setTestNow(); // Reset
});

test('community service suggestion is triggered every 3 reports', function () {
    Carbon::setTestNow(Carbon::create(2026, 2, 20, 10, 0, 0)); // A Friday
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $student = Student::factory()->create();
    $cycle = Cycle::factory()->create(['is_active' => true]);
    $infraction = Infraction::create(['description' => 'Falta', 'severity' => 'NORMAL']);

    // Create 2 existing reports
    Report::factory()->count(2)->create([
        'student_id' => $student->id,
        'cycle_id' => $cycle->id,
        'teacher_id' => $admin->id,
        'infraction_id' => $infraction->id,
    ]);

    Volt::actingAs($admin)
        ->test('reports.index')
        ->call('selectStudent', $student->id)
        ->set('infractionId', $infraction->id)
        ->set('description', 'Third report')
        ->call('save')
        ->assertDispatched('community-service-suggested', [
            'student_name' => $student->name,
            'count' => 3,
        ]);
    Carbon::setTestNow(); // Reset
});

test('reports can be filtered by severity and status', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $student = Student::factory()->create();
    $cycle = Cycle::factory()->create(['is_active' => true]);

    $infractionGrave = Infraction::create(['description' => 'Grave issue', 'severity' => 'GRAVE']);
    $infractionNormal = Infraction::create(['description' => 'Normal issue', 'severity' => 'NORMAL']);

    Report::create([
        'student_id' => $student->id,
        'cycle_id' => $cycle->id,
        'teacher_id' => $admin->id,
        'infraction_id' => $infractionGrave->id,
        'date' => now(),
        'status' => 'PENDING_SIGNATURE',
        'description' => 'D-GRAVE',
    ]);

    Report::create([
        'student_id' => $student->id,
        'cycle_id' => $cycle->id,
        'teacher_id' => $admin->id,
        'infraction_id' => $infractionNormal->id,
        'date' => now(),
        'status' => 'SIGNED',
        'description' => 'D-NORMAL',
    ]);

    Volt::actingAs($admin)
        ->test('reports.index')
        ->set('severity', 'GRAVE')
        ->assertSee('D-GRAVE')
        ->assertDontSee('D-NORMAL')
        ->set('severity', '')
        ->set('status', 'SIGNED')
        ->assertSee('D-NORMAL')
        ->assertDontSee('D-GRAVE');
});

test('can register a report on a Sunday for a previous Monday', function () {
    // Today is Sunday, March 1st, 2026
    Carbon::setTestNow(Carbon::create(2026, 3, 1, 12, 0, 0));

    $admin = User::factory()->create(['role' => 'ADMIN']);
    $student = Student::factory()->create();
    $cycle = Cycle::factory()->create(['is_active' => true]);
    $infraction = Infraction::create(['description' => 'Falta', 'severity' => 'NORMAL']);

    Volt::actingAs($admin)
        ->test('reports.index')
        ->call('selectStudent', $student->id)
        ->set('infractionId', $infraction->id)
        ->set('reportDate', '2026-02-23') // A Monday
        ->set('reportTime', '09:00')
        ->set('description', 'Reported on Sunday for Monday')
        ->call('save')
        ->assertHasNoErrors();

    expect(Report::where('description', 'Reported on Sunday for Monday')->exists())->toBeTrue();
    Carbon::setTestNow();
});

test('cannot register a report for a Saturday even if today is a weekday', function () {
    // Today is Monday, March 2nd, 2026
    Carbon::setTestNow(Carbon::create(2026, 3, 2, 12, 0, 0));

    $admin = User::factory()->create(['role' => 'ADMIN']);
    $student = Student::factory()->create();
    Cycle::factory()->create(['is_active' => true]);
    $infraction = Infraction::create(['description' => 'Falta', 'severity' => 'NORMAL']);

    Volt::actingAs($admin)
        ->test('reports.index')
        ->call('selectStudent', $student->id)
        ->set('infractionId', $infraction->id)
        ->set('reportDate', '2026-02-28') // A Saturday
        ->set('reportTime', '09:00')
        ->set('description', 'Report for Saturday')
        ->call('save')
        ->assertHasErrors(['reportDate']);

    Carbon::setTestNow();
});

test('can register a report for tomorrow if tomorrow is a weekday', function () {
    // Today is Monday, March 2nd, 2026
    Carbon::setTestNow(Carbon::create(2026, 3, 2, 12, 0, 0));

    $admin = User::factory()->create(['role' => 'ADMIN']);
    $student = Student::factory()->create();
    Cycle::factory()->create(['is_active' => true]);
    $infraction = Infraction::create(['description' => 'Falta', 'severity' => 'NORMAL']);

    Volt::actingAs($admin)
        ->test('reports.index')
        ->call('selectStudent', $student->id)
        ->set('infractionId', $infraction->id)
        ->set('reportDate', '2026-03-03') // Tuesday (Tomorrow)
        ->set('reportTime', '09:00')
        ->set('description', 'Report for tomorrow')
        ->call('save')
        ->assertHasNoErrors();

    expect(Report::where('description', 'Report for tomorrow')->exists())->toBeTrue();
    Carbon::setTestNow();
});
