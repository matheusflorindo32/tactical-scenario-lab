@props(['items' => [], 'variant' => 'neutral'])

@php
$dotColors = [
    'neutral'   => 'text-navy-700',
    'clinical'  => 'text-clinical-600',
    'emergency' => 'text-emergency-600',
    'alert'     => 'text-alert-600',
];
$dot = $dotColors[$variant] ?? $dotColors['neutral'];
@endphp

<ul {{ $attributes->merge(['class' => 'space-y-2.5']) }}>
    @foreach ($items as $item)
        <li class="flex items-start gap-3">
            <svg class="mt-0.5 h-4 w-4 shrink-0 {{ $dot }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 13l4 4L19 7"/>
            </svg>
            <span class="text-sm leading-relaxed text-ink-700">{{ $item }}</span>
        </li>
    @endforeach
</ul>
