@props(['items' => []])

<nav aria-label="Trilha de navegação" {{ $attributes }}>
    <ol class="flex flex-wrap items-center gap-1.5 text-xs text-ink-500">
        @foreach ($items as $item)
            <li class="flex items-center gap-1.5">
                @if (! $loop->first)
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3 w-3 text-ink-300"><path d="M9 5l7 7-7 7"/></svg>
                @endif
                @if ($loop->last)
                    <span class="font-semibold text-navy-900" aria-current="page">{{ $item['label'] }}</span>
                @elseif (! empty($item['href']))
                    <a href="{{ $item['href'] }}" class="hover:text-navy-700">{{ $item['label'] }}</a>
                @else
                    <span>{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
