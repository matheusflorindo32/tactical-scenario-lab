@props([
    'variant' => 'neutral', // neutral | navy | emergency | clinical | alert
    'size'    => 'md',      // sm | md
    'dot'     => false,
])

@php
$variants = [
    'neutral'   => 'bg-stone-100 text-ink-700 ring-stone-200',
    'navy'      => 'bg-navy-50 text-navy-800 ring-navy-100',
    'emergency' => 'bg-emergency-50 text-emergency-700 ring-emergency-100',
    'clinical'  => 'bg-clinical-50 text-clinical-700 ring-clinical-100',
    'alert'     => 'bg-alert-50 text-alert-700 ring-alert-100',
];

$dots = [
    'neutral'   => 'bg-ink-500',
    'navy'      => 'bg-navy-500',
    'emergency' => 'bg-emergency-500',
    'clinical'  => 'bg-clinical-500',
    'alert'     => 'bg-alert-500',
];

$sizes = [
    'sm' => 'text-[10px] px-2 py-0.5 tracking-[0.08em]',
    'md' => 'text-xs px-2.5 py-1 tracking-[0.06em]',
];
@endphp

<span {{ $attributes->merge(['class' =>
    'inline-flex items-center gap-1.5 rounded-full font-semibold uppercase ring-1 ring-inset '
    . ($variants[$variant] ?? $variants['neutral']) . ' '
    . ($sizes[$size] ?? $sizes['md']) ]) }}>
    @if ($dot)
        <span class="h-1.5 w-1.5 rounded-full {{ $dots[$variant] ?? $dots['neutral'] }}" aria-hidden="true"></span>
    @endif
    {{ $slot }}
</span>
