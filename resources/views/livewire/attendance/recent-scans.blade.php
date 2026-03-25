<?php

use Livewire\Volt\Component;

new class extends Component
{
    public $scans = [];
}; ?>

<div x-show="recentScans && recentScans.length > 0" x-cloak>
    <flux:text variant="subtle" class="text-xs font-medium mb-3 uppercase tracking-wider opacity-60">
        Últimos registros
    </flux:text>
    <div class="space-y-2">
        <template x-for="(scan, index) in recentScans" :key="index">
            <div class="flex items-center justify-between px-4 py-3 rounded-2xl bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-100 dark:border-zinc-800 transition-all hover:bg-zinc-100 dark:hover:bg-zinc-800/50">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-2.5 h-2.5 rounded-full shrink-0 shadow-sm" :class="{
                        'bg-green-500': scan.color === 'green',
                        'bg-amber-500': scan.color === 'amber',
                        'bg-red-500': scan.color === 'red',
                        'bg-amber-400': scan.color === 'orange',
                        'bg-zinc-500': !scan.color
                    }"></div>
                    <span class="text-sm font-bold text-zinc-700 dark:text-zinc-200 truncate uppercase tracking-tight" x-text="scan.name"></span>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider shadow-sm border border-black/5 dark:border-white/5"
                        :class="{
                            'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400': scan.color === 'green',
                            'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400': scan.color === 'amber' || scan.color === 'orange',
                            'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400': scan.color === 'red',
                            'bg-zinc-100 text-zinc-700 dark:bg-zinc-900/40 dark:text-zinc-400': !scan.color
                        }" x-text="scan.status"></span>
                    <span class="font-mono text-[10px] text-zinc-400 font-bold" x-text="scan.time"></span>
                </div>
            </div>
        </template>
    </div>
</div>
