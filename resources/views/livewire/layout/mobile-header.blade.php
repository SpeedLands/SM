<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;

new class extends Component {
    public int $totalPending = 0;
    public bool $isViewParent = false;
    public bool $hasDualRole = false;

    public function mount(): void
    {
        $this->refreshCounts();
    }

    #[On('navigation-refresh')]
    public function refreshCounts(): void
    {
        $user = auth()->user();
        $this->isViewParent = $user->isViewParent();
        $this->hasDualRole = ($user->isAdmin() || $user->isTeacher()) && $user->hasStudents();

        if ($this->isViewParent) {
            $this->totalPending = $user->getUnsignedReportsCount() + 
                                 $user->getUnsignedCommunityServicesCount() + 
                                 $user->getUnsignedNoticesCount() + 
                                 $user->getUnsignedCitationsCount();
        } else {
            $this->totalPending = 0;
        }
    }
}; ?>

<flux:header class="lg:hidden">
    <div class="relative">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
        @if($isViewParent && $totalPending > 0)
            <div class="absolute top-1 right-1 flex h-2.5 w-2.5 rounded-full bg-red-500 border-2 border-white dark:border-zinc-800 shadow-xs shadow-red-500/50"></div>
        @endif
    </div>

    @if($hasDualRole)
        <form action="{{ route('toggle-view') }}" method="POST" class="ms-2">
            @csrf
            <flux:button type="submit" variant="subtle" size="sm" icon="{{ $isViewParent ? 'briefcase' : 'user-group' }}" class="text-[10px] font-black uppercase tracking-widest text-zinc-500">
                <span class="hidden sm:inline">{{ $isViewParent ? 'Staff' : 'Hijos' }}</span>
            </flux:button>
        </form>
    @endif

    <flux:spacer />

    <flux:dropdown position="top" align="end">
        <div class="relative">
            <flux:profile
                :initials="auth()->user()->initials()"
                icon-trailing="chevron-down"
            />
        </div>

        @include('partials.app.user-menu')
    </flux:dropdown>
</flux:header>
