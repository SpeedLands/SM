@props([
    'class' => '',
])

<div x-data="{ showFilters: false }" {{ $attributes->merge(['class' => $class]) }}>
    {{-- Mobile Filter Pills --}}
    <div class="flex flex-wrap gap-2 sm:hidden pb-2 overflow-x-auto no-scrollbar">
        {{ $pills ?? '' }}
        <flux:button variant="ghost" size="xs" icon="funnel" class="ml-auto" title="Mostrar/ocultar filtros" x-on:click="showFilters = !showFilters" />
    </div>

    {{-- Desktop / Collapsible Filter Panel --}}
    <div x-show="showFilters" class="sm:block! p-6 rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 shadow-sm space-y-6 transition-all">
        {{ $slot }}
    </div>
</div>
