@props([
    'title'      => null,
    'subtitle'   => null,
    'accent'     => null,   // navy | emergency | clinical | alert | null
    'padding'    => 'md',   // sm | md | lg | none
    'as'         => 'section',
])

@php
$paddings = [
    'none' => '',
    'sm'   => 'p-4',
    'md'   => 'p-6',
    'lg'   => 'p-8',
];

$accents = [
    'navy'      => 'before:bg-navy-700',
    'emergency' => 'before:bg-emergency-500',
    'clinical'  => 'before:bg-clinical-500',
    'alert'     => 'before:bg-alert-500',
];

$accentClass = $accent
    ? "relative before:absolute before:left-0 before:top-4 before:bottom-4 before:w-[3px] before:rounded-full {$accents[$accent]}"
    : '';
@endphp

<{{ $as }} {{ $attributes->merge(['class' => "bg-white ring-1 ring-stone-200 rounded-lg shadow-xs {$accentClass}"]) }}>
    @if ($title || $subtitle || isset($actions))
        <header class="flex items-start justify-between gap-4 border-b border-stone-100 px-6 pt-5 pb-4">
            <div class="min-w-0">
                @if ($title)
                    <h2 class="text-base font-semibold text-navy-900">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="mt-1 text-sm text-ink-500">{{ $subtitle }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif
    <div class="{{ $paddings[$padding] ?? $paddings['md'] }}">
        {{ $slot }}
    </div>
</{{ $as }}>
