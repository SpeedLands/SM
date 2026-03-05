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
            $restrictedRoutes = ['users.index', 'cycles.index', 'infractions.index'];
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
Route::post('/fcm-token', [\App\Http\Controllers\FcmController::class, 'storeToken'])->name('fcm-token')->middleware('auth');
