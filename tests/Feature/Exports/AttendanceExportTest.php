<?php

use App\Models\Attendance;
use App\Models\ClassGroup;
use App\Models\Cycle;
use App\Models\Student;
use App\Models\User;
use App\Models\StudentCycleAssociation;
use App\Exports\AttendanceExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'ADMIN']);
    $this->cycle = Cycle::factory()->create(['is_active' => true]);
    $this->group = ClassGroup::create([
        'cycle_id' => $this->cycle->id,
        'grade' => '2',
        'section' => 'A',
    ]);
    
    $this->student1 = Student::factory()->create(['name' => 'A Student']);
    $this->student2 = Student::factory()->create(['name' => 'B Student']);

    StudentCycleAssociation::create([
        'student_id' => $this->student1->id,
        'cycle_id' => $this->cycle->id,
        'class_group_id' => $this->group->id,
        'status' => 'ACTIVE',
    ]);

    StudentCycleAssociation::create([
        'student_id' => $this->student2->id,
        'cycle_id' => $this->cycle->id,
        'class_group_id' => $this->group->id,
        'status' => 'ACTIVE',
    ]);
});

test('admin can download attendance export', function () {
    Excel::fake();

    $response = $this->actingAs($this->admin)
        ->get(route('export.attendance', [
            'group_id' => $this->group->id,
            'month' => 1,
            'year' => 2026,
        ]));

    $response->assertSuccessful();
    Excel::assertDownloaded('asistencia_'.now()->format('Y-m-d').'.xlsx');
});

test('attendance export contains correct symbols and filters working days', function () {
    $date1 = Carbon::create(2026, 1, 5); // Monday
    $date2 = Carbon::create(2026, 1, 6); // Tuesday
    // Day 7 is not used (non-working day in our test context)

    Attendance::create([
        'student_id' => $this->student1->id,
        'date' => $date1->format('Y-m-d'),
        'status' => 'PRESENTE',
    ]);

    Attendance::create([
        'student_id' => $this->student2->id,
        'date' => $date1->format('Y-m-d'),
        'status' => 'FALTA',
    ]);

    Attendance::create([
        'student_id' => $this->student1->id,
        'date' => $date2->format('Y-m-d'),
        'status' => 'RETARDO',
    ]);

    $export = new AttendanceExport($this->group->id, 1, 2026);
    $collection = $export->collection();

    expect($collection)->toHaveCount(2);
    
    $row1 = $collection->first();
    expect($row1['name'])->toBe($this->student1->name);
    expect($row1['2026-01-05'])->toBe('.'); // PRESENTE
    expect($row1['2026-01-06'])->toBe('+'); // RETARDO
    
    $row2 = $collection->last();
    expect($row2['name'])->toBe($this->student2->name);
    expect($row2['2026-01-05'])->toBe('|'); // FALTA
    expect($row2['2026-01-06'])->toBe(''); // Empty if no record
});
