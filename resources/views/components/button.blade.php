@props([
    'variant' => 'primary', // primary | secondary | ghost | danger | success
    'size'    => 'md',      // sm | md | lg
    'type'    => 'button',
    'href'    => null,
    'icon'    => null,      // slot right-hand icon markup (optional)
    'block'   => false,
])

@php
$base = 'inline-flex items-center justify-center gap-2 font-semibold rounded-md transition-colors '
      . 'focus-visible:outline-none disabled:opacity-50 disabled:cursor-not-allowed select-none whitespace-nowrap';

$sizes = [
    'sm' => 'text-xs px-3 py-2 h-8',
    'md' => 'text-sm px-4 py-2.5 h-10',
    'lg' => 'text-sm px-6 py-3 h-12',
];

$variants = [
    'primary'   => 'bg-navy-900 text-white hover:bg-navy-800 active:bg-navy-950 shadow-sm',
    'secondary' => 'bg-white text-navy-900 ring-1 ring-inset ring-stone-300 hover:bg-stone-50 hover:ring-stone-400',
    'ghost'     => 'bg-transparent text-navy-800 hover:bg-stone-100',
    'danger'    => 'bg-emergency-500 text-white hover:bg-emergency-600 active:bg-emergency-700 shadow-sm',
    'success'   => 'bg-clinical-500 text-white hover:bg-clinical-600 active:bg-clinical-700 shadow-sm',
];

$classes = $base . ' ' . ($sizes[$size] ?? $sizes['md']) . ' ' . ($variants[$variant] ?? $variants['primary']);
if ($block) $classes .= ' w-full';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
