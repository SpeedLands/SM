<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse">
                <x-app-logo />
            </a>

            @php
                $user = auth()->user();
                $isViewParent = $user->isViewParent();
                $isViewStaff = $user->isViewStaff();
                $hasDualRole = ($user->isAdmin() || $user->isTeacher()) && $user->hasStudents();

                if ($isViewParent) {
                    $reportsCount = $user->getUnsignedReportsCount();
                    $servicesCount = $user->getUnsignedCommunityServicesCount();
                    $noticesCount = $user->getUnsignedNoticesCount();
                    $citationsCount = $user->getUnsignedCitationsCount();
                    $totalPending = $reportsCount + $servicesCount + $noticesCount + $citationsCount;
                }
            @endphp

            <livewire:layout.navigation />

            @if($hasDualRole)
                <div class="px-3 py-4 mt-auto">
                    <form action="{{ route('toggle-view') }}" method="POST">
                        @csrf
                        <flux:button type="submit" variant="filled" icon="{{ $isViewParent ? 'briefcase' : 'user-group' }}" class="w-full justify-start text-xs font-bold uppercase tracking-wider shadow-sm transition-all active:scale-95">
                            {{ $isViewParent ? 'Vista Personal' : 'Vista Familiar' }}
                        </flux:button>
                    </form>
                </div>
            @endif

            <flux:spacer />

            <!-- Desktop User Menu -->
            <flux:dropdown class="hidden lg:block" position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevrons-up-down"
                    data-test="sidebar-menu-button"
                />

                @include('partials.app.user-menu')
            </flux:dropdown>

        </flux:sidebar>

        <livewire:layout.mobile-header />

        {{ $slot }}

        @fluxScripts
        <script>
            document.addEventListener('livewire:initialized', () => {
                Livewire.hook('request', ({ fail }) => {
                    fail(({ status, preventDefault }) => {
                        if (status === 419) {
                            preventDefault();
                            window.location.reload();
                        }
                    });
                });

                // Local Notifications Logic for Parents
                @if($isViewParent && ($totalPending ?? 0) > 0)
                    const totalPending = {{ $totalPending }};
                    
                    if ("Notification" in window) {
                        // Check if we should show a notification (e.g. once per session)
                        if (!sessionStorage.getItem('notified_pending')) {
                            const requestPermissionAndNotify = () => {
                                Notification.requestPermission().then(permission => {
                                    if (permission === "granted") {
                                        new Notification("⚠️ Trámites Pendientes", {
                                            body: `Tienes ${totalPending} documentos o avisos que requieren tu atención.`,
                                            icon: "/apple-touch-icon.png",
                                            tag: "pending-notifications"
                                        });
                                        sessionStorage.setItem('notified_pending', 'true');
                                    }
                                });
                            };

                            if (Notification.permission === "granted") {
                                requestPermissionAndNotify();
                            } else if (Notification.permission !== "denied") {
                                // Optional: You might want a button to trigger this instead of auto-requesting
                                // but for a PWA it's common to request on interaction or dashboard entry.
                                setTimeout(requestPermissionAndNotify, 2000);
                            }
                        }
                    }
                @endif
            });
        </script>
    </body>
</html>
