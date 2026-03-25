<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\Cycle;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Contracts\Console\Kernel;

$today = now()->toDateString();
$currentTime = now()->format('H:i');

$matutinoTime = Setting::get('attendance.matutino_auto_absence_time', '14:00');
$matutinoLastRun = Setting::get('attendance.matutino_auto_absence_last_run', '2000-01-01');
echo "Matutino -> Time: $matutinoTime, LastRun: $matutinoLastRun\n";

$vespertinoTime = Setting::get('attendance.vespertino_auto_absence_time', '20:00');
$vespertinoLastRun = Setting::get('attendance.vespertino_auto_absence_last_run', '2000-01-01');
echo "Vespertino -> Time: $vespertinoTime, LastRun: $vespertinoLastRun\n";

echo "Current -> Date: $today, Time: $currentTime\n";

if ($vespertinoLastRun !== $today && $currentTime >= $vespertinoTime) {
    echo "Condition for Vespertino is met.\n";
    $activeCycle = Cycle::where('is_active', true)->first();
    $students = Student::whereHas('currentCycleAssociation', function ($query) use ($activeCycle) {
        $query->where('cycle_id', $activeCycle->id)
            ->whereHas('classGroup', function ($q) {
                $q->whereNotIn('section', ['A', 'B', 'C', 'D', 'E', 'F', 'a', 'b', 'c', 'd', 'e', 'f']);
            });
    })->get();
    echo 'Students found: '.$students->count()."\n";
} else {
    echo "Condition for Vespertino NOT met.\n";
}
