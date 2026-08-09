@props(['current' => null])

@php
$user = auth()->user();
$activeOrganizationId = session('active_organization_id');
$access = $user?->activeOrganizationAccesses()
    ->where('organization_id', $activeOrganizationId)
    ->first();
$abilities = $access?->abilities ?? [];

$canViewScenarios = in_array(\App\Support\Auth\AccessAbility::SCENARIOS_VIEW, $abilities, true);
$canViewReports = in_array(\App\Support\Auth\AccessAbility::REPORTS_VIEW, $abilities, true);
$canViewPeople = in_array(\App\Support\Auth\AccessAbility::PEOPLE_VIEW, $abilities, true);
$canManageAccess = in_array(\App\Support\Auth\AccessAbility::ACCESS_MANAGE, $abilities, true);

$sections = [
    [
        'label' => 'Operação',
        'items' => array_values(array_filter([
            $canViewScenarios ? ['label' => 'Painel', 'route' => 'dashboard', 'key' => 'dashboard', 'icon' => 'M3 12l9-9 9 9M5 10v10h14V10'] : null,
            $canViewScenarios ? ['label' => 'Cenários', 'route' => 'scenarios.index', 'key' => 'scenarios', 'icon' => 'M4 6h16M4 12h16M4 18h10'] : null,
            $canViewScenarios ? ['label' => 'Templates', 'route' => 'scenario-templates.index', 'key' => 'templates', 'icon' => 'M4 5h16v14H4zM8 9h8M8 13h5'] : null,
            $canViewReports ? ['label' => 'Histórico', 'route' => 'execution-history.index', 'key' => 'history', 'icon' => 'M3 12a9 9 0 109-9M3 4v8h8M12 7v5l3 2'] : null,
        ])),
    ],
    [
        'label' => 'Análise',
        'items' => array_values(array_filter([
            $canViewReports ? ['label' => 'Visão executiva', 'route' => 'dashboard.executive', 'key' => 'executive', 'icon' => 'M4 19V9m5 10V5m5 14v-7m5 7V3'] : null,
        ])),
    ],
    [
        'label' => 'Gestão',
        'items' => array_values(array_filter([
            $canViewPeople ? ['label' => 'Pessoas', 'route' => 'people.index', 'key' => 'people', 'icon' => 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75'] : null,
            ['label' => 'Organizações', 'route' => 'organizations.index', 'key' => 'organizations', 'icon' => 'M3 21h18M5 21V7l7-4 7 4v14M9 10h1M9 14h1M14 10h1M14 14h1'],
            $canManageAccess ? ['label' => 'Acessos', 'route' => 'access.index', 'key' => 'access', 'icon' => 'M12 11a4 4 0 100-8 4 4 0 000 8zm-7 9a7 7 0 0114 0M19 8h3m-1.5-1.5v3'] : null,
        ])),
    ],
];

$sections = array_values(array_filter($sections, fn (array $section): bool => $section['items'] !== []));
@endphp

<aside
    x-data="{ open: false }"
    x-on:toggle-sidebar.window="open = ! open"
    x-on:keydown.escape.window="open = false"
    class="fixed inset-y-0 left-0 z-40 w-72 -translate-x-full transform border-r border-stone-200 bg-white transition-transform lg:sticky lg:top-16 lg:h-[calc(100vh-4rem)] lg:translate-x-0"
    :class="{ 'translate-x-0': open }"
    aria-label="Navegação principal"
>
    <div class="flex h-16 items-center justify-between border-b border-stone-200 px-5 lg:hidden">
        <x-brand />
        <button x-on:click="open = false" class="rounded-md p-3 text-ink-700 hover:bg-stone-100" aria-label="Fechar menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path d="M6 6l12 12M6 18L18 6"/></svg>
        </button>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto p-4 lg:p-5" aria-label="Seções da aplicação">
        @foreach ($sections as $section)
            <div>
                <p class="mb-2 px-2 text-[10px] font-semibold uppercase tracking-[0.16em] text-ink-500">{{ $section['label'] }}</p>
                <ul class="space-y-0.5">
                    @foreach ($section['items'] as $item)
                        @php $active = $current === $item['key']; @endphp
                        <li>
                            <a
                                href="{{ route($item['route']) }}"
                                @if($active) aria-current="page" @endif
                                class="group flex min-h-11 items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors {{ $active ? 'bg-navy-900 text-white' : 'text-ink-700 hover:bg-stone-100 hover:text-navy-900' }}"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4 shrink-0 {{ $active ? 'text-white' : 'text-ink-500 group-hover:text-navy-700' }}"><path d="{{ $item['icon'] }}"/></svg>
                                <span>{{ $item['label'] }}</span>
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
