@props(['current' => null])

@php
$sections = [
    [
        'label' => 'Operação',
        'items' => [
            ['label' => 'Painel',    'route' => 'scenarios.index', 'key' => 'dashboard',  'icon' => 'M3 12l9-9 9 9M5 10v10h14V10'],
            ['label' => 'Cenários',  'route' => 'scenarios.index', 'key' => 'scenarios',  'icon' => 'M4 6h16M4 12h16M4 18h10'],
            ['label' => 'Novo',      'route' => 'scenarios.create','key' => 'new',        'icon' => 'M12 5v14M5 12h14'],
        ],
    ],
    [
        'label' => 'Documentação',
        'items' => [
            ['label' => 'Guias',     'href'  => '#',               'key' => 'guides',     'icon' => 'M4 4h16v16H4zM4 9h16M9 4v16'],
            ['label' => 'Referência','href'  => '#',               'key' => 'reference',  'icon' => 'M12 4v16m8-8H4'],
        ],
    ],
];
@endphp

<aside
    x-data="{ open: false }"
    x-on:toggle-sidebar.window="open = ! open"
    x-on:keydown.escape.window="open = false"
    class="fixed inset-y-0 left-0 z-40 w-72 -translate-x-full transform border-r border-stone-200 bg-white transition-transform lg:sticky lg:top-16 lg:h-[calc(100vh-4rem)] lg:translate-x-0"
    :class="{ 'translate-x-0': open }"
    aria-label="Navegação secundária"
>
    <div class="flex h-16 items-center justify-between border-b border-stone-200 px-5 lg:hidden">
        <x-brand />
        <button x-on:click="open = false" class="rounded-md p-2 text-ink-700 hover:bg-stone-100" aria-label="Fechar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path d="M6 6l12 12M6 18L18 6"/></svg>
        </button>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto p-4 lg:p-5">
        @foreach ($sections as $section)
            <div>
                <p class="mb-2 px-2 text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-500">{{ $section['label'] }}</p>
                <ul class="space-y-0.5">
                    @foreach ($section['items'] as $item)
                        @php
                            $active = $current === $item['key'];
                            $href   = isset($item['route']) ? route($item['route']) : $item['href'];
                        @endphp
                        <li>
                            <a
                                href="{{ $href }}"
                                @if($active) aria-current="page" @endif
                                class="group flex items-center gap-3 rounded-md px-2.5 py-2 text-sm font-medium transition-colors {{ $active ? 'bg-navy-900 text-white' : 'text-ink-700 hover:bg-stone-100 hover:text-navy-900' }}"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4 {{ $active ? 'text-white' : 'text-ink-500 group-hover:text-navy-700' }}"><path d="{{ $item['icon'] }}"/></svg>
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    <div class="border-t border-stone-200 p-4 lg:p-5">
        <div class="rounded-md bg-navy-950 p-4 text-xs text-navy-100">
            <p class="font-semibold uppercase tracking-[0.16em] text-white">Aviso educacional</p>
            <p class="mt-1.5 leading-relaxed text-navy-200">
                Ferramenta de simulação. Não substitui protocolos institucionais, treinamento certificado ou decisão clínica em campo.
            </p>
        </div>
    </div>
</aside>

{{-- Overlay para drawer em mobile --}}
<div
    x-data="{ open: false }"
    x-on:toggle-sidebar.window="open = ! open"
    x-show="open"
    x-transition.opacity
    x-cloak
    x-on:click="$dispatch('toggle-sidebar')"
    class="fixed inset-0 z-30 bg-navy-950/50 backdrop-blur-sm lg:hidden"
    aria-hidden="true"
></div>
