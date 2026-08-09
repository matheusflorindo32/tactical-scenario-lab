@props([
    'items' => [],
    'label' => 'Seções desta página',
])

<nav {{ $attributes->merge(['class' => 'overflow-x-auto rounded-lg border border-stone-200 bg-white p-2 shadow-xs']) }} aria-label="{{ $label }}">
    <ul class="flex min-w-max items-center gap-1">
        @foreach ($items as $item)
            @php
                $state = $item['state'] ?? null;
                $current = $state === 'current';
                $stateClass = match ($state) {
                    'current' => 'bg-navy-900 text-white',
                    'complete' => 'bg-clinical-50 text-clinical-700 hover:bg-clinical-100',
                    'attention' => 'bg-alert-50 text-alert-700 hover:bg-alert-100',
                    default => 'text-ink-700 hover:bg-stone-100 hover:text-navy-950',
                };
            @endphp
            <li>
                <a
                    href="{{ $item['href'] }}"
                    @if ($current) aria-current="location" @endif
                    class="inline-flex min-h-11 items-center rounded-md px-3 py-2 text-sm font-semibold transition-colors {{ $stateClass }}"
                >
                    {{ $item['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
