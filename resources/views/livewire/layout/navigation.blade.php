<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component {
    public int $reportsCount = 0;
    public int $servicesCount = 0;
    public int $noticesCount = 0;
    public int $citationsCount = 0;
    public int $totalPending = 0;
    public bool $isViewParent = false;
    public bool $isViewStaff = false;

    public function mount(): void
    {
        $this->refreshCounts();
    }

    #[On('navigation-refresh')]
    public function refreshCounts(): void
    {
        $user = auth()->user();
        $this->isViewParent = $user->isViewParent();
        $this->isViewStaff = $user->isViewStaff();

        if ($this->isViewParent) {
            $this->reportsCount = $user->getUnsignedReportsCount();
            $this->servicesCount = $user->getUnsignedCommunityServicesCount();
            $this->noticesCount = $user->getUnsignedNoticesCount();
            $this->citationsCount = $user->getUnsignedCitationsCount();
            $this->totalPending = $this->reportsCount + $this->servicesCount + $this->noticesCount + $this->citationsCount;
        } else {
            $this->reportsCount = 0;
            $this->servicesCount = 0;
            $this->noticesCount = 0;
            $this->citationsCount = 0;
            $this->totalPending = 0;
        }
    }
}; ?>

<flux:navlist variant="outline">
    <flux:navlist.group heading="{{ $isViewParent ? 'Mis Hijos' : 'Plataforma' }}" class="grid gap-y-0">
        <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" class="py-1.5">Tablero</flux:navlist.item>
        
        @if($isViewStaff)
            @can('admin-only')
                <flux:navlist.item href="{{ route('cycles.index') }}" icon="academic-cap" class="py-1.5">Ciclos Escolares</flux:navlist.item>
            @endcan
        @endif

        <flux:navlist.item icon="user-group" href="{{ route('students.index') }}" :current="request()->routeIs('students.index')" class="py-1.5">
            {{ $isViewParent ? 'Ficha de Alumnos' : 'Alumnos' }}
        </flux:navlist.item>

        @if($isViewStaff)
            @can('admin-only')
                <flux:navlist.item icon="arrow-path-rounded-square" href="{{ route('students.promote') }}" :current="request()->routeIs('students.promote')" class="py-1.5">Promover Alumnos</flux:navlist.item>
            @endcan
        @endif

        <flux:navlist.item icon="document-text" href="{{ route('reports.index') }}" :current="request()->routeIs('reports.index')" :badge="$isViewParent && $reportsCount > 0 ? $reportsCount : null" badge:color="red" class="py-1.5">
            Reportes
        </flux:navlist.item>

        <flux:navlist.item icon="briefcase" href="{{ route('community-services.index') }}" :current="request()->routeIs('community-services.index')" :badge="$isViewParent && $servicesCount > 0 ? $servicesCount : null" badge:color="red" class="py-1.5">
            Servicio Comunitario
        </flux:navlist.item>

        <flux:navlist.item icon="megaphone" href="{{ route('notices.index') }}" :current="request()->routeIs('notices.index')" :badge="$isViewParent && $noticesCount > 0 ? $noticesCount : null" badge:color="red" class="py-1.5">
            Avisos
        </flux:navlist.item>

        <flux:navlist.item icon="calendar-days" href="{{ route('citations.index') }}" :current="request()->routeIs('citations.index')" :badge="$isViewParent && $citationsCount > 0 ? $citationsCount : null" badge:color="red" class="py-1.5">
            Citatorios
        </flux:navlist.item>

        <flux:navlist.item icon="academic-cap" href="{{ route('exams.index') }}" class="py-1.5">{{ __('Exámenes') }}</flux:navlist.item>

        @if($isViewStaff)
            @can('teacher-or-admin')
                 <flux:navlist.item icon="clock" href="{{ route('attendance.index') }}" :current="request()->routeIs('attendance.*')" class="py-1.5">Asistencia</flux:navlist.item>
                <flux:navlist.item icon="calendar" href="{{ route('calendar.index') }}" :current="request()->routeIs('calendar.index')" class="py-1.5">Calendario General</flux:navlist.item>
            @endcan
        @endif
        
        @if($isViewStaff)
            @can('admin-only')
                <flux:navlist.item href="{{ route('data-importer') }}" icon="document-arrow-up" :current="request()->routeIs('data-importer')" class="py-1.5">Importar Datos</flux:navlist.item>
                <flux:navlist.item href="{{ route('data-exporter') }}" icon="document-arrow-down" :current="request()->routeIs('data-exporter')" class="py-1.5">Exportar Datos</flux:navlist.item>
                <flux:navlist.item href="{{ route('users.index') }}" icon="users" :current="request()->routeIs('users.index')" class="py-1.5">Gestión de Usuarios</flux:navlist.item>
            @endcan
        @endif

        <flux:navlist.item icon="book-open" href="{{ route('regulations.index') }}" :current="request()->routeIs('regulations.index')" class="py-1.5">Reglamento</flux:navlist.item>
        <flux:navlist.item icon="question-mark-circle" href="{{ route('tutorials.index') }}" :current="request()->routeIs('tutorials.index')" class="py-1.5">Tutoriales</flux:navlist.item>
    </flux:navlist.group>
</flux:navlist>
