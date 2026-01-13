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
    Volt::route('cycles', 'cycles.index')->name('cycles.index');
    Volt::route('users', 'users.index')->name('users.index');
    Volt::route('reglamento', 'regulations.index')->name('regulations.index');
    Volt::route('alumnos', 'students.index')->name('students.index');
    Volt::route('reportes', 'reports.index')->name('reports.index');
    Volt::route('servicio-comunitario', 'community-services.index')->name('community-services.index');
    Volt::route('avisos', 'notices.index')->name('notices.index');
    Volt::route('citatorios', 'citations.index')->name('citations.index');
    Volt::route('examenes', 'exams.index')->name('exams.index');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

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
