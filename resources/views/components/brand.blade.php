@props(['inverse' => false, 'variant' => 'full'])

@php
$textClass = $inverse ? 'text-white' : 'text-navy-900';
$subClass  = $inverse ? 'text-navy-200' : 'text-ink-500';
@endphp

<a href="{{ route('scenarios.index') }}" class="inline-flex items-center gap-3 group" aria-label="Tactical Scenario Lab — início">
    <span class="relative inline-flex h-10 w-10 items-center justify-center rounded-md bg-navy-900 text-white shadow-sm ring-1 ring-navy-800/40">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" class="h-5 w-5" aria-hidden="true">
            <path d="M12 3v18M3 12h18" />
            <circle cx="12" cy="12" r="7" />
        </svg>
        <span class="absolute -bottom-0.5 -right-0.5 h-2 w-2 rounded-full bg-emergency-500 ring-2 ring-white"></span>
    </span>
    @if ($variant === 'full')
        <span class="flex flex-col leading-none">
            <span class="text-[13px] font-semibold uppercase tracking-[0.16em] {{ $textClass }}">Tactical Scenario Lab</span>
            <span class="mt-1 text-[11px] font-medium uppercase tracking-[0.2em] {{ $subClass }}">Simulação · APH · Debrief</span>
        </span>
    @endif
</a>
