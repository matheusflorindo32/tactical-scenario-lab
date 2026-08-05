@props(['value' => 0, 'max' => 100, 'label' => null, 'variant' => 'navy'])

@php
$pct = max(0, min(100, ($value / max(1, $max)) * 100));
$colors = [
    'navy'      => 'bg-navy-600',
    'clinical'  => 'bg-clinical-500',
    'emergency' => 'bg-emergency-500',
    'alert'     => 'bg-alert-500',
];
$bar = $colors[$variant] ?? $colors['navy'];
@endphp

<div {{ $attributes }}>
    @if ($label)
        <div class="mb-1.5 flex items-center justify-between text-xs">
            <span class="font-medium text-ink-700">{{ $label }}</span>
            <span class="font-mono text-ink-500">{{ round($pct) }}%</span>
        </div>
    @endif
    <div class="h-1.5 w-full overflow-hidden rounded-full bg-stone-100" role="progressbar" aria-valuenow="{{ round($pct) }}" aria-valuemin="0" aria-valuemax="100">
        <div class="h-full {{ $bar }} transition-all" style="width: {{ $pct }}%"></div>
    </div>
</div>
