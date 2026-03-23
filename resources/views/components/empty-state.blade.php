@props([
    'icon' => 'inbox',
    'heading' => '',
    'description' => '',
])

<div {{ $attributes->merge(['class' => 'py-12 text-center rounded-xl']) }}>
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-300 dark:text-zinc-600 mb-4">
        <flux:icon :icon="$icon" size="xl" />
    </div>
    @if($heading)
        <flux:heading size="md" class="text-zinc-400 mb-1">{{ $heading }}</flux:heading>
    @endif
    @if($description)
        <flux:text class="text-zinc-500">{{ $description }}</flux:text>
    @endif
    {{ $slot }}
</div>
