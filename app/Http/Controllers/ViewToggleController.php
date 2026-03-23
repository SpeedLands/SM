<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class ViewToggleController extends Controller
{
    /**
     * Routes that are restricted when in parent view.
     *
     * @var array<int, string>
     */
    protected array $restrictedRoutes = [
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

    /**
     * Toggle between staff and parent views.
     */
    public function __invoke(): RedirectResponse
    {
        $user = auth()->user();

        if (! $user->hasStudents()) {
            return back();
        }

        $current = session('active_view', 'staff');
        $new = $current === 'staff' ? 'parent' : 'staff';

        session(['active_view' => $new]);

        if ($new === 'parent' && $this->isOnRestrictedRoute()) {
            return redirect()->route('dashboard')->with('notify', [
                'message' => 'Cambiando a vista de Padre',
                'variant' => 'success',
            ]);
        }

        return back()->with('notify', [
            'message' => 'Cambiando a vista de '.($new === 'parent' ? 'Padre' : 'Personal'),
            'variant' => 'success',
        ]);
    }

    /**
     * Check if the user is currently on a restricted route.
     */
    protected function isOnRestrictedRoute(): bool
    {
        $referer = request()->header('Referer');

        if (! $referer) {
            return false;
        }

        try {
            $currentRouteName = app('router')
                ->getRoutes()
                ->match(app('request')->create($referer))
                ->getName();

            return in_array($currentRouteName, $this->restrictedRoutes);
        } catch (\Exception) {
            return false;
        }
    }
}
