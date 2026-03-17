<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Volt::route('dashboard', 'dashboard.index')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('can:admin-only')->group(function () {
        Volt::route('cycles', 'cycles.index')->name('cycles.index');
        Volt::route('users', 'users.index')->name('users.index');
        Route::get('importar-datos', \App\Livewire\DataImporter::class)->name('data-importer');
        Volt::route('exportar-datos', 'data-exporter')->name('data-exporter');

        Route::get('exportar/maestros', [\App\Http\Controllers\ExportController::class, 'teachers'])->name('export.teachers');
        Route::get('exportar/padres', [\App\Http\Controllers\ExportController::class, 'parents'])->name('export.parents');
        Route::get('exportar/alumnos', [\App\Http\Controllers\ExportController::class, 'students'])->name('export.students');
        Route::get('exportar/asistencias', [\App\Http\Controllers\ExportController::class, 'attendance'])->name('export.attendance');
    });
    Volt::route('reglamento', 'regulations.index')->name('regulations.index');
    Volt::route('alumnos', 'students.index')->name('students.index');
    Volt::route('alumnos/promover', 'students.promote-students')->name('students.promote');
    Route::get('alumnos/{student}/credencial', [\App\Http\Controllers\CredentialController::class, 'show'])->name('students.credential');
    Route::post('alumnos/credenciales/bulk', [\App\Http\Controllers\CredentialController::class, 'bulk'])->name('students.credential.bulk');
    Volt::route('reportes', 'reports.index')->name('reports.index');
    Volt::route('servicio-comunitario', 'community-services.index')->name('community-services.index');
    Volt::route('avisos', 'notices.index')->name('notices.index');
    Volt::route('citatorios', 'citations.index')->name('citations.index');
    Volt::route('examenes', 'exams.index')->name('exams.index');
    Volt::route('tutoriales', 'tutorials.index')->name('tutorials.index');
    Volt::route('reportes/tipos', 'infractions.index')->name('infractions.index');
    Volt::route('calendario', 'calendar.index')->name('calendar.index');

    // Attendance System
    Volt::route('asistencia', 'attendance.index')->name('attendance.index');
    Volt::route('asistencia/escanear', 'attendance.scanner')->name('attendance.scanner');
    Volt::route('escaneo-rapido', 'global-scanner')->name('global-scanner');

    // API: Bulk CURP data for local scanner cache
    Route::get('api/curps', function () {
        \Illuminate\Support\Facades\Gate::authorize('teacher-or-admin');

        return response()->json(
            \App\Models\Student::select('id', 'curp', 'name', 'grade', 'group_name', 'turn')
                ->whereNotNull('curp')
                ->where('curp', '!=', '')
                ->get()
        );
    })->name('api.curps');

    // API: Background attendance recording (fire-and-forget from scanner)
    Route::post('api/attendance', function (\Illuminate\Http\Request $request) {
        \Illuminate\Support\Facades\Gate::authorize('teacher-or-admin');

        $request->validate([
            'curp' => 'required|string|max:18',
        ]);

        $curp = trim(strtoupper($request->input('curp')));
        $student = \App\Models\Student::where('curp', $curp)->first();

        if (!$student) {
            return response()->json(['status' => 'error', 'message' => 'CURP no encontrado'], 404);
        }

        $today = \Illuminate\Support\Carbon::today()->toDateString();
        $now = \Illuminate\Support\Carbon::now();
        $status = 'PRESENTE';
        $graceMinutes = (int) \App\Models\Setting::get('attendance.grace_minutes', 10);

        if ($student->turn === 'MATUTINO') {
            $entryTime = \App\Models\Setting::get('attendance.matutino_entry_time', '07:30');
            $threshold = \Illuminate\Support\Carbon::createFromFormat('H:i', $entryTime)->addMinutes($graceMinutes);
            if ($now->greaterThan($threshold)) {
                $status = 'RETARDO';
            }
        } elseif ($student->turn === 'VESPERTINO') {
            $entryTime = \App\Models\Setting::get('attendance.vespertino_entry_time', '13:30');
            $threshold = \Illuminate\Support\Carbon::createFromFormat('H:i', $entryTime)->addMinutes($graceMinutes);
            if ($now->greaterThan($threshold)) {
                $status = 'RETARDO';
            }
        }

        $attendance = \App\Models\Attendance::updateOrCreate(
            ['student_id' => $student->id, 'date' => $today],
            ['entry_time' => $now->format('H:i:s'), 'status' => $status]
        );

        return response()->json([
            'status' => $status,
            'duplicate' => !$attendance->wasRecentlyCreated,
            'entry_time' => $now->format('H:i:s'),
            'student_name' => $student->name,
        ]);
    })->name('api.attendance');

    Route::post('toggle-view', function () {
        $user = auth()->user();
        if (! $user->hasStudents()) {
            return back();
        }

        $current = session('active_view', 'staff');
        $new = $current === 'staff' ? 'parent' : 'staff';

        session(['active_view' => $new]);

        // If switching TO parent view, and we are on a restricted route, redirect to dashboard
        if ($new === 'parent') {
            $restrictedRoutes = [
                'users.index',
                'cycles.index',
                'infractions.index',
                'students.index',
                'students.promote',
                'students.credential',
                'students.credential.bulk',
                'calendar.index',
                'attendance.index',
                'attendance.scanner',
                'data-importer',
                'data-exporter',
                'export.teachers',
                'export.parents',
                'export.students',
                'export.attendance',
                'settings.attendance',
            ];
            $currentRouteName = request()->header('Referer') ? app('router')->getRoutes()->match(app('request')->create(request()->header('Referer')))->getName() : null;

            if (in_array($currentRouteName, $restrictedRoutes)) {
                return redirect()->route('dashboard')->with('notify', [
                    'message' => 'Cambiando a vista de Padre',
                    'variant' => 'success',
                ]);
            }
        }

        return back()->with('notify', [
            'message' => 'Cambiando a vista de '.($new === 'parent' ? 'Padre' : 'Personal'),
            'variant' => 'success',
        ]);
    })->name('toggle-view');
});

Route::middleware(['auth'])->group(function () {
    Route::get('settings', function () {
        return auth()->user()->isAdmin()
            ? redirect()->route('profile.edit')
            : redirect()->route('notifications.edit');
    })->name('settings');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');
    Volt::route('settings/notifications', 'settings.notifications')->name('notifications.edit');
    Volt::route('settings/asistencia', 'settings.attendance')->middleware('can:admin-only')->name('settings.attendance');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
Route::post('/fcm-token', [\App\Http\Controllers\PushSubscriptionController::class, 'storeLegacyToken'])->name('fcm-token')->middleware('auth');
Route::post('/push/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'store'])->name('push.subscribe')->middleware('auth');
Route::post('/push/unsubscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe')->middleware('auth');
