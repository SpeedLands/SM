<flux:menu class="w-55">
    <flux:menu.radio.group>
        <div class="p-0 text-sm font-normal">
            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                    <span
                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                    >
                        {{ auth()->user()->initials() }}
                    </span>
                </span>

                <div class="grid flex-1 text-start text-sm leading-tight">
                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                </div>
            </div>
        </div>
    </flux:menu.radio.group>

    <flux:menu.separator />

    <flux:menu.radio.group>
        @php
            $isParentView = auth()->user()->isViewParent();
            $settingsLabel = $isParentView ? __('Appearance') : __('Settings');
            $settingsRoute = $isParentView ? route('appearance.edit') : (auth()->user()->isAdmin() ? route('profile.edit') : route('appearance.edit'));
            $settingsIcon = $isParentView ? 'paint-brush' : 'cog';
        @endphp
        <flux:menu.item :href="$settingsRoute" :icon="$settingsIcon" wire:navigate>{{ $settingsLabel }}</flux:menu.item>
    </flux:menu.radio.group>

    <flux:menu.separator />

    <form method="POST" action="{{ route('logout') }}" id="logout-form" class="w-full">
        @csrf
        <flux:menu.item 
            as="button" 
            type="submit" 
            icon="arrow-right-start-on-rectangle" 
            class="w-full" 
            data-test="logout-button"
            onclick="handleLogout(event)"
        >
            {{ __('Log Out') }}
        </flux:menu.item>
    </form>

    <script>
        function handleLogout(event) {
            event.preventDefault();
            
            // Check if online
            if (!navigator.onLine) {
                alert('Estás desconectado. Se cerrará la sesión localmente, pero reconéctate para asegurar el cierre en el servidor.');
            }

            // Tell Service Worker to clear cache
            if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage({ type: 'CLEAR_CACHE' });
                
                // Give it a short moment to clear before redirecting/submitting
                setTimeout(() => {
                    document.getElementById('logout-form').submit();
                }, 300);
            } else {
                document.getElementById('logout-form').submit();
            }
        }
    </script>
</flux:menu>
