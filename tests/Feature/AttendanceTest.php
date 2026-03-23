<?php

use App\Models\Attendance;
use App\Models\ClassGroup;
use App\Models\Cycle;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentCycleAssociation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'ADMIN']);
    $this->cycle = Cycle::factory()->create(['is_active' => true]);
    $this->student = Student::factory()->create([
        'curp' => 'TESTCURP1234567890',
        'turn' => 'MATUTINO',
    ]);

    // Seed known thresholds: entry at 08:00, grace 10 min → retardo after 08:10
    Setting::set('attendance.matutino_entry_time', '08:00');
    Setting::set('attendance.vespertino_entry_time', '14:00');
    Setting::set('attendance.grace_minutes', '10');

    // Ensure route exists for component testing
    if (! Route::has('attendance.scanner')) {
        Route::get('/asistencia/escanear', function () {})->name('attendance.scanner');
    }
});

test('attendance scanner marks a student as present', function () {
    // Travel to 8:00 AM
    Carbon::setTestNow(Carbon::today()->setTime(8, 0));

    Volt::actingAs($this->admin)
        ->test('attendance.scanner')
        ->set('curp', 'TESTCURP1234567890')
        ->call('processScan')
        ->assertSet('statusColor', 'green')
        ->assertSet('statusMessage', 'ASISTENCIA Registrada');

    expect(Attendance::where('student_id', $this->student->id)->exists())->toBeTrue();
    $attendance = Attendance::where('student_id', $this->student->id)->first();
    expect($attendance->status)->toBe('PRESENTE');
    expect($attendance->entry_time->format('H:i'))->toBe('08:00');
});

test('attendance scanner marks a student as retardo after threshold', function () {
    // Travel to 8:15 AM (Threshold is 8:10)
    Carbon::setTestNow(Carbon::today()->setTime(8, 15));

    Volt::actingAs($this->admin)
        ->test('attendance.scanner')
        ->set('curp', 'TESTCURP1234567890')
        ->call('processScan')
        ->assertSet('statusColor', 'amber')
        ->assertSet('statusMessage', 'RETARDO Registrado');

    $attendance = Attendance::where('student_id', $this->student->id)->first();
    expect($attendance->status)->toBe('RETARDO');
    expect($attendance->entry_time->format('H:i'))->toBe('08:15');
});

test('scanner prevents duplicate attendance for the same day', function () {
    Carbon::setTestNow(Carbon::today()->setTime(8, 0));

    Attendance::create([
        'student_id' => $this->student->id,
        'date' => now()->toDateString(),
        'status' => 'PRESENTE',
        'entry_time' => '07:55',
    ]);

    Volt::actingAs($this->admin)
        ->test('attendance.scanner')
        ->set('curp', 'TESTCURP1234567890')
        ->call('processScan')
        ->assertSet('statusColor', 'amber')
        ->assertSet('statusMessage', 'Ya se registró asistencia hoy');

    expect(Attendance::where('student_id', $this->student->id)->count())->toBe(1);
});

test('admin can manually change attendance status in index', function () {
    $group = ClassGroup::factory()->create(['cycle_id' => $this->cycle->id]);

    // Associate student to group
    StudentCycleAssociation::create([
        'student_id' => $this->student->id,
        'cycle_id' => $this->cycle->id,
        'class_group_id' => $group->id,
    ]);

    Volt::actingAs($this->admin)
        ->test('attendance.index')
        ->set('cycle_id', $this->cycle->id)
        ->set('date', date('Y-m-d'))
        ->set('grade', $group->grade)
        ->set('group_id', $group->id)
        ->call('setStatus', $this->student->id, 'JUSTIFICADO');

    $attendance = Attendance::where('student_id', $this->student->id)->first();
    expect($attendance->status)->toBe('JUSTIFICADO');
});

test('admin can mark all students in a group as present', function () {
    $group = ClassGroup::factory()->create(['cycle_id' => $this->cycle->id]);
    $student2 = Student::factory()->create();

    StudentCycleAssociation::create([
        'student_id' => $this->student->id,
        'cycle_id' => $this->cycle->id,
        'class_group_id' => $group->id,
    ]);

    StudentCycleAssociation::create([
        'student_id' => $student2->id,
        'cycle_id' => $this->cycle->id,
        'class_group_id' => $group->id,
    ]);

    Volt::actingAs($this->admin)
        ->test('attendance.index')
        ->set('cycle_id', $this->cycle->id)
        ->set('date', date('Y-m-d'))
        ->set('grade', $group->grade)
        ->set('group_id', $group->id)
        ->call('markAllPresent')
        ->assertDispatched('notify');

    expect(Attendance::where('student_id', $this->student->id)->where('status', 'PRESENTE')->exists())->toBeTrue();
    expect(Attendance::where('student_id', $student2->id)->where('status', 'PRESENTE')->exists())->toBeTrue();
});
