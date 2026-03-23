<?php

use Livewire\Volt\Component;

new class extends Component
{
    public $lastStudent = null;
    public $statusMessage = '';
    public $lastStatus = '';
    public $lastEntryTime = '';
}; ?>

<div x-show="lastStudent || (statusMessage && lastStatus === 'error')" x-cloak
    class="rounded-3xl p-6 border-2 transition-all duration-500 shadow-lg backdrop-blur-sm"
    style="background-color: var(--{{ $lastStatus === 'success' ? 'green' : ($lastStatus === 'error' ? 'red' : 'amber') }}-50, #{{ match($lastStatus) { 'success' => 'f0fdf4', 'error' => 'fef2f2', default => 'fffbeb' } }}); 
           border-color: var(--{{ $lastStatus === 'success' ? 'green' : ($lastStatus === 'error' ? 'red' : 'amber') }}-200, #{{ match($lastStatus) { 'success' => 'bbfc7', 'error' => 'fecaca', default => 'fef3c7' } }});"
    :class="{
        'dark:bg-green-950/20 dark:border-green-800/50': lastStatus === 'success',
        'dark:bg-amber-950/20 dark:border-amber-800/50': lastStatus === 'retardo' || lastStatus === 'duplicate',
        'dark:bg-red-950/20 dark:border-red-800/50': lastStatus === 'error',
    }">
    @if($lastStudent || ($statusMessage && $lastStatus === 'error') || $lastStatus === 'duplicate')
    <div class="flex items-center gap-6">
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center shrink-0 shadow-inner"
            :class="{
                'bg-green-100 dark:bg-green-900/60': lastStatus === 'success',
                'bg-amber-100 dark:bg-amber-900/60': lastStatus === 'retardo' || lastStatus === 'duplicate',
                'bg-red-100 dark:bg-red-900/60': lastStatus === 'error',
            }">
            <template x-if="lastStatus === 'success'"><flux:icon name="check-circle" variant="solid" class="w-10 h-10 text-green-600 dark:text-green-400" /></template>
            <template x-if="lastStatus === 'retardo'"><flux:icon name="clock" variant="solid" class="w-10 h-10 text-amber-600 dark:text-amber-400" /></template>
            <template x-if="lastStatus === 'duplicate'"><flux:icon name="information-circle" variant="solid" class="w-10 h-10 text-amber-600 dark:text-amber-400" /></template>
            <template x-if="lastStatus === 'error'"><flux:icon name="x-circle" variant="solid" class="w-10 h-10 text-red-600 dark:text-red-400" /></template>
        </div>

        <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2 mb-2">
                <span class="inline-flex items-center px-2 py-1 rounded-lg text-[10px] font-black text-white uppercase tracking-widest shadow-sm"
                    :class="{
                        'bg-green-500': lastStatus === 'success',
                        'bg-amber-500': lastStatus === 'retardo' || lastStatus === 'duplicate',
                        'bg-red-500': lastStatus === 'error',
                    }" x-text="statusMessage">{{ $statusMessage }}</span>
                <span x-show="lastEntryTime" class="font-mono text-2xl font-black tracking-tighter"
                    :class="{
                        'text-green-700 dark:text-green-300': lastStatus === 'success',
                        'text-amber-700 dark:text-amber-300': lastStatus === 'retardo' || lastStatus === 'duplicate',
                        'text-red-700 dark:text-red-300': lastStatus === 'error',
                    }" x-text="lastEntryTime">{{ $lastEntryTime }}</span>
            </div>

            <template x-if="lastStudent">
                <div class="space-y-1">
                    <p class="font-black text-lg text-zinc-900 dark:text-white truncate uppercase leading-none tracking-tight" x-text="lastStudent.name"></p>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-zinc-200/50 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 uppercase" x-text="lastStudent.grade + ' ' + (lastStudent.group_name || '')"></span>
                        <div class="w-1 h-1 rounded-full bg-zinc-300 dark:bg-zinc-700"></div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-zinc-200/50 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 uppercase" x-text="lastStudent.turn"></span>
                    </div>
                </div>
            </template>
            <template x-if="lastStatus === 'error' && !lastStudent">
                <p class="text-sm font-bold text-red-600/80 dark:text-red-400/80">No se pudo identificar al alumno en la base de datos local o remota.</p>
            </template>
        </div>
    </div>
    @endif
</div>
