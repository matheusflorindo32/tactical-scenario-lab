@props(['score' => 0, 'label' => 'Pontuação'])

@php
$score = max(0, min(100, (int) $score));
if ($score >= 80) {
    $ring = 'text-clinical-500';
    $classification = 'Excelente';
    $badge = 'clinical';
} elseif ($score >= 60) {
    $ring = 'text-navy-500';
    $classification = 'Satisfatório';
    $badge = 'navy';
} elseif ($score >= 40) {
    $ring = 'text-alert-500';
    $classification = 'Precisa reforçar';
    $badge = 'alert';
} else {
    $ring = 'text-emergency-500';
    $classification = 'Refazer';
    $badge = 'emergency';
}
$c = 2 * M_PI * 40;                 // circunferência
$offset = $c - ($score / 100) * $c;
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-5']) }}>
    <div class="relative h-24 w-24">
        <svg viewBox="0 0 100 100" class="h-full w-full -rotate-90">
            <circle cx="50" cy="50" r="40" stroke="currentColor" stroke-width="8" fill="none" class="text-stone-100"/>
            <circle cx="50" cy="50" r="40" stroke="currentColor" stroke-width="8" fill="none"
                    stroke-linecap="round" class="{{ $ring }} transition-all duration-500"
                    stroke-dasharray="{{ $c }}" stroke-dashoffset="{{ $offset }}"/>
        </svg>
        <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="font-display text-2xl font-semibold text-navy-900 tabular-nums">{{ $score }}</span>
            <span class="text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-500">/100</span>
        </div>
    </div>
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-500">{{ $label }}</p>
        <x-badge :variant="$badge" size="md" class="mt-1.5">{{ $classification }}</x-badge>
    </div>
</div>
