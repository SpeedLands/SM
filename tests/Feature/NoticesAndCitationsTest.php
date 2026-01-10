<?php

use App\Models\User;
use App\Models\Cycle;
use App\Models\Student;
use App\Models\Notice;
use App\Models\NoticeSignature;
use App\Models\Citation;
use Livewire\Volt\Volt;

test('admins can create notices', function () {
    $admin = User::factory()->create(['role' => 'ADMIN']);
    $cycle = Cycle::factory()->create(['is_active' => true]);

    Volt::actingAs($admin)
        ->test('notices.index')
        ->set('title', 'New Notice')
        ->set('content', 'This is a test notice content.')
        ->set('type', 'URGENT')
        ->call('saveNotice')
        ->assertHasNoErrors();

    expect(Notice::count())->toBe(1);
    expect(Notice::first()->type)->toBe('URGENT');
});

test('parents can sign notices with authorization', function () {
    $parent = User::factory()->create(['role' => 'PARENT']);
    $student = Student::factory()->create();
    $cycle = Cycle::factory()->create(['is_active' => true]);
    
    // Link parent to student
    $parent->students()->attach($student->id, ['relationship' => 'PADRE']);

    $notice = Notice::create([
        'cycle_id' => $cycle->id,
        'author_id' => User::factory()->create(['role' => 'ADMIN'])->id,
        'title' => 'Excursion',
        'content' => 'Do you allow excursion?',
        'type' => 'EVENT',
        'target_audience' => 'PARENTS',
        'requires_authorization' => true,
        'date' => now(),
    ]);

    Volt::actingAs($parent)
        ->test('notices.index')
        ->assertSee('Excursion')
        ->call('signNotice', $notice->id, $student->id, true)
        ->assertHasNoErrors();

    $signature = NoticeSignature::where('notice_id', $notice->id)->where('student_id', $student->id)->first();
    expect($signature)->not->toBeNull();
    expect($signature->authorized)->toBeTrue();
});

test('teachers can manage citations', function () {
    $teacher = User::factory()->create(['role' => 'TEACHER']);
    $student = Student::factory()->create();
    $cycle = Cycle::factory()->create(['is_active' => true]);

    Volt::actingAs($teacher)
        ->test('citations.index')
        ->call('selectStudent', $student->id)
        ->set('reason', 'Academic review')
        ->set('citationDate', now()->addDay()->format('Y-m-d'))
        ->set('citationTime', '09:00')
        ->call('saveCitation')
        ->assertHasNoErrors();

    expect(Citation::count())->toBe(1);
    $citation = Citation::first();
    expect($citation->status)->toBe('PENDING');

    Volt::actingAs($teacher)
        ->test('citations.index')
        ->call('updateStatus', $citation->id, 'ATTENDED');

    expect($citation->refresh()->status)->toBe('ATTENDED');
});
