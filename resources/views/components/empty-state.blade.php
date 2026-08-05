@props([
    'title'       => 'Nada por aqui ainda',
    'description' => null,
    'icon'        => 'file',
])

@php
$icons = [
    'file'    => 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6',
    'search'  => 'M11 4a7 7 0 1 1 0 14 7 7 0 0 1 0-14zm10 17-5.2-5.2',
    'clip'    => 'M9 5h6a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2zm3-2v4',
    'alert'   => 'M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z',
];
$d = $icons[$icon] ?? $icons['file'];
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-stone-200 bg-stone-25 px-6 py-14 text-center']) }}>
    <span class="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-full bg-white ring-1 ring-stone-200">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6 text-navy-600"><path d="{{ $d }}"/></svg>
    </span>
    <h3 class="text-base font-semibold text-navy-900">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1.5 max-w-sm text-sm text-ink-500">{{ $description }}</p>
    @endif
    @isset($actions)
        <div class="mt-5 flex items-center justify-center gap-2">{{ $actions }}</div>
    @endisset
</div>
