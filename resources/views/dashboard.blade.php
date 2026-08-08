<x-layouts.app :current="'dashboard'" :title="'Painel do instrutor · Tactical Scenario Lab'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[['label' => 'Painel do instrutor']]" />
    </x-slot:breadcrumbs>

    <x-slot:header>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <x-badge variant="navy" size="sm" dot>Operação institucional</x-badge>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">Painel do instrutor</h1>
                <p class="mt-1.5 max-w-3xl text-sm leading-6 text-ink-500">Execuções, avaliações e ações que exigem atenção no período selecionado. As métricas usam o domínio M3/M4 e não a avaliação legada do cenário.</p>
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

    <form method="GET" action="{{ route('dashboard') }}" class="mb-6 grid gap-3 rounded-xl border border-stone-200 bg-white p-4 shadow-sm sm:grid-cols-3 lg:grid-cols-4">
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

    <section aria-label="Indicadores operacionais" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
        <x-stats-card label="Em execução" :value="$running_count" hint="agora" icon="M5 3v18l14-9L5 3z" accent="emergency" />
        <x-stats-card label="Rascunhos" :value="$draft_execution_count" hint="execuções" icon="M12 20h9" accent="alert" />
        <x-stats-card label="Sem avaliação" :value="$completed_without_assessment_count" hint="concluídas" icon="M9 12l2 2 4-4" accent="navy" />
        <x-stats-card label="Avaliações draft" :value="$draft_assessment_count" hint="a finalizar" icon="M4 6h16M4 12h16" accent="navy" />
        <x-stats-card label="Ações abertas" :value="$open_action_count" hint="corretivas" icon="M5 13l4 4L19 7" accent="clinical" />
        <x-stats-card label="Ações vencidas" :value="$overdue_action_count" hint="prioridade" icon="M12 9v4m0 4h.01" accent="emergency" />
    </section>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <x-card title="Execuções em andamento" subtitle="Prioridade operacional" accent="emergency">
            <div class="space-y-3">
                @forelse ($running_executions as $execution)
                    <a href="{{ route('executions.show', $execution) }}" class="block rounded-lg border border-stone-200 bg-stone-50 px-4 py-3 hover:bg-white">
                        <p class="text-sm font-semibold text-navy-950">{{ $execution->scenarioVersion->scenario->title }}</p>
                        <p class="mt-1 text-xs text-ink-500">Execução #{{ $execution->sequence_number }} · iniciada {{ $execution->started_at?->format('d/m/Y H:i') }}</p>
                    </a>
                @empty
                    <p class="text-sm text-ink-500">Nenhuma execução em andamento no período.</p>
                @endforelse
            </div>
        </x-card>

        <x-card title="Concluídas sem avaliação" subtitle="Prontas para iniciar o assessment" accent="alert">
            <div class="space-y-3">
                @forelse ($completed_without_assessment as $execution)
                    <a href="{{ route('executions.show', $execution) }}" class="block rounded-lg border border-stone-200 bg-stone-50 px-4 py-3 hover:bg-white">
                        <p class="text-sm font-semibold text-navy-950">{{ $execution->scenarioVersion->scenario->title }}</p>
                        <p class="mt-1 text-xs text-ink-500">Execução #{{ $execution->sequence_number }} · concluída {{ $execution->completed_at?->format('d/m/Y H:i') }}</p>
                    </a>
                @empty
                    <p class="text-sm text-ink-500">Nenhuma execução aguardando criação de avaliação.</p>
                @endforelse
            </div>
        </x-card>

        <x-card title="Avaliações em elaboração" subtitle="Assessment M4 ainda não finalizado" accent="navy">
            <div class="space-y-3">
                @forelse ($draft_assessments as $assessment)
                    <a href="{{ route('assessments.show', $assessment) }}" class="block rounded-lg border border-stone-200 bg-stone-50 px-4 py-3 hover:bg-white">
                        <p class="text-sm font-semibold text-navy-950">{{ $assessment->execution->scenarioVersion->scenario->title }}</p>
                        <p class="mt-1 text-xs text-ink-500">Execução #{{ $assessment->execution->sequence_number }} · avaliação em elaboração</p>
                    </a>
                @empty
                    <p class="text-sm text-ink-500">Nenhuma avaliação em elaboração.</p>
                @endforelse
            </div>
        </x-card>

        <x-card title="Ações com prazo próximo" subtitle="Próximos 14 dias" accent="clinical">
            <div class="space-y-3">
                @forelse ($actions_due_soon as $action)
                    <div class="rounded-lg border border-stone-200 bg-stone-50 px-4 py-3">
                        <p class="text-sm font-semibold text-ink-900">{{ $action->action }}</p>
                        <p class="mt-1 text-xs text-ink-500">{{ $action->responsible_label ?: $action->responsiblePerson?->preferredName() }} · prazo {{ $action->due_date?->format('d/m/Y') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-ink-500">Nenhuma ação com prazo nos próximos 14 dias.</p>
                @endforelse
            </div>
        </x-card>
    </div>

    <x-card class="mt-6" title="Avaliações finalizadas recentemente" subtitle="Registros históricos mensuráveis" accent="clinical">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($recent_finalized_assessments as $assessment)
                <a href="{{ route('assessments.show', $assessment) }}" class="rounded-lg border border-stone-200 bg-stone-50 px-4 py-3 hover:bg-white">
                    <p class="text-sm font-semibold text-navy-950">{{ $assessment->execution->scenarioVersion->scenario->title }}</p>
                    <p class="mt-1 text-xs text-ink-500">{{ $assessment->final_score !== null ? number_format((float) $assessment->final_score, 2, ',', '.') . '/100' : 'Sem nota numérica' }} · {{ $assessment->result ?: 'Sem classificação histórica' }}</p>
                </a>
            @empty
                <p class="text-sm text-ink-500">Nenhuma avaliação finalizada no período.</p>
            @endforelse
        </div>
    </x-card>
</x-layouts.app>
