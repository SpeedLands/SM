<?php

use App\Models\User;
use App\Models\Cycle;
use App\Models\Student;
use App\Models\Infraction;
use App\Models\Report;
use Livewire\Volt\Volt;

test('admins and teachers can create disciplinary reports', function () {
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
});

test('community service suggestion is triggered every 3 reports', function () {
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
            'count' => 3
        ]);
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
        'description' => 'D-GRAVE'
    ]);

    Report::create([
        'student_id' => $student->id,
        'cycle_id' => $cycle->id,
        'teacher_id' => $admin->id,
        'infraction_id' => $infractionNormal->id,
        'date' => now(),
        'status' => 'SIGNED',
        'description' => 'D-NORMAL'
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
