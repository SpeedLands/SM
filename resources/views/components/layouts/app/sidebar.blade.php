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
        
        <!-- Firebase SDK -->
        <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"></script>
        <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js"></script>

        <script>
            const firebaseConfig = {
                apiKey: "AIzaSyDrMr4T9g9eUub_LDYcs27vp5aE6tolB8I",
                authDomain: "educom-24ee8.firebaseapp.com",
                projectId: "educom-24ee8",
                storageBucket: "educom-24ee8.firebasestorage.app",
                messagingSenderId: "977130140369",
                appId: "1:977130140369:web:75a5296cab81caa5c28bf0",
                measurementId: "G-JD1JYBKQ4Y"
            };

            firebase.initializeApp(firebaseConfig);
            const messaging = firebase.messaging();

            function updateFcmToken() {
                if (!('serviceWorker' in navigator)) return;

                navigator.serviceWorker.ready.then((registration) => {
                    // Try to unsubscribe first to clear any stale state that might cause AbortError
                    registration.pushManager.getSubscription().then(subscription => {
                        if (subscription) {
                            subscription.unsubscribe().then(() => {
                                console.log('Unsubscribed from old push service');
                                retrieveNewToken(registration);
                            }).catch((err) => {
                                console.warn('Unsubscribe failed, attempting new token anyway', err);
                                retrieveNewToken(registration);
                            });
                        } else {
                            retrieveNewToken(registration);
                        }
                    }).catch(() => retrieveNewToken(registration));
                });
            }

            function retrieveNewToken(registration) {
                messaging.getToken({ 
                    vapidKey: "{{ env('VAPID_PUBLIC_KEY') }}",
                    serviceWorkerRegistration: registration
                }).then((currentToken) => {
                    if (currentToken) {
                        fetch("{{ route('fcm-token') }}", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },
                            body: JSON.stringify({ token: currentToken })
                        })
                        .then(response => response.json())
                        .then(data => console.log('FCM Token Updated:', data))
                        .catch(err => console.error('Error updating FCM token:', err));
                    }
                }).catch((err) => {
                    console.error('An error occurred while retrieving token. ', err);
                });
            }

            document.addEventListener('livewire:initialized', () => {
                Livewire.hook('request', ({ fail }) => {
                    fail(({ status, preventDefault }) => {
                        if (status === 419) {
                            preventDefault();
                            window.location.reload();
                        }
                    });
                });

                // Request FCM Permission and Token
                if ("Notification" in window) {
                    if (Notification.permission === "granted") {
                        updateFcmToken();
                    } else if (Notification.permission !== "denied") {
                        Notification.requestPermission().then(permission => {
                            if (permission === "granted") {
                                updateFcmToken();
                            }
                        });
                    }
                }

                // Handle Foreground Messages
                messaging.onMessage((payload) => {
                    console.log('Message received. ', payload);
                    const title = payload.notification.title;
                    const body = payload.notification.body;
                    const icon = payload.notification.icon || "/apple-touch-icon.png";
                    const url = payload.data ? payload.data.url : null;

                    window.dispatchEvent(new CustomEvent('flux-toast', {
                        detail: {
                            title: title,
                            body: body,
                            icon: icon,
                            variant: 'success',
                            url: url
                        }
                    }));
                });

                // Global listener for 'notify' event from Livewire
                window.addEventListener('notify', (event) => {
                    const data = event.detail[0] || event.detail;
                    window.dispatchEvent(new CustomEvent('flux-toast', {
                        detail: {
                            title: data.title || 'Aviso',
                            body: data.message || data.body || '',
                            variant: data.variant || 'success'
                        }
                    }));
                });
            });
        </script>

        <!-- Custom Toast Container for Flux Free -->
        <div 
            x-data="{ 
                toasts: [],
                add(toast) {
                    toast.id = Date.now();
                    this.toasts.push(toast);
                    setTimeout(() => this.remove(toast.id), 5000);
                },
                remove(id) {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                }
            }"
            @flux-toast.window="add($event.detail)"
            class="fixed bottom-0 right-0 p-6 z-50 flex flex-col gap-3 w-full max-w-sm"
        >
            <template x-for="toast in toasts" :key="toast.id">
                <div 
                    x-show="true"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform translate-y-2"
                    x-transition:enter-end="opacity-100 transform translate-y-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 transform translate-y-0"
                    x-transition:leave-end="opacity-0 transform translate-y-2"
                    class="cursor-pointer"
                    @click="toast.url ? window.location.href = toast.url : remove(toast.id)"
                >
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xl p-4 relative overflow-hidden">
                        <div class="flex items-start gap-4">
                            <template x-if="toast.icon">
                                <img :src="toast.icon" class="w-12 h-12 rounded-xl shadow-sm object-cover" x-show="toast.icon">
                            </template>
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-zinc-900 dark:text-white text-sm truncate" x-text="toast.title"></div>
                                <div class="text-zinc-500 dark:text-zinc-400 text-xs mt-1 line-clamp-2 leading-relaxed" x-text="toast.body"></div>
                            </div>
                            <button @click.stop="remove(toast.id)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors">
                                <flux:icon name="x-mark" variant="micro" />
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </body>
</html>
