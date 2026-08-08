@php
$total     = $scenarios->total();
$drafts    = $scenarios->getCollection()->where('status','draft')->count();
$running   = $scenarios->getCollection()->where('status','running')->count();
$completed = $scenarios->getCollection()->where('status','completed')->count();
$avgScore  = optional($scenarios->getCollection()->where('status','completed'))->avg('score');
@endphp

<x-layouts.app :current="'scenarios'" :title="'Cenários · Tactical Scenario Lab'">

    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => 'Painel',    'href'  => route('scenarios.index')],
            ['label' => 'Cenários'],
        ]" />
    </x-slot:breadcrumbs>

    <x-slot:header>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <x-badge variant="navy" size="sm" dot>MVP · v0.1.0</x-badge>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">Painel de cenários</h1>
                <p class="mt-1.5 max-w-2xl text-sm text-ink-500">Rascunhos, execuções em andamento e cenários avaliados. Clique em um cenário para abrir a ficha completa.</p>
            </div>
            <x-button href="{{ route('scenarios.create') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-3.5 w-3.5"><path d="M12 5v14M5 12h14"/></svg>
                Novo cenário
            </x-button>
        </div>
    </x-slot:header>

    {{-- Indicadores --}}
    <section aria-label="Indicadores gerais" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stats-card label="Total" :value="$total" hint="cenários registrados" icon="M4 6h16M4 12h16M4 18h10" accent="navy" />
        <x-stats-card label="Rascunhos" :value="$drafts" hint="aguardando execução" icon="M12 20h9M16 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z" accent="alert" />
        <x-stats-card label="Em execução" :value="$running" hint="cenários ativos agora" icon="M5 3v18l14-9L5 3z" accent="emergency" />
        <x-stats-card
            label="Média das avaliações"
            :value="$avgScore ? round($avgScore) . '/100' : '—'"
            :hint="$completed . ' avaliados'"
            icon="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"
            accent="clinical"
        />
    </section>

    {{-- Lista --}}
    <section aria-labelledby="lista-cenarios-titulo" class="mt-8">
        <div class="mb-4 flex items-baseline justify-between">
            <h2 id="lista-cenarios-titulo" class="font-display text-lg font-semibold text-navy-900">Cenários recentes</h2>
            <span class="text-xs text-ink-500">Página {{ $scenarios->currentPage() }} de {{ $scenarios->lastPage() ?: 1 }}</span>
        </div>

        @forelse ($scenarios as $scenario)
            @if ($loop->first)
                <ul class="grid gap-3">
            @endif
                <li>
                    <a
                        href="{{ route('scenarios.show', $scenario) }}"
                        class="group block rounded-lg bg-white p-5 ring-1 ring-stone-200 transition-all hover:-translate-y-0.5 hover:ring-navy-300 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy-500"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-status-pill :status="$scenario->status" />
                                    <x-badge variant="{{ $scenario->threat_level === 'ativa' ? 'emergency' : ($scenario->threat_level === 'potencial' ? 'alert' : 'neutral') }}" size="sm">
                                        Ameaça {{ $scenario->threat_level }}
                                    </x-badge>
                                </div>
                                <h3 class="mt-2 font-display text-lg font-semibold text-navy-900 group-hover:text-navy-700">{{ $scenario->title }}</h3>
                                <p class="mt-1 text-sm text-ink-500">
                                    {{ $scenario->casualties }} vítima{{ $scenario->casualties > 1 ? 's' : '' }}
                                    @if (is_array($scenario->resources) && count($scenario->resources))
                                        · {{ implode(' · ', array_slice($scenario->resources, 0, 3)) }}@if(count($scenario->resources) > 3)…@endif
                                    @endif
                                </p>
                            </div>

                            <div class="flex items-center gap-6">
                                @if ($scenario->status === 'completed')
                                    <div class="text-right">
                                        <p class="font-display text-2xl font-semibold text-navy-900 tabular-nums">{{ $scenario->score ?? 0 }}<span class="text-sm text-ink-500">/100</span></p>
                                        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-500">avaliação</p>
                                    </div>
                                @endif
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4 shrink-0 text-ink-300 transition-transform group-hover:translate-x-1 group-hover:text-navy-700"><path d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </div>
                    </a>
                </li>
            @if ($loop->last)
                </ul>
            @endif
        @empty
            <x-empty-state
                icon="clip"
                title="Nenhum cenário criado ainda"
                description="Comece pelo wizard: em oito passos curtos você gera a ficha completa, com objetivos, ações esperadas e erros críticos a monitorar."
            >
                <x-slot:actions>
                    <x-button href="{{ route('scenarios.create') }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-3.5 w-3.5"><path d="M12 5v14M5 12h14"/></svg>
                        Criar o primeiro cenário
                    </x-button>
                    <x-button href="{{ url('/') }}" variant="ghost">Voltar à página inicial</x-button>
                </x-slot:actions>
            </x-empty-state>
        @endforelse

        @if ($scenarios->hasPages())
            <div class="mt-6">{{ $scenarios->links() }}</div>
        @endif
    </section>

</x-layouts.app>
