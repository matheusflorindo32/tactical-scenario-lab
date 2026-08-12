@extends('layouts.public')

@section('title', 'Tactical Scenario Lab — Treinamento orientado por evidências')
@section('description', 'Organize o ciclo completo do treinamento baseado em cenários, avalie a execução e transforme cada exercício em debriefing e melhoria.')

@section('content')
<section id="visao-geral" data-d2-hero class="d2-hero scroll-mt-24">
    <div class="d2-hero-pattern" aria-hidden="true"></div>
    <div class="tsl-container relative grid items-center gap-12 py-14 md:py-18 lg:min-h-[calc(100svh-4.5rem)] lg:grid-cols-[minmax(0,1.02fr)_minmax(31rem,.98fr)] lg:gap-16 lg:py-20">
        <div class="max-w-3xl tsl-fade-in">
            <p class="d2-eyebrow">PLATAFORMA DE SIMULAÇÃO, AVALIAÇÃO E DEBRIEFING</p>

            <h1 class="mt-6 max-w-[16ch] text-[2.55rem] font-semibold leading-[1.04] tracking-[-0.045em] text-navy-950 sm:text-5xl lg:text-[3.75rem]">
                Treine decisões. Avalie a execução. Transforme cada cenário em aprendizado.
            </h1>

            <p class="mt-6 max-w-2xl text-base leading-7 text-ink-600 sm:text-lg sm:leading-8">
                O Tactical Scenario Lab estrutura todo o ciclo de treinamento baseado em cenários — do planejamento à execução, da avaliação objetiva ao debriefing — reunindo métricas, histórico e relatórios para apoiar a melhoria contínua.
            </p>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                <x-button href="#recursos" size="lg" class="w-full sm:w-auto">
                    Conhecer a plataforma
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4" aria-hidden="true"><path d="M4 10h12m-4-4 4 4-4 4" /></svg>
                </x-button>
                <x-button href="{{ route('login') }}" variant="secondary" size="lg" class="w-full sm:w-auto">
                    Acessar o ambiente
                </x-button>
            </div>

            <p class="mt-7 flex items-center gap-3 text-sm font-medium text-ink-500">
                <span class="h-px w-8 bg-stone-300" aria-hidden="true"></span>
                Instrutores <span aria-hidden="true">•</span> Equipes <span aria-hidden="true">•</span> Instituições
            </p>
        </div>

        <article class="d2-product-card tsl-fade-in" aria-labelledby="demo-title">
            <header class="d2-product-header">
                <div>
                    <p class="d2-panel-kicker">Experiência do produto</p>
                    <h2 id="demo-title" class="mt-1 text-lg font-semibold text-navy-950">Ciclo de treinamento</h2>
                </div>
                <span class="d2-demo-label">Dados ilustrativos</span>
            </header>

            <div class="p-5 sm:p-6">
                <div class="flex flex-col gap-4 border-b border-stone-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="d2-field-label">Cenário</p>
                        <p class="mt-1 text-lg font-semibold text-navy-950">Treinamento de tomada de decisão</p>
                    </div>
                    <div class="sm:text-right">
                        <p class="d2-field-label">Fase atual</p>
                        <span class="d2-phase-badge">AVALIAR</span>
                    </div>
                </div>

                <dl id="recursos" class="mt-5 grid scroll-mt-24 grid-cols-2 gap-px overflow-hidden rounded-xl border border-stone-200 bg-stone-200">
                    <div class="d2-metric-cell">
                        <dt>Participantes</dt>
                        <dd>12</dd>
                    </div>
                    <div class="d2-metric-cell">
                        <dt>Execuções</dt>
                        <dd>10 / 12</dd>
                    </div>
                    <div class="d2-metric-cell">
                        <dt>Avaliação</dt>
                        <dd class="text-sm!">Indicadores objetivos</dd>
                    </div>
                    <div class="d2-metric-cell">
                        <dt>Debriefing</dt>
                        <dd class="text-sm! text-alert-700!">Pendente</dd>
                    </div>
                </dl>

                <div id="como-funciona" class="mt-6 scroll-mt-24">
                    <div class="flex items-center justify-between gap-4">
                        <p class="d2-field-label">Ciclo de melhoria</p>
                        <p class="font-mono text-[10px] uppercase tracking-[0.14em] text-ink-500">5 etapas</p>
                    </div>
                    <ol class="d2-cycle" aria-label="Planejar, executar, avaliar, debriefar e melhorar">
                        @foreach (['Planejar', 'Executar', 'Avaliar', 'Debriefar', 'Melhorar'] as $index => $phase)
                            <li class="{{ $phase === 'Avaliar' ? 'is-current' : '' }}">
                                <span>{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                <strong>{{ $phase }}</strong>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>

            <footer class="d2-product-footer">
                Demonstração visual da organização do treinamento. Não representa dados de clientes.
            </footer>
        </article>
    </div>
</section>

<section class="border-y border-slate-200 bg-slate-50">
    <div class="mx-auto max-w-6xl px-6 py-16">
        <div class="grid gap-8 md:grid-cols-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-950">
                    Para instrutores
                </h2>

                <p class="mt-2 text-slate-600">
                    Organize cenários reproduzíveis e critérios objetivos de avaliação.
                </p>
            </div>

            <div>
                <h2 class="text-lg font-semibold text-slate-950">
                    Para equipes
                </h2>

                <p class="mt-2 text-slate-600">
                    Treine tomada de decisão, comunicação e resposta sob pressão.
                </p>
            </div>

            <div>
                <h2 class="text-lg font-semibold text-slate-950">
                    Para formação
                </h2>

                <p class="mt-2 text-slate-600">
                    Registre evolução, pontos fortes e oportunidades de melhoria.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="mx-auto max-w-6xl px-6 py-16">
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6">
        <h2 class="font-semibold text-amber-950">
            Aviso de uso
        </h2>

        <p class="mt-2 text-amber-900">
            Esta plataforma tem finalidade educacional e de simulação.
            Não substitui protocolos oficiais, formação profissional,
            avaliação médica ou decisão operacional.
        </p>
    </div>
</section>
@endsection
