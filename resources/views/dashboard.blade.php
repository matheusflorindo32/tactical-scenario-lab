@php
$hasData = $total > 0;
@endphp

<x-layouts.app :current="'dashboard'" :title="'Painel · Tactical Scenario Lab'">

    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => 'Painel'],
        ]" />
    </x-slot:breadcrumbs>

    <x-slot:header>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <x-badge variant="navy" size="sm" dot>Visão geral</x-badge>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">Painel do instrutor</h1>
                <p class="mt-1.5 max-w-2xl text-sm text-ink-500">Estado atual dos cenários, avaliações e erros críticos mais monitorados. Atualizado a cada carregamento.</p>
            </div>
            <div class="flex items-center gap-2">
                <x-button href="{{ route('scenarios.create') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-3.5 w-3.5"><path d="M12 5v14M5 12h14"/></svg>
                    Novo cenário
                </x-button>
                <x-button href="{{ route('scenarios.index') }}" variant="secondary">Ver todos</x-button>
            </div>
        </div>
    </x-slot:header>

    @if (! $hasData)
        {{-- Estado vazio de dashboard --}}
        <x-empty-state
            icon="clip"
            title="Ainda não há dados no painel"
            description="Crie o primeiro cenário para começar a acompanhar indicadores, cenários recentes e erros críticos mais frequentes."
        >
            <x-slot:actions>
                <x-button href="{{ route('scenarios.create') }}">Criar primeiro cenário</x-button>
                <x-button href="{{ route('home') }}" variant="ghost">Voltar à página inicial</x-button>
            </x-slot:actions>
        </x-empty-state>
    @else

        {{-- Indicadores --}}
        <section aria-label="Indicadores gerais" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-stats-card label="Total de cenários" :value="$total" hint="registrados no banco" icon="M4 6h16M4 12h16M4 18h10" accent="navy" />
            <x-stats-card label="Rascunhos" :value="$drafts" hint="aguardando execução" icon="M12 20h9M16 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z" accent="alert" />
            <x-stats-card label="Em execução" :value="$running" hint="ativos agora" icon="M5 3v18l14-9L5 3z" accent="emergency" />
            <x-stats-card
                label="Média das avaliações"
                :value="$avgScore ? round($avgScore) . '/100' : '—'"
                :hint="$completed . ' cenários concluídos'"
                icon="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"
                accent="clinical"
            />
        </section>

        {{-- Corpo: 2 colunas (2/3 + 1/3) --}}
        <div class="mt-8 grid gap-6 lg:grid-cols-3">

            {{-- Cenários recentes --}}
            <div class="lg:col-span-2 space-y-6">
                <x-card title="Cenários recentes" subtitle="Últimos seis registros" padding="none">
                    <x-slot:actions>
                        <a href="{{ route('scenarios.index') }}" class="text-xs font-semibold text-navy-700 hover:text-navy-900">Ver todos →</a>
                    </x-slot:actions>

                    @if ($recent->isEmpty())
                        <div class="p-6">
                            <x-empty-state
                                icon="clip"
                                title="Nenhum cenário nas últimas atividades"
                                description="Crie um novo cenário para começar."
                            />
                        </div>
                    @else
                        <ul class="divide-y divide-stone-100">
                            @foreach ($recent as $s)
                                <li>
                                    <a href="{{ route('scenarios.show', $s) }}" class="group grid grid-cols-12 items-center gap-3 px-6 py-4 hover:bg-stone-25 focus-visible:outline-none focus-visible:bg-stone-25">
                                        <div class="col-span-7 min-w-0">
                                            <p class="truncate font-display text-sm font-semibold text-navy-900 group-hover:text-navy-700">{{ $s->title }}</p>
                                            <p class="mt-0.5 truncate text-xs text-ink-500">
                                                {{ $s->casualties }} vítima{{ $s->casualties > 1 ? 's' : '' }} · ameaça {{ $s->threat_level }}
                                            </p>
                                        </div>
                                        <div class="col-span-3">
                                            <x-status-pill :status="$s->status" />
                                        </div>
                                        <div class="col-span-2 text-right">
                                            @if ($s->status === 'completed')
                                                <span class="font-mono text-sm font-semibold text-navy-900 tabular-nums">{{ $s->score }}/100</span>
                                            @else
                                                <span class="text-xs text-ink-500">—</span>
                                            @endif
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>

                {{-- Progresso geral --}}
                <x-card title="Distribuição de status" subtitle="Composição do portfólio atual">
                    <div class="space-y-4">
                        <x-progress :value="$drafts" :max="$total" label="Rascunhos" variant="alert" />
                        <x-progress :value="$running" :max="$total" label="Em execução" variant="emergency" />
                        <x-progress :value="$completed" :max="$total" label="Concluídos" variant="clinical" />
                    </div>
                </x-card>
            </div>

            {{-- Barra lateral: erros críticos + ações --}}
            <aside class="space-y-6">
                <x-card title="Erros críticos mais monitorados" subtitle="Do catálogo gerado" accent="emergency">
                    @if ($topErrors->isEmpty())
                        <p class="text-sm text-ink-500">Sem dados ainda.</p>
                    @else
                        <ol class="space-y-3">
                            @foreach ($topErrors as $err => $count)
                                <li class="flex items-start justify-between gap-3">
                                    <span class="text-sm leading-relaxed text-ink-700">{{ $err }}</span>
                                    <x-badge variant="emergency" size="sm">{{ $count }}×</x-badge>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </x-card>

                <x-card title="Ações rápidas" padding="none">
                    <ul class="divide-y divide-stone-100 text-sm">
                        <li>
                            <a href="{{ route('scenarios.create') }}" class="flex items-center justify-between px-6 py-3 hover:bg-stone-25">
                                <span class="font-medium text-ink-900">Criar cenário</span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4 text-ink-300"><path d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('scenarios.index') }}" class="flex items-center justify-between px-6 py-3 hover:bg-stone-25">
                                <span class="font-medium text-ink-900">Ver todos os cenários</span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4 text-ink-300"><path d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </li>
                        <li>
                            <a href="{{ url('/health') }}" target="_blank" rel="noopener" class="flex items-center justify-between px-6 py-3 hover:bg-stone-25">
                                <span class="font-medium text-ink-900">Status da aplicação</span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4 text-ink-300"><path d="M15 3h6v6M14 10l7-7M9 21H3v-6M10 14l-7 7"/></svg>
                            </a>
                        </li>
                    </ul>
                </x-card>

                <x-alert variant="warning" title="Uso educacional">
                    Ferramenta de simulação. Não substitui protocolos institucionais nem decisão clínica em campo.
                </x-alert>
            </aside>
        </div>
    @endif

</x-layouts.app>
