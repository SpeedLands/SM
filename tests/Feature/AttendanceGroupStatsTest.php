<?php

use App\Models\Attendance;
use App\Models\ClassGroup;
use App\Models\Cycle;
use App\Models\Student;
use App\Models\StudentCycleAssociation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'ADMIN']);
    $this->cycle = Cycle::factory()->create(['is_active' => true]);
    $this->group = ClassGroup::factory()->create(['cycle_id' => $this->cycle->id, 'grade' => '1º', 'section' => 'A']);

    $this->student1 = Student::factory()->create(['name' => 'Student One']);
    $this->student2 = Student::factory()->create(['name' => 'Student Two']);

    StudentCycleAssociation::create([
        'student_id' => $this->student1->id,
        'cycle_id' => $this->cycle->id,
        'class_group_id' => $this->group->id,
    ]);

    StudentCycleAssociation::create([
        'student_id' => $this->student2->id,
        'cycle_id' => $this->cycle->id,
        'class_group_id' => $this->group->id,
    ]);
});

test('attendance group stats correctly aggregates data', function () {
    $startDate = Carbon::now()->startOfMonth()->toDateString();
    $endDate = Carbon::now()->toDateString();

    // Student 1: 1 FALTA, 1 RETARDO
    Attendance::create(['student_id' => $this->student1->id, 'date' => $startDate, 'status' => 'FALTA']);
    Attendance::create(['student_id' => $this->student1->id, 'date' => Carbon::parse($startDate)->addDay()->toDateString(), 'status' => 'RETARDO']);

    // Student 2: 1 JUSTIFICADO, 1 TRABAJO_EN_CASA
    Attendance::create(['student_id' => $this->student2->id, 'date' => $startDate, 'status' => 'JUSTIFICADO']);
    Attendance::create(['student_id' => $this->student2->id, 'date' => Carbon::parse($startDate)->addDay()->toDateString(), 'status' => 'TRABAJO_EN_CASA']);

    Volt::actingAs($this->admin)
        ->test('attendance.group-stats')
        ->set('cycle_id', $this->cycle->id)
        ->set('grade', '1º')
        ->set('group_id', $this->group->id)
        ->set('start_date', $startDate)
        ->set('end_date', $endDate)
        ->assertSee('Student One')
        ->assertSee('Student Two')
        // Check aggregate stats in cards
        ->assertSee('Faltas Injust.')
        ->assertSee('1') // FALTA count
        ->assertSee('Retardos')
        ->assertSee('1') // RETARDO count
        ->assertSee('Faltas Justif.')
        ->assertSee('1') // JUSTIFICADO count
        ->assertSee('Trabajo en Casa')
        ->assertSee('1'); // TRABAJO_EN_CASA count
});

test('attendance group stats filters by date range', function () {
    $dateOutside = Carbon::now()->subMonth()->toDateString();
    $dateInside = Carbon::now()->toDateString();

    Attendance::create(['student_id' => $this->student1->id, 'date' => $dateOutside, 'status' => 'FALTA']);
    Attendance::create(['student_id' => $this->student1->id, 'date' => $dateInside, 'status' => 'RETARDO']);

    Volt::actingAs($this->admin)
        ->test('attendance.group-stats')
        ->set('cycle_id', $this->cycle->id)
        ->set('grade', '1º')
        ->set('group_id', $this->group->id)
        ->set('start_date', Carbon::now()->startOfMonth()->toDateString())
        ->set('end_date', Carbon::now()->toDateString())
        ->assertSee('0') // FALTA is outside range
        ->assertSee('1'); // RETARDO is inside range
});
