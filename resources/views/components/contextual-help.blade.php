@props([
    'slug',
    'label' => 'Como usar esta tela',
])

<a
    href="{{ route('knowledge.show', $slug) }}"
    {{ $attributes->class([
        'inline-flex min-h-11 items-center gap-2 rounded-md border border-navy-200 bg-white px-3 py-2 text-sm font-semibold text-navy-700 shadow-xs transition',
        'hover:border-navy-500 hover:text-navy-950 focus-visible:ring-2 focus-visible:ring-navy-500 focus-visible:ring-offset-2',
    ]) }}
>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4" aria-hidden="true">
        <path d="M4 5.5A2.5 2.5 0 016.5 3H11a3 3 0 013 3v15a3 3 0 00-3-3H6.5A2.5 2.5 0 004 20.5v-15zM20 5.5A2.5 2.5 0 0017.5 3H14v15h3.5a2.5 2.5 0 012.5 2.5v-15z"/>
    </svg>
    <span>{{ $label }}</span>
</a>
