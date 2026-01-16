<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Do not force HTTPS here — allow runtime scheme (useful for tunnels/dev).

        \Illuminate\Support\Facades\Gate::define('admin-only', function ($user) {
            return $user->role === 'ADMIN';
        });

        \Illuminate\Support\Facades\Gate::define('teacher-or-admin', function ($user) {
            return in_array($user->role, ['ADMIN', 'TEACHER']);
        });

        \Illuminate\Support\Facades\Gate::define('parent-only', function ($user) {
            return $user->role === 'PARENT';
        });
    }
}
