<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CredentialController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FcmController;
use App\Http\Controllers\ViewToggleController;
use App\Livewire\DataImporter;
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
        Route::get('importar-datos', DataImporter::class)->name('data-importer');
        Volt::route('exportar-datos', 'data-exporter')->name('data-exporter');

        Route::get('exportar/maestros', [ExportController::class, 'teachers'])->name('export.teachers');
        Route::get('exportar/padres', [ExportController::class, 'parents'])->name('export.parents');
        Route::get('exportar/alumnos', [ExportController::class, 'students'])->name('export.students');
        Route::get('exportar/asistencias', [ExportController::class, 'attendance'])->name('export.attendance');
    });
    Volt::route('reglamento', 'regulations.index')->name('regulations.index');
    Volt::route('alumnos', 'students.index')->name('students.index');
    Volt::route('alumnos/promover', 'students.promote-students')->name('students.promote');
    Route::get('alumnos/{student}/credencial', [CredentialController::class, 'show'])->name('students.credential');
    Route::post('alumnos/credenciales/bulk', [CredentialController::class, 'bulk'])->name('students.credential.bulk');
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

    // API: Attendance system
    Route::get('api/curps', [AttendanceController::class, 'curps'])->name('api.curps');
    Route::post('api/attendance', [AttendanceController::class, 'store'])->name('api.attendance');

    Route::post('toggle-view', ViewToggleController::class)->name('toggle-view');
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
Route::post('/fcm-token', [FcmController::class, 'storeToken'])->name('fcm-token')->middleware('auth');

Route::post('/push-subscribe', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'type' => 'required|string|in:webpush,fcm',
        'endpoint' => 'required_if:type,webpush|string',
        'keys.p256dh' => 'required_if:type,webpush|string',
        'keys.auth' => 'required_if:type,webpush|string',
    ]);

    $user = auth()->user();

    \App\Models\PushSubscription::updateOrCreate(
        ['user_id' => $user->id, 'endpoint' => $request->input('endpoint')],
        [
            'type' => $request->input('type'),
            'p256dh_key' => $request->input('keys.p256dh'),
            'auth_key' => $request->input('keys.auth'),
            'user_agent' => $request->userAgent(),
        ]
    );

    return response()->json(['message' => 'Subscription stored successfully.']);
})->name('push.subscribe')->middleware('auth');
