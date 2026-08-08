@php
$statusLabel = $assessment->isFinalized() ? 'Finalizada' : 'Rascunho';
$statusVariant = $assessment->isFinalized() ? 'clinical' : 'navy';
$resultLabel = match ($assessment->result) {
    'passed' => 'Aprovado',
    'failed' => 'Reprovado',
    default => 'Pendente',
};
$resultVariant = match ($assessment->result) {
    'passed' => 'clinical',
    'failed' => 'emergency',
    default => 'neutral',
};
@endphp

<x-layouts.app :current="'scenarios'" :title="'Avaliação da execução ' . $execution->sequence_number . ' · Tactical Scenario Lab'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => 'Painel', 'href' => route('dashboard')],
            ['label' => 'Cenários', 'href' => route('scenarios.index')],
            ['label' => $execution->scenarioVersion->scenario->title, 'href' => route('scenarios.show', $execution->scenarioVersion->scenario)],
            ['label' => 'Execução ' . $execution->sequence_number, 'href' => route('executions.show', $execution)],
            ['label' => 'Avaliação & Debriefing'],
        ]" />
    </x-slot:breadcrumbs>

    <x-slot:header>
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <x-badge :variant="$statusVariant" size="sm" dot>{{ $statusLabel }}</x-badge>
                    <x-badge :variant="$resultVariant" size="sm">{{ $resultLabel }}</x-badge>
                    <x-badge variant="neutral" size="sm">Execução #{{ $execution->sequence_number }}</x-badge>
                </div>
                <h1 class="mt-3 font-display text-3xl font-semibold tracking-tight text-navy-950">Avaliação & Debriefing</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-500">
                    {{ $execution->scenarioVersion->scenario->title }} · avaliação institucional vinculada exclusivamente a esta execução.
                </p>
            </div>
            <x-button href="{{ route('executions.show', $execution) }}" variant="secondary">Voltar ao cockpit</x-button>
        </div>
    </x-slot:header>

    @if (session('success'))
        <div class="mb-6">
            <x-alert variant="success" title="Operação concluída">{{ session('success') }}</x-alert>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6">
            <x-alert variant="danger" title="Revise os dados enviados">{{ $errors->first() }}</x-alert>
        </div>
    @endif

    @if ($assessment->isFinalized())
        <div class="mb-6">
            <x-alert variant="success" title="Registro institucional finalizado">
                O conteúdo da avaliação está congelado. Apenas o status operacional de ações do plano poderá evoluir nos fluxos autorizados.
            </x-alert>
        </div>
    @endif

    <div class="space-y-6">
        <x-card title="Resumo da avaliação" subtitle="Composição transparente do resultado" accent="navy">
            <dl class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Nota-base</dt>
                    <dd class="mt-2 font-display text-2xl font-semibold tabular-nums text-navy-950">{{ $assessment->base_score ?? '—' }}</dd>
                </div>
                <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Penalidades</dt>
                    <dd class="mt-2 font-display text-2xl font-semibold tabular-nums text-navy-950">{{ $assessment->penalty_points ?? '—' }}</dd>
                </div>
                <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Ajuste do avaliador</dt>
                    <dd class="mt-2 font-display text-2xl font-semibold tabular-nums text-navy-950">{{ $assessment->evaluator_adjustment > 0 ? '+' : '' }}{{ $assessment->evaluator_adjustment }}</dd>
                </div>
                <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Nota final</dt>
                    <dd class="mt-2 font-display text-2xl font-semibold tabular-nums text-navy-950">{{ $assessment->final_score ?? '—' }}</dd>
                </div>
                <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Resultado</dt>
                    <dd class="mt-2"><x-badge :variant="$resultVariant" dot>{{ $resultLabel }}</x-badge></dd>
                </div>
            </dl>

            @if ($assessment->automatic_fail)
                <div class="mt-4">
                    <x-alert variant="danger" title="Reprovação automática">
                        Há ocorrência crítica marcada como reprovação automática. A nota numérica foi preservada para transparência.
                    </x-alert>
                </div>
            @endif

            @if ($canEvaluate && $assessment->isDraft())
                <form method="POST" action="{{ route('assessments.adjustment', $assessment) }}" class="mt-6 grid gap-4 border-t border-stone-100 pt-5 md:grid-cols-[180px_minmax(0,1fr)_auto] md:items-end">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label for="evaluator_adjustment" class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-600">Ajuste (-10 a +10)</label>
                        <input id="evaluator_adjustment" name="evaluator_adjustment" type="number" min="-10" max="10" value="{{ $assessment->evaluator_adjustment }}" class="mt-1 w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" required>
                    </div>
                    <div>
                        <label for="adjustment_justification" class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-600">Justificativa quando não zero</label>
                        <input id="adjustment_justification" name="adjustment_justification" type="text" maxlength="2000" value="{{ $assessment->adjustment_justification }}" class="mt-1 w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" placeholder="Fundamentação profissional do ajuste">
                    </div>
                    <x-button type="submit" variant="secondary">Salvar ajuste</x-button>
                </form>
            @elseif ($assessment->adjustment_justification)
                <p class="mt-4 text-sm leading-6 text-ink-600"><strong>Justificativa:</strong> {{ $assessment->adjustment_justification }}</p>
            @endif
        </x-card>

        <div class="grid gap-6 xl:grid-cols-2">
            <x-card title="Rubrica" subtitle="Critérios ponderados e evidências objetivas" accent="clinical">
                <div class="space-y-3">
                    @forelse ($assessment->criteria as $criterion)
                        <div class="rounded-lg border border-stone-200 bg-white p-4 shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-ink-900">{{ $criterion->label }}</p>
                                    @if ($criterion->description)
                                        <p class="mt-1 text-xs leading-5 text-ink-500">{{ $criterion->description }}</p>
                                    @endif
                                </div>
                                <div class="flex gap-2">
                                    <x-badge variant="neutral" size="sm">Peso {{ $criterion->weight }}%</x-badge>
                                    <x-badge variant="navy" size="sm">Nota {{ $criterion->score ?? '—' }}</x-badge>
                                </div>
                            </div>
                            <div class="mt-3 space-y-2 border-t border-stone-100 pt-3">
                                @forelse ($criterion->evidence as $evidence)
                                    <p class="text-xs leading-5 text-ink-600">{{ $evidence->statement }}</p>
                                @empty
                                    <p class="text-xs text-ink-500">Nenhuma evidência registrada.</p>
                                @endforelse
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-ink-500">Nenhum critério definido. A rubrica precisa ser completada antes da finalização.</p>
                    @endforelse
                </div>
            </x-card>

            <x-card title="Erros críticos observados" subtitle="Catálogo da versão separado das ocorrências desta execução" accent="alert">
                <div class="space-y-3">
                    @forelse ($assessment->criticalErrorOccurrences as $occurrence)
                        <div class="rounded-lg border border-stone-200 bg-white p-4 shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <p class="text-sm font-semibold text-ink-900">{{ $occurrence->catalog_label_snapshot }}</p>
                                <x-badge :variant="$occurrence->rule === 'automatic_fail' ? 'emergency' : ($occurrence->rule === 'penalty' ? 'alert' : 'neutral')" size="sm">
                                    {{ $occurrence->rule }}
                                </x-badge>
                            </div>
                            @if ((float) $occurrence->penalty_points > 0)
                                <p class="mt-2 text-xs font-medium text-ink-600">Penalidade: {{ $occurrence->penalty_points }} ponto(s)</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-ink-500">Nenhum erro crítico observado foi registrado.</p>
                    @endforelse
                </div>
            </x-card>
        </div>

        <x-card title="Tempos-chave" subtitle="Marcos temporais calculados a partir do início da execução" accent="navy">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($assessment->keyTimes as $keyTime)
                    <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                        <p class="text-sm font-semibold text-ink-900">{{ $keyTime->label }}</p>
                        <p class="mt-2 text-xs tabular-nums text-ink-600">Tempo decorrido: {{ $keyTime->elapsed_seconds }} s</p>
                        @if ($keyTime->reference_seconds !== null)
                            <p class="mt-1 text-xs tabular-nums text-ink-500">Referência: {{ $keyTime->reference_seconds }} s</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-ink-500">Nenhum tempo-chave registrado.</p>
                @endforelse
            </div>
        </x-card>

        <div class="grid gap-6 xl:grid-cols-3">
            @foreach ([
                'fact' => ['Fatos', 'O que objetivamente aconteceu.'],
                'interpretation' => ['Interpretações', 'O que os fatos significam.'],
                'recommendation' => ['Recomendações', 'O que deve ser mantido ou modificado.'],
            ] as $kind => [$title, $subtitle])
                <x-card :title="$title" :subtitle="$subtitle" accent="clinical">
                    <div class="space-y-3">
                        @forelse (($assessment->debrief?->entries ?? collect())->where('kind', $kind) as $entry)
                            <p class="rounded-lg border border-stone-200 bg-stone-50 p-4 text-sm leading-6 text-ink-700">{{ $entry->content }}</p>
                        @empty
                            <p class="text-sm text-ink-500">Nenhum registro nesta categoria.</p>
                        @endforelse
                    </div>
                </x-card>
            @endforeach
        </div>

        <x-card title="Plano de ação" subtitle="Ações corretivas com responsável, prazo e acompanhamento operacional" accent="alert">
            <div class="space-y-3">
                @forelse ($assessment->debrief?->actionItems ?? collect() as $actionItem)
                    <div class="rounded-lg border border-stone-200 bg-white p-4 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-ink-900">{{ $actionItem->action }}</p>
                                <p class="mt-1 text-xs text-ink-500">
                                    Responsável: {{ $actionItem->responsiblePerson?->preferredName() ?? $actionItem->responsible_label }} · Prazo: {{ $actionItem->due_date?->format('d/m/Y') }}
                                </p>
                            </div>
                            <x-badge variant="neutral" size="sm">{{ $actionItem->status }}</x-badge>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-ink-500">Nenhuma ação corretiva registrada.</p>
                @endforelse
            </div>
        </x-card>

        @if ($canEvaluate && $assessment->isDraft())
            <x-card title="Finalização institucional" subtitle="A finalização congela o conteúdo histórico da avaliação" accent="navy">
                <p class="text-sm leading-6 text-ink-600">
                    A execução deve estar concluída, os pesos devem totalizar 100%, todos os critérios precisam de nota e evidência, e o debrief deve conter fato, interpretação e recomendação.
                </p>
                <form method="POST" action="{{ route('assessments.finalize', $assessment) }}" class="mt-4">
                    @csrf
                    @method('PATCH')
                    <x-button type="submit" variant="success">Finalizar avaliação</x-button>
                </form>
            </x-card>
        @endif
    </div>
</x-layouts.app>
