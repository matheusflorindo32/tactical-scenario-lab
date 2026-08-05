@props(['align' => 'right', 'width' => '56'])

@php
$alignment = $align === 'left' ? 'origin-top-left left-0' : 'origin-top-right right-0';
$widths = ['48' => 'w-48', '56' => 'w-56', '64' => 'w-64'];
@endphp

<div x-data="{ open: false }" x-on:click.outside="open = false" class="relative inline-block text-left">
    <div x-on:click="open = ! open" x-on:keydown.escape.window="open = false">
        {{ $trigger }}
    </div>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        x-cloak
        class="{{ $alignment }} {{ $widths[$width] ?? 'w-56' }} absolute z-40 mt-2 rounded-md bg-white shadow-md ring-1 ring-stone-200 focus:outline-none"
        role="menu"
    >
        <div class="py-1 text-sm">{{ $content }}</div>
    </div>
</div>
