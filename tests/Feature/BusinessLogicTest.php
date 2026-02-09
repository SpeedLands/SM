<?php

use App\Models\Notice;
use App\Models\Student;
use App\Models\NoticeSignature;
use App\Models\Cycle;
use App\Models\ClassGroup;
use App\Models\User;
use App\Models\StudentCycleAssociation;

test('notice signature statistics are calculated correctly', function () {
    $cycle = Cycle::factory()->create(['is_active' => true]);
    
    // Create students in different grades/groups
    $classGroupA = ClassGroup::factory()->create(['cycle_id' => $cycle->id, 'grade' => 1, 'section' => 'A']);
    $classGroupB = ClassGroup::factory()->create(['cycle_id' => $cycle->id, 'grade' => 2, 'section' => 'B']);
    
    $studentsA = Student::factory()->count(10)->create(['grade' => 1]);
    foreach($studentsA as $s) {
        StudentCycleAssociation::create([
            'student_id' => $s->id,
            'cycle_id' => $cycle->id,
            'class_group_id' => $classGroupA->id,
            'status' => 'ACTIVE'
        ]);
        // Associate parent
        $parent = User::factory()->create(['role' => 'PARENT']);
        $s->parents()->attach($parent);
    }

    $studentsB = Student::factory()->count(5)->create(['grade' => 2]);
    foreach($studentsB as $s) {
        StudentCycleAssociation::create([
            'student_id' => $s->id,
            'cycle_id' => $cycle->id,
            'class_group_id' => $classGroupB->id,
            'status' => 'ACTIVE'
        ]);
        $parent = User::factory()->create(['role' => 'PARENT']);
        $s->parents()->attach($parent);
    }

    // 1. Notice for ALL PARENTS
    $noticeAll = Notice::create([
        'cycle_id' => $cycle->id,
        'title' => 'All Parents',
        'content' => 'Content',
        'type' => 'GENERAL',
        'target_audience' => 'PARENTS',
        'author_id' => User::factory()->create(['role' => 'ADMIN'])->id,
        'date' => now(),
    ]);

    expect($noticeAll->getExpectedRecipientsCount())->toBe(15);
    
    // Sign for 3 students from A
    foreach($studentsA->take(3) as $student) {
        NoticeSignature::create([
            'notice_id' => $noticeAll->id,
            'student_id' => $student->id,
            'parent_id' => $student->parents->first()->id,
            'signed_at' => now(),
        ]);
    }

    $stats = $noticeAll->getSignatureStats();
    expect($stats['expected'])->toBe(15);
    expect($stats['signed'])->toBe(3);
    expect($stats['pending'])->toBe(12);
    expect($stats['percentage'])->toEqual(20);

    // 2. Notice for Specific Grade (Grade 1)
    $noticeGrade1 = Notice::create([
        'cycle_id' => $cycle->id,
        'title' => 'Grade 1',
        'content' => 'Content',
        'type' => 'GENERAL',
        'target_audience' => 'PARENTS',
        'target_grades' => [1],
        'author_id' => User::factory()->create(['role' => 'ADMIN'])->id,
        'date' => now(),
    ]);

    expect($noticeGrade1->getExpectedRecipientsCount())->toBe(10);
    
     // Sign for 10 students from A
    foreach($studentsA as $student) {
        NoticeSignature::create([
            'notice_id' => $noticeGrade1->id,
            'student_id' => $student->id,
            'parent_id' => $student->parents->first()->id,
            'signed_at' => now(),
        ]);
    }

    $stats = $noticeGrade1->getSignatureStats();
    expect($stats['expected'])->toBe(10);
    expect($stats['signed'])->toBe(10);
    expect($stats['percentage'])->toEqual(100);
});
