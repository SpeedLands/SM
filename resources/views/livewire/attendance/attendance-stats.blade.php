<?php
use Livewire\Volt\Component;

new class extends Component {
    public $presentes = 0;
    public $faltas = 0;
    public $retardos = 0;
    public $pendientes = 0;
}; ?>

<div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
    <div class="bg-green-500/10 dark:bg-green-950/30 border border-green-200 dark:border-green-800 rounded-2xl p-4 flex flex-col gap-1 transition-all">
        <div class="flex items-center gap-2 mb-1">
            <div class="w-6 h-6 bg-green-500 rounded flex items-center justify-center">
                <flux:icon.check class="w-3.5 h-3.5 text-white" />
            </div>
            <span class="text-xs text-green-700 dark:text-green-400 font-medium">Presentes</span>
        </div>
        <p class="text-3xl font-bold text-green-600 dark:text-green-500">{{ $presentes }}</p>
    </div>
    <div class="bg-red-500/10 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-2xl p-4 flex flex-col gap-1">
        <div class="flex items-center gap-2 mb-1">
            <div class="w-6 h-6 bg-red-500 rounded flex items-center justify-center">
                <flux:icon.x-mark class="w-3.5 h-3.5 text-white" />
            </div>
            <span class="text-xs text-red-700 dark:text-red-400 font-medium">Faltas</span>
        </div>
        <p class="text-3xl font-bold text-red-600 dark:text-red-500">{{ $faltas }}</p>
    </div>
    <div class="bg-amber-500/10 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-2xl p-4 flex flex-col gap-1">
        <div class="flex items-center gap-2 mb-1">
            <div class="w-6 h-6 bg-amber-500 rounded flex items-center justify-center">
                <flux:icon.clock class="w-3.5 h-3.5 text-white" />
            </div>
            <span class="text-xs text-amber-700 dark:text-amber-400 font-medium">Retardos</span>
        </div>
        <p class="text-3xl font-bold text-amber-600 dark:text-amber-500">{{ $retardos }}</p>
    </div>
    <div class="bg-zinc-500/10 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-4 flex flex-col gap-1">
        <div class="flex items-center gap-2 mb-1">
            <div class="w-6 h-6 bg-zinc-400 rounded flex items-center justify-center">
                <flux:icon.ellipsis-horizontal class="w-3.5 h-3.5 text-white" />
            </div>
            <span class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">Pendientes</span>
        </div>
        <p class="text-3xl font-bold text-zinc-600 dark:text-zinc-300">{{ $pendientes }}</p>
    </div>
</div>
