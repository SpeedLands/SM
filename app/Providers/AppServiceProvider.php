<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Request::macro('hasValidSignature', function ($absolute = true) {
            if (str_contains($this->path(), 'preview-file') || str_contains($this->path(), 'upload-file')) {
                return true;
            }

            return URL::hasValidSignature($this, $absolute);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            Login::class,
            function ($event) {
                $event->user->update([
                    'last_login_at' => now(),
                ]);
            }
        );

        Gate::define('admin-only', function ($user) {
            return $user->isAdmin() && $user->isViewStaff();
        });

        Gate::define('teacher-or-admin', function ($user) {
            return ($user->isAdmin() || $user->isTeacher()) && $user->isViewStaff();
        });

        Gate::define('parent-only', function ($user) {
            return $user->isViewParent();
        });
    }
}
