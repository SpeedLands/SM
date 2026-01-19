<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group heading="Plataforma" class="grid">
                    <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>Tablero</flux:navlist.item>
                    
                    @can('admin-only')
                        <flux:navlist.item href="{{ route('cycles.index') }}" wire:navigate icon="academic-cap">Ciclos Escolares</flux:navlist.item>
                    @endcan

                    <flux:navlist.item icon="user-group" href="{{ route('students.index') }}" :current="request()->routeIs('students.index')">Alumnos</flux:navlist.item>
                    <flux:navlist.item icon="document-text" href="{{ route('reports.index') }}" :current="request()->routeIs('reports.index')">Reportes</flux:navlist.item>
                    <flux:navlist.item icon="briefcase" href="{{ route('community-services.index') }}" :current="request()->routeIs('community-services.index')">Servicio Comunitario</flux:navlist.item>
                    <flux:navlist.item icon="megaphone" href="{{ route('notices.index') }}" :current="request()->routeIs('notices.index')">Avisos</flux:navlist.item>
                    <flux:navlist.item icon="calendar-days" href="{{ route('citations.index') }}" :current="request()->routeIs('citations.index')">Citatorios</flux:navlist.item>
                    <flux:navlist.item icon="academic-cap" href="{{ route('exams.index') }}" wire:navigate>{{ __('Exámenes') }}</flux:navlist.item>
                    
                    @can('admin-only')
                        <flux:navlist.item href="{{ route('users.index') }}" wire:navigate icon="users">Gestión de Usuarios</flux:navlist.item>
                    @endcan

                    <flux:navlist.item icon="book-open" href="{{ route('regulations.index') }}" :current="request()->routeIs('regulations.index')">Reglamento</flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>

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

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                @include('partials.app.user-menu')
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
