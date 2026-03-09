@props([
    'icon' => 'document-text',
    'title' => '',
    'description' => '',
    'name' => '',
    'tourRoute' => null,
])

<div class="flex flex-col gap-4 p-6 rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 transition-all hover:shadow-xl hover:-translate-y-1 group">
    <div class="flex items-center gap-4">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-500 group-hover:bg-zinc-200 dark:group-hover:bg-zinc-700 group-hover:text-zinc-900 dark:group-hover:text-white transition-colors shadow-inner">
            <flux:icon :icon="$icon" variant="outline" />
        </div>
        <div>
            <flux:heading size="lg">{{ $title }}</flux:heading>
        </div>
    </div>
    
    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400 line-clamp-2">
        {{ $description }}
    </flux:text>
    
    <div class="mt-auto pt-2 flex flex-col gap-2">
        <flux:button variant="primary" class="w-full" wire:click="selectTutorial('{{ $name }}')">
            Ver Guía de Texto
        </flux:button>

        @if($tourRoute)
            <flux:button 
                variant="filled" 
                class="w-full bg-indigo-50! text-indigo-600! hover:bg-indigo-100! hover:text-indigo-700! dark:bg-indigo-900/30! dark:text-indigo-400! dark:hover:bg-indigo-900/50!" 
                icon="play-circle" 
                href="{{ route($tourRoute, ['start_tour' => 1]) }}"
                wire:navigate
            >
                Guía Interactiva
            </flux:button>
        @endif
    </div>
</div>
