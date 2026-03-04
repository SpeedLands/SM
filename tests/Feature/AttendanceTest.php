<?php

use App\Models\Attendance;
use App\Models\ClassGroup;
use App\Models\Cycle;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;
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
    if (! Illuminate\Support\Facades\Route::has('attendance.scanner')) {
        Illuminate\Support\Facades\Route::get('/asistencia/escanear', function () {})->name('attendance.scanner');
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
        'date' => Carbon::today(),
        'status' => 'PRESENTE',
        'entry_time' => '07:55',
    ]);

    Volt::actingAs($this->admin)
        ->test('attendance.scanner')
        ->set('curp', 'TESTCURP1234567890')
        ->call('processScan')
        ->assertSet('statusColor', 'amber')
        ->assertSee('Ya se registró asistencia hoy');

    expect(Attendance::where('student_id', $this->student->id)->count())->toBe(1);
});

test('admin can manually change attendance status in index', function () {
    $group = ClassGroup::factory()->create(['cycle_id' => $this->cycle->id]);

    // Associate student to group
    \App\Models\StudentCycleAssociation::create([
        'student_id' => $this->student->id,
        'cycle_id' => $this->cycle->id,
        'class_group_id' => $group->id,
    ]);

    Volt::actingAs($this->admin)
        ->test('attendance.index')
        ->set('date', date('Y-m-d'))
        ->set('grade', $group->grade)
        ->set('group_id', $group->id)
        ->call('setStatus', $this->student->id, 'JUSTIFICADO');

    $attendance = Attendance::where('student_id', $this->student->id)->first();
    expect($attendance->status)->toBe('JUSTIFICADO');
});
