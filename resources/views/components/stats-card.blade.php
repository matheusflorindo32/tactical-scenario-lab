@props([
    'label'   => '',
    'value'   => '—',
    'hint'    => null,
    'trend'   => null,  // 'up' | 'down' | null
    'icon'    => null,  // svg path (24x24)
    'accent'  => 'navy', // navy | clinical | emergency | alert
])

@php
$accents = [
    'navy'      => 'text-navy-700 bg-navy-50 ring-navy-100',
    'clinical'  => 'text-clinical-700 bg-clinical-50 ring-clinical-100',
    'emergency' => 'text-emergency-700 bg-emergency-50 ring-emergency-100',
    'alert'     => 'text-alert-700 bg-alert-50 ring-alert-100',
];
$a = $accents[$accent] ?? $accents['navy'];
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col justify-between rounded-lg bg-white p-5 ring-1 ring-stone-200 shadow-xs']) }}>
    <div class="flex items-center justify-between">
        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-ink-500">{{ $label }}</p>
        @if ($icon)
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-md ring-1 ring-inset {{ $a }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="{{ $icon }}"/></svg>
            </span>
        @endif
    </div>
    <div class="mt-4">
        <p class="font-display text-3xl font-semibold tracking-tight text-navy-900 tabular-nums">{{ $value }}</p>
        @if ($hint)
            <p class="mt-1 flex items-center gap-1 text-xs text-ink-500">
                @if ($trend === 'up')
                    <svg class="h-3.5 w-3.5 text-clinical-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17l10-10M7 7h10v10"/></svg>
                @elseif ($trend === 'down')
                    <svg class="h-3.5 w-3.5 text-emergency-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 7l10 10M7 17V7h10"/></svg>
                @endif
                <span>{{ $hint }}</span>
            </p>
        @endif
    </div>
</div>
