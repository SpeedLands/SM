<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceExport;
use App\Exports\ParentsExport;
use App\Exports\ReportsExport;
use App\Exports\StudentsExport;
use App\Exports\StudentsWithParentsExport;
use App\Exports\TeachersExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function teachers(Request $request)
    {
        Gate::authorize('admin-only');

        $generatePasswords = $request->boolean('generate_passwords');

        return Excel::download(new TeachersExport($generatePasswords), 'maestros_'.now()->format('Y-m-d').'.xlsx');
    }

    public function parents(Request $request)
    {
        Gate::authorize('admin-only');

        $groupId = $request->query('group_id');
        $cycleId = $request->query('cycle_id');
        $generatePasswords = $request->boolean('generate_passwords');

        $filename = 'padres_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new ParentsExport($groupId, $cycleId, $generatePasswords), $filename);
    }

    public function students(Request $request)
    {
        Gate::authorize('admin-only');

        $groupId = $request->query('group_id');
        $cycleId = $request->query('cycle_id');
        $includeParents = $request->boolean('include_parents');
        $generatePasswords = $request->boolean('generate_passwords');

        $filename = 'alumnos_'.now()->format('Y-m-d').'.xlsx';

        if ($includeParents && $groupId) {
            return Excel::download(new StudentsWithParentsExport($groupId, $cycleId, $generatePasswords), $filename);
        }

        return Excel::download(new StudentsExport($groupId, $cycleId), $filename);
    }

    public function attendance(Request $request)
    {
        Gate::authorize('admin-only');

        $request->validate([
            'group_id' => ['required', 'string', 'exists:class_groups,id'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020'],
        ]);

        $groupId = $request->query('group_id');
        $month = $request->integer('month');
        $year = $request->integer('year');

        $filename = 'asistencia_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new AttendanceExport($groupId, $month, $year), $filename);
    }

    public function reports(Request $request)
    {
        Gate::authorize('admin-only');

        $groupId = $request->query('group_id');
        $studentId = $request->query('student_id');
        $allCycles = $request->boolean('all_cycles');

        $filename = 'reportes_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new ReportsExport($groupId, $studentId, $allCycles), $filename);
    }
}
