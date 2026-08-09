<x-layouts.app :current="'dashboard'" :title="'Painel do instrutor · Tactical Scenario Lab'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[['label' => 'Painel do instrutor']]" />
    </x-slot:breadcrumbs>

    <x-slot:header>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <x-badge variant="navy" size="sm" dot>Operação institucional</x-badge>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">Painel do instrutor</h1>
                <p class="mt-1.5 max-w-3xl text-sm leading-6 text-ink-500">Priorize execuções, avaliações e ações que exigem intervenção no período selecionado. As métricas continuam derivadas do domínio operacional M3/M4.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($canManageScenarios)
                    <x-button href="{{ route('scenarios.create') }}">Novo cenário</x-button>
                @endif
                <x-button href="{{ route('scenarios.index') }}" variant="secondary">Cenários</x-button>
                @if ($canViewReports)
                    <x-button href="{{ route('dashboard.executive') }}" variant="secondary">Visão executiva</x-button>
                @endif
            </div>
        </div>
    </x-slot:header>

    <form method="GET" action="{{ route('dashboard') }}" class="mb-8 grid gap-3 rounded-xl border border-stone-200 bg-white p-4 shadow-sm sm:grid-cols-3 lg:grid-cols-4">
        <div>
            <label for="date_from" class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-500">De</label>
            <input id="date_from" name="date_from" type="date" value="{{ $filter->dateFrom->toDateString() }}" class="mt-1 w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">
        </div>
        <div>
            <label for="date_to" class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-500">Até</label>
            <input id="date_to" name="date_to" type="date" value="{{ $filter->dateTo->toDateString() }}" class="mt-1 w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">
        </div>
        <div>
            <label for="status" class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-500">Status da execução</label>
            <select id="status" name="status" class="mt-1 w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                <option value="">Todos</option>
                @foreach (['draft' => 'Rascunho', 'running' => 'Em execução', 'completed' => 'Concluída', 'cancelled' => 'Cancelada'] as $value => $label)
                    <option value="{{ $value }}" @selected($filter->status === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end"><x-button type="submit" variant="secondary">Aplicar período</x-button></div>
    </form>

    <section aria-labelledby="attention-heading">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-emergency-600">Prioridade operacional</p>
                <h2 id="attention-heading" class="mt-1 font-display text-2xl font-semibold tracking-tight text-navy-950">Central de atenção</h2>
                <p class="mt-1 max-w-2xl text-sm leading-6 text-ink-500">A fila abaixo é organizada por urgência operacional, sem alterar a fonte de verdade dos indicadores.</p>
            </div>
            <p class="text-xs font-medium text-ink-500">Período: {{ $filter->dateFrom->format('d/m/Y') }} – {{ $filter->dateTo->format('d/m/Y') }}</p>
        </div>

        <div class="grid gap-5 xl:grid-cols-2">
            <section data-attention-priority="running" aria-labelledby="attention-running" class="rounded-xl border border-stone-200 bg-white p-5 shadow-xs">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h3 id="attention-running" class="text-base font-semibold text-navy-950">Em execução</h3>
                        <p class="mt-1 text-xs text-ink-500">Operações ativas têm precedência sobre todas as demais filas.</p>
                    </div>
                    <x-badge variant="emergency" dot>{{ $running_count }}</x-badge>
                </div>
                <div class="space-y-3">
                    @forelse ($running_executions as $execution)
                        <x-attention-item
                            :title="$execution->scenarioVersion->scenario->title"
                            :metadata="'Execução #'.$execution->sequence_number.' · iniciada '.($execution->started_at?->format('d/m/Y H:i') ?? 'sem horário')"
                            variant="emergency"
                            :href="route('executions.show', $execution)"
                        >
                            Abrir o cockpit para acompanhar timeline, injects, recursos e ciclo da execução.
                        </x-attention-item>
                    @empty
                        <x-empty-state title="Nenhuma execução ativa" description="Não há operação em andamento no período selecionado." icon="clip" class="py-8" />
                    @endforelse
                </div>
            </section>

            <section data-attention-priority="overdue-actions" aria-labelledby="attention-overdue" class="rounded-xl border border-stone-200 bg-white p-5 shadow-xs">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h3 id="attention-overdue" class="text-base font-semibold text-navy-950">Ações vencidas</h3>
                        <p class="mt-1 text-xs text-ink-500">Pendências corretivas cujo prazo já terminou.</p>
                    </div>
                    <x-badge variant="emergency" dot>{{ $overdue_action_count }}</x-badge>
                </div>
                @if ($overdue_action_count > 0)
                    <x-attention-item
                        :title="$overdue_action_count.' '.($overdue_action_count === 1 ? 'ação vencida' : 'ações vencidas')"
                        metadata="Acompanhamento institucional prioritário"
                        variant="emergency"
                    >
                        O contador usa exclusivamente ações abertas ou em andamento com prazo anterior à data atual no filtro institucional vigente.
                    </x-attention-item>
                @else
                    <x-empty-state title="Nenhuma ação vencida" description="Não há pendência corretiva fora do prazo neste período." icon="clip" class="py-8" />
                @endif
            </section>

            <section data-attention-priority="unassessed" aria-labelledby="attention-unassessed" class="rounded-xl border border-stone-200 bg-white p-5 shadow-xs">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h3 id="attention-unassessed" class="text-base font-semibold text-navy-950">Sem avaliação</h3>
                        <p class="mt-1 text-xs text-ink-500">Execuções concluídas que ainda não possuem assessment.</p>
                    </div>
                    <x-badge variant="alert" dot>{{ $completed_without_assessment_count }}</x-badge>
                </div>
                <div class="space-y-3">
                    @forelse ($completed_without_assessment as $execution)
                        <x-attention-item
                            :title="$execution->scenarioVersion->scenario->title"
                            :metadata="'Execução #'.$execution->sequence_number.' · concluída '.($execution->completed_at?->format('d/m/Y H:i') ?? 'sem horário')"
                            variant="alert"
                            :href="route('executions.show', $execution)"
                        >
                            Abra a execução para iniciar a avaliação institucional quando autorizado.
                        </x-attention-item>
                    @empty
                        <x-empty-state title="Fila de avaliação em dia" description="Nenhuma execução concluída aguarda criação de assessment." icon="clip" class="py-8" />
                    @endforelse
                </div>
            </section>

            <section data-attention-priority="draft-assessments" aria-labelledby="attention-draft-assessments" class="rounded-xl border border-stone-200 bg-white p-5 shadow-xs">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h3 id="attention-draft-assessments" class="text-base font-semibold text-navy-950">Avaliações em elaboração</h3>
                        <p class="mt-1 text-xs text-ink-500">Assessments M4 existentes que ainda não foram finalizados.</p>
                    </div>
                    <x-badge variant="navy" dot>{{ $draft_assessment_count }}</x-badge>
                </div>
                <div class="space-y-3">
                    @forelse ($draft_assessments as $assessment)
                        <x-attention-item
                            :title="$assessment->execution->scenarioVersion->scenario->title"
                            :metadata="'Execução #'.$assessment->execution->sequence_number.' · avaliação em elaboração'"
                            variant="navy"
                            :href="route('assessments.show', $assessment)"
                        >
                            Continue rubrica, evidências, debrief e plano de ação no registro desta execução.
                        </x-attention-item>
                    @empty
                        <x-empty-state title="Nenhuma avaliação em elaboração" description="Não há assessment draft pendente no período selecionado." icon="file" class="py-8" />
                    @endforelse
                </div>
            </section>

            <section data-attention-priority="due-soon" aria-labelledby="attention-due-soon" class="rounded-xl border border-stone-200 bg-white p-5 shadow-xs">
                <div class="mb-4">
                    <h3 id="attention-due-soon" class="text-base font-semibold text-navy-950">Ações com prazo próximo</h3>
                    <p class="mt-1 text-xs text-ink-500">Plano de ação com vencimento nos próximos 14 dias.</p>
                </div>
                <div class="space-y-3">
                    @forelse ($actions_due_soon as $action)
                        <x-attention-item
                            :title="$action->action"
                            :metadata="($action->responsible_label ?: $action->responsiblePerson?->preferredName() ?: 'Responsável não exibido').' · prazo '.($action->due_date?->format('d/m/Y') ?? 'não informado')"
                            variant="clinical"
                        >
                            Status atual: {{ $action->status }}.
                        </x-attention-item>
                    @empty
                        <x-empty-state title="Sem vencimentos próximos" description="Nenhuma ação aberta ou em andamento vence nos próximos 14 dias." icon="clip" class="py-8" />
                    @endforelse
                </div>
            </section>

            <section data-attention-priority="recent-finalized" aria-labelledby="attention-finalized" class="rounded-xl border border-stone-200 bg-white p-5 shadow-xs">
                <div class="mb-4">
                    <h3 id="attention-finalized" class="text-base font-semibold text-navy-950">Avaliações finalizadas recentemente</h3>
                    <p class="mt-1 text-xs text-ink-500">Registros históricos mensuráveis já congelados.</p>
                </div>
                <div class="space-y-3">
                    @forelse ($recent_finalized_assessments as $assessment)
                        <x-attention-item
                            :title="$assessment->execution->scenarioVersion->scenario->title"
                            :metadata="'Execução #'.$assessment->execution->sequence_number.' · '.($assessment->final_score !== null ? number_format((float) $assessment->final_score, 2, ',', '.').'/100' : 'sem nota numérica')"
                            variant="clinical"
                            :href="route('assessments.show', $assessment)"
                        >
                            Resultado: {{ $assessment->result ?: 'sem classificação histórica' }}.
                        </x-attention-item>
                    @empty
                        <x-empty-state title="Nenhuma avaliação finalizada" description="Não há registro finalizado recente para o período selecionado." icon="file" class="py-8" />
                    @endforelse
                </div>
            </section>
        </div>
    </section>

    <section aria-labelledby="panorama-heading" class="mt-10">
        <div class="mb-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-ink-500">Contexto do período</p>
            <h2 id="panorama-heading" class="mt-1 font-display text-xl font-semibold text-navy-950">Panorama operacional</h2>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
            <x-stats-card label="Em execução" :value="$running_count" hint="agora" icon="M5 3v18l14-9L5 3z" accent="emergency" />
            <x-stats-card label="Rascunhos" :value="$draft_execution_count" hint="execuções" icon="M12 20h9" accent="alert" />
            <x-stats-card label="Sem avaliação" :value="$completed_without_assessment_count" hint="concluídas" icon="M9 12l2 2 4-4" accent="navy" />
            <x-stats-card label="Avaliações draft" :value="$draft_assessment_count" hint="a finalizar" icon="M4 6h16M4 12h16" accent="navy" />
            <x-stats-card label="Ações abertas" :value="$open_action_count" hint="corretivas" icon="M5 13l4 4L19 7" accent="clinical" />
            <x-stats-card label="Ações vencidas" :value="$overdue_action_count" hint="prioridade" icon="M12 9v4m0 4h.01" accent="emergency" />
        </div>
    </section>
</x-layouts.app>
