<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use App\Models\Attendance;
use App\Models\Cycle;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    if (! Setting::get('attendance.auto_absence_enabled', false)) {
        return;
    }

    $today = now()->toDateString();
    $currentTime = now()->format('H:i');

    $activeCycle = Cycle::where('is_active', true)->first();
    if (! $activeCycle) {
        return;
    }

    // Check Matutino
    $matutinoTime = Setting::get('attendance.matutino_auto_absence_time', '14:00');
    $matutinoLastRun = Setting::get('attendance.matutino_auto_absence_last_run', '2000-01-01');

    if ($matutinoLastRun !== $today && $currentTime >= $matutinoTime) {
        $students = Student::whereHas('currentCycleAssociation', function ($query) use ($activeCycle) {
            $query->where('cycle_id', $activeCycle->id)
                ->whereHas('group', function ($q) {
                    $q->whereIn('section', ['A', 'B', 'C', 'D', 'E', 'F', 'a', 'b', 'c', 'd', 'e', 'f']);
                });
        })->get();

        foreach ($students as $student) {
            Attendance::firstOrCreate(
                ['student_id' => $student->id, 'date' => $today],
                ['status' => 'FALTA', 'notes' => 'Generado automáticamente.']
            );
        }
        Setting::set('attendance.matutino_auto_absence_last_run', $today);
    }

    // Check Vespertino
    $vespertinoTime = Setting::get('attendance.vespertino_auto_absence_time', '20:00');
    $vespertinoLastRun = Setting::get('attendance.vespertino_auto_absence_last_run', '2000-01-01');

    if ($vespertinoLastRun !== $today && $currentTime >= $vespertinoTime) {
        $students = Student::whereHas('currentCycleAssociation', function ($query) use ($activeCycle) {
            $query->where('cycle_id', $activeCycle->id)
                ->whereHas('group', function ($q) {
                    $q->whereNotIn('section', ['A', 'B', 'C', 'D', 'E', 'F', 'a', 'b', 'c', 'd', 'e', 'f']);
                });
        })->get();

        foreach ($students as $student) {
            Attendance::firstOrCreate(
                ['student_id' => $student->id, 'date' => $today],
                ['status' => 'FALTA', 'notes' => 'Generado automáticamente.']
            );
        }
        Setting::set('attendance.vespertino_auto_absence_last_run', $today);
    }
})->everyMinute();
