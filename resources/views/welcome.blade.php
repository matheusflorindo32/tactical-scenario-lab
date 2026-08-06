@extends('layouts.public')

@section('title', 'Tactical Scenario Lab')

@section('content')
<section class="mx-auto max-w-6xl px-6 py-20 lg:py-28">
    <div class="grid items-center gap-12 lg:grid-cols-2">
        <div>
            <span class="inline-flex rounded-full border border-red-200 bg-red-50 px-3 py-1 text-sm font-semibold text-red-700">
                MVP v0.1.0
            </span>

            <h1 class="mt-6 text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl lg:text-6xl">
                Cenários simulados de APH tático com estrutura, avaliação e debriefing.
            </h1>

            <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600">
                Crie cenários padronizados, acompanhe a execução, registre erros críticos e conduza avaliações com foco em treinamento e melhoria contínua.
            </p>

            <div class="mt-8 flex flex-wrap gap-4">
                <a href="{{ route('scenarios.create') }}"
                   class="rounded-lg bg-slate-950 px-6 py-3 font-semibold text-white transition hover:bg-slate-800">
                    Criar cenário
                </a>

                <a href="{{ route('dashboard') }}"
                   class="rounded-lg border border-slate-300 bg-white px-6 py-3 font-semibold text-slate-900 transition hover:bg-slate-50">
                    Ver painel
                </a>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-200 p-5">
                    <p class="text-sm font-medium text-slate-500">Planejamento</p>
                    <p class="mt-2 text-lg font-semibold text-slate-950">
                        Contexto, ameaça, vítimas e recursos
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 p-5">
                    <p class="text-sm font-medium text-slate-500">Execução</p>
                    <p class="mt-2 text-lg font-semibold text-slate-950">
                        Fluxo controlado do início ao encerramento
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 p-5">
                    <p class="text-sm font-medium text-slate-500">Avaliação</p>
                    <p class="mt-2 text-lg font-semibold text-slate-950">
                        Pontuação e erros críticos
                    </p>
                </div>

                <div class="rounded-xl border border-slate-200 p-5">
                    <p class="text-sm font-medium text-slate-500">Debriefing</p>
                    <p class="mt-2 text-lg font-semibold text-slate-950">
                        Registro de lições aprendidas
                    </p>
                </div>
            </div>
        </div>
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