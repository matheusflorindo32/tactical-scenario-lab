@props(['steps' => [], 'current' => 1])

<nav aria-label="Progresso do wizard">
    <ol class="flex items-center gap-1 overflow-x-auto pb-1">
        @foreach ($steps as $index => $label)
            @php
                $n = $index + 1;
                $isDone   = $n < $current;
                $isActive = $n === $current;
                $circle   = $isActive
                    ? 'bg-navy-900 text-white ring-4 ring-navy-100'
                    : ($isDone
                        ? 'bg-clinical-500 text-white'
                        : 'bg-stone-100 text-ink-500 ring-1 ring-stone-200');
                $textCls  = $isActive
                    ? 'text-navy-900 font-semibold'
                    : ($isDone ? 'text-ink-500' : 'text-ink-300');
            @endphp
            <li class="flex min-w-0 flex-1 items-center gap-3">
                <button
                    type="button"
                    @if(isset($clickable) && $clickable) x-on:click="goTo({{ $n }})" @else disabled @endif
                    class="group flex min-w-0 items-center gap-2.5 rounded-md py-1.5 pl-1 pr-2 focus-visible:outline-none"
                    aria-current="{{ $isActive ? 'step' : 'false' }}"
                >
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold {{ $circle }}">
                        @if ($isDone)
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="h-3.5 w-3.5"><path d="M5 13l4 4L19 7"/></svg>
                        @else
                            {{ $n }}
                        @endif
                    </span>
                    <span class="truncate text-[11px] font-semibold uppercase tracking-[0.1em] {{ $textCls }}">{{ $label }}</span>
                </button>
                @if (! $loop->last)
                    <span class="h-px flex-1 {{ $isDone ? 'bg-clinical-500' : 'bg-stone-200' }}"></span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
