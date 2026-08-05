@props(['name', 'title' => null, 'maxWidth' => 'lg'])

@php
$widths = [
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
    '2xl' => 'max-w-2xl',
];
@endphp

<div
    x-data="{ open: false }"
    x-on:open-modal-{{ $name }}.window="open = true"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    @if($title) aria-label="{{ $title }}" @endif
>
    <div x-show="open" x-transition.opacity class="absolute inset-0 bg-navy-950/60 backdrop-blur-sm" x-on:click="open = false"></div>

    <div
        x-show="open"
        x-transition
        x-trap.inert.noscroll="open"
        class="relative w-full {{ $widths[$maxWidth] ?? $widths['lg'] }} rounded-lg bg-white shadow-lg ring-1 ring-stone-200"
    >
        @if ($title)
            <header class="flex items-center justify-between border-b border-stone-100 px-5 py-4">
                <h2 class="text-base font-semibold text-navy-900">{{ $title }}</h2>
                <button type="button" x-on:click="open = false" class="rounded-md p-1 text-ink-500 hover:bg-stone-100 hover:text-ink-900" aria-label="Fechar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path d="M6 6l12 12M6 18L18 6"/></svg>
                </button>
            </header>
        @endif
        <div class="px-5 py-4">
            {{ $slot }}
        </div>
        @isset($footer)
            <footer class="flex items-center justify-end gap-2 border-t border-stone-100 bg-stone-25 px-5 py-3 rounded-b-lg">
                {{ $footer }}
            </footer>
        @endisset
    </div>
</div>
