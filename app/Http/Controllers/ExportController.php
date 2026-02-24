<?php

namespace App\Http\Controllers;

use App\Exports\ParentsExport;
use App\Exports\StudentsExport;
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

        $filename = 'alumnos_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new StudentsExport($groupId, $cycleId), $filename);
    }
}
