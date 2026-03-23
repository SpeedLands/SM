<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class AttendanceController extends Controller
{
    /**
     * Return bulk CURP data for local scanner cache.
     */
    public function curps(): JsonResponse
    {
        Gate::authorize('teacher-or-admin');

        return response()->json(
            Student::select('id', 'curp', 'name', 'grade', 'group_name', 'turn')
                ->whereNotNull('curp')
                ->where('curp', '!=', '')
                ->get()
        );
    }

    /**
     * Process a batch of attendance scans.
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('teacher-or-admin');

        $request->validate([
            'scans' => 'required|array',
            'scans.*.curp' => 'required|string|max:18',
            'scans.*.timestamp' => 'nullable|numeric',
        ]);

        $today = Carbon::today()->toDateString();
        $graceMinutes = (int) Setting::get('attendance.grace_minutes', 10);
        $matutinoEntryTime = Setting::get('attendance.matutino_entry_time', '07:30');
        $vespertinoEntryTime = Setting::get('attendance.vespertino_entry_time', '13:30');

        $results = [];

        foreach ($request->input('scans') as $scan) {
            $results[] = $this->processScan(
                $scan,
                $today,
                $graceMinutes,
                $matutinoEntryTime,
                $vespertinoEntryTime,
            );
        }

        return response()->json(['results' => $results]);
    }

    /**
     * Process a single scan entry and return its result.
     *
     * @param  array{curp: string, timestamp: int|null}  $scan
     * @return array<string, mixed>
     */
    protected function processScan(
        array $scan,
        string $today,
        int $graceMinutes,
        string $matutinoEntryTime,
        string $vespertinoEntryTime,
    ): array {
        $curp = trim(strtoupper($scan['curp']));
        $student = Student::where('curp', $curp)->first();

        if (! $student) {
            return [
                'curp' => $curp,
                'timestamp' => $scan['timestamp'] ?? null,
                'status' => 'error',
                'message' => 'CURP no encontrado',
            ];
        }

        $now = isset($scan['timestamp'])
            ? Carbon::createFromTimestampMs($scan['timestamp'])
            : Carbon::now();

        $status = $this->determineAttendanceStatus(
            $student->turn,
            $now,
            $graceMinutes,
            $matutinoEntryTime,
            $vespertinoEntryTime,
        );

        try {
            $attendance = Attendance::updateOrCreate(
                ['student_id' => $student->id, 'date' => $today],
                ['entry_time' => $now->format('H:i:s'), 'status' => $status]
            );

            return [
                'curp' => $curp,
                'timestamp' => $scan['timestamp'] ?? null,
                'status' => $status,
                'duplicate' => ! $attendance->wasRecentlyCreated,
                'entry_time' => $now->format('H:i:s'),
                'student_name' => $student->name,
            ];
        } catch (\Exception $e) {
            return [
                'curp' => $curp,
                'timestamp' => $scan['timestamp'] ?? null,
                'status' => 'error',
                'message' => 'Error al guardar',
            ];
        }
    }

    /**
     * Determine the attendance status based on entry time and turn.
     */
    protected function determineAttendanceStatus(
        ?string $turn,
        Carbon $now,
        int $graceMinutes,
        string $matutinoEntryTime,
        string $vespertinoEntryTime,
    ): string {
        $entryTime = match ($turn) {
            'MATUTINO' => $matutinoEntryTime,
            'VESPERTINO' => $vespertinoEntryTime,
            default => null,
        };

        if ($entryTime) {
            $threshold = Carbon::createFromFormat('H:i', $entryTime)->addMinutes($graceMinutes);

            if ($now->greaterThan($threshold)) {
                return 'RETARDO';
            }
        }

        return 'PRESENTE';
    }
}
