@props([
    'title',
    'metadata' => null,
    'variant' => 'navy',
    'href' => null,
])

@php
$variantClasses = [
    'navy' => 'border-navy-200 bg-navy-50/60 before:bg-navy-700',
    'emergency' => 'border-emergency-100 bg-emergency-50 before:bg-emergency-500',
    'clinical' => 'border-clinical-100 bg-clinical-50 before:bg-clinical-500',
    'alert' => 'border-alert-100 bg-alert-50 before:bg-alert-500',
];
$classes = $variantClasses[$variant] ?? $variantClasses['navy'];
$tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    data-variant="{{ $variant }}"
    {{ $attributes->merge(['class' => "relative block overflow-hidden rounded-lg border p-4 pl-5 before:absolute before:inset-y-0 before:left-0 before:w-1 {$classes} ".($href ? 'transition-shadow hover:shadow-sm' : '')]) }}
>
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-navy-950">{{ $title }}</p>
            @if ($metadata)
                <p class="mt-1 text-xs font-medium text-ink-500">{{ $metadata }}</p>
            @endif
            @if (trim((string) $slot) !== '')
                <div class="mt-2 text-sm leading-6 text-ink-700">{{ $slot }}</div>
            @endif
        </div>
        @if ($href)
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 h-4 w-4 shrink-0 text-ink-500" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
        @endif
    </div>
</{{ $tag }}>
