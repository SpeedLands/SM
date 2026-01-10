<?php

use App\Models\User;
use App\Models\Cycle;
use App\Models\ClassGroup;
use App\Models\ExamSchedule;
use App\Models\Student;
use Livewire\Volt\Volt;

test('admins can schedule exams', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $cycle = Cycle::factory()->create(['is_active' => true]);
    $group = ClassGroup::factory()->create([
        'cycle_id' => $cycle->id,
        'grade' => '1',
        'section' => 'A'
    ]);

    Volt::actingAs($admin)
        ->test('exams.index')
        ->set('subject', 'Matemáticas')
        ->set('grade', '1')
        ->set('groupName', 'A')
        ->set('period', '1')
        ->set('examDate', now()->next('Monday')->format('Y-m-d'))
        ->call('saveExam')
        ->assertHasNoErrors();

    expect(ExamSchedule::where('subject', 'Matemáticas')->exists())->toBeTrue();
});

test('parents can view exams for their children', function () {
    $parent = User::factory()->create(['role' => 'PARENT']);
    $student = Student::factory()->create(['grade' => '2', 'group_name' => 'B']);
    $cycle = Cycle::factory()->create(['is_active' => true]);
    
    // Link parent to student
    $parent->students()->attach($student->id, ['relationship' => 'PADRE']);

    ExamSchedule::create([
        'cycle_id' => $cycle->id,
        'grade' => '2',
        'group_name' => 'B',
        'period' => '1',
        'subject' => 'Historia',
        'exam_date' => now()->next('Tuesday')->format('Y-m-d'),
        'day_of_week' => 'Martes',
    ]);

    Volt::actingAs($parent)
        ->test('exams.index')
        ->assertSee('Historia')
        ->assertSee('2')
        ->assertSee('B');
});

test('filtering works correctly', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $cycle = Cycle::factory()->create(['is_active' => true]);
    
    ExamSchedule::create([
        'cycle_id' => $cycle->id,
        'grade' => '1',
        'group_name' => 'A',
        'period' => '1',
        'subject' => 'Visible',
        'exam_date' => now()->next('Wednesday')->format('Y-m-d'),
        'day_of_week' => 'Miércoles',
    ]);

    ExamSchedule::create([
        'cycle_id' => $cycle->id,
        'grade' => '2',
        'group_name' => 'B',
        'period' => '2',
        'subject' => 'Hidden',
        'exam_date' => now()->next('Thursday')->format('Y-m-d'),
        'day_of_week' => 'Jueves',
    ]);

    Volt::actingAs($admin)
        ->test('exams.index')
        ->set('periodFilter', '1')
        ->assertSee('Visible')
        ->assertDontSee('Hidden');
});
