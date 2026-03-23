<?php

use Livewire\Volt\Component;

new class extends Component
{
    public $scans = [];
}; ?>

<div x-show="recentScans.length > 0" x-cloak>
    <flux:text variant="subtle" class="text-xs font-medium mb-3 uppercase tracking-wider opacity-60">
        Últimos registros
    </flux:text>
    <div class="space-y-2">
        @foreach($scans as $scan)
            <div wire:key="scan-{{ $loop->index }}"
                class="flex items-center justify-between px-4 py-3 rounded-2xl bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-100 dark:border-zinc-800 transition-all hover:bg-zinc-100 dark:hover:bg-zinc-800/50">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-2.5 h-2.5 rounded-full shrink-0 shadow-sm" style="background-color: var(--{{ $scan['color'] }}-500, #{{ match($scan['color'] ?? 'zinc') {
                        'green' => '22c55e',
                        'amber' => 'f59e0b',
                        'red' => 'ef4444',
                        default => '71717a'
                    } }})"></div>
                    <span class="text-sm font-bold text-zinc-700 dark:text-zinc-200 truncate uppercase tracking-tight">{{ $scan['name'] }}</span>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <flux:badge size="xs" variant="solid" 
                        color="{{ match($scan['color'] ?? 'zinc') {
                            'green' => 'green',
                            'amber' => 'amber',
                            'red' => 'red',
                            default => 'zinc'
                        } }}">
                        {{ $scan['status'] }}
                    </flux:badge>
                    <span class="font-mono text-[10px] text-zinc-400 font-bold">{{ $scan['time'] }}</span>
                </div>
            </div>
        @endforeach
    </div>
</div>
