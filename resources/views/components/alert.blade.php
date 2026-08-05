@props([
    'variant' => 'info', // info | success | warning | danger
    'title'   => null,
])

@php
$map = [
    'info'    => ['bg' => 'bg-navy-50',      'ring' => 'ring-navy-100',      'title' => 'text-navy-900',      'text' => 'text-navy-800',      'icon' => 'i-info',    'iconColor' => 'text-navy-600'],
    'success' => ['bg' => 'bg-clinical-50',  'ring' => 'ring-clinical-100',  'title' => 'text-clinical-700', 'text' => 'text-clinical-700',  'icon' => 'i-check',   'iconColor' => 'text-clinical-500'],
    'warning' => ['bg' => 'bg-alert-50',     'ring' => 'ring-alert-100',     'title' => 'text-alert-700',    'text' => 'text-alert-700',     'icon' => 'i-warn',    'iconColor' => 'text-alert-500'],
    'danger'  => ['bg' => 'bg-emergency-50', 'ring' => 'ring-emergency-100', 'title' => 'text-emergency-700','text' => 'text-emergency-700','icon' => 'i-alert',    'iconColor' => 'text-emergency-500'],
];
$c = $map[$variant] ?? $map['info'];

$paths = [
    'i-info'  => 'M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z',
    'i-check' => 'M5 13l4 4L19 7',
    'i-warn'  => 'M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z',
    'i-alert' => 'M12 8v4m0 4h.01M4.93 4.93l14.14 14.14',
];
@endphp

<div role="alert" {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-md p-4 ring-1 ring-inset {$c['bg']} {$c['ring']}"]) }}>
    <svg class="h-5 w-5 shrink-0 {{ $c['iconColor'] }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="{{ $paths[$c['icon']] }}"/>
    </svg>
    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="text-sm font-semibold {{ $c['title'] }}">{{ $title }}</p>
        @endif
        <div class="text-sm {{ $c['text'] }} @if($title) mt-0.5 @endif">{{ $slot }}</div>
    </div>
</div>
