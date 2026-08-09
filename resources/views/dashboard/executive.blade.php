<x-layouts.app :current="'executive'" :title="'Painel executivo · Tactical Scenario Lab'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => 'Painel do instrutor', 'href' => route('dashboard')],
            ['label' => 'Visão executiva'],
        ]" />
    </x-slot:breadcrumbs>

    <x-slot:header>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <x-badge variant="navy" size="sm" dot>Relatórios institucionais</x-badge>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">Painel executivo</h1>
                <p class="mt-1.5 max-w-3xl text-sm leading-6 text-ink-500">Risco, desempenho e acompanhamento derivados de execuções e avaliações M4. Resultados históricos desconhecidos continuam fora da taxa de aprovação.</p>
            </div>
            <x-button href="{{ route('dashboard') }}" variant="secondary">Voltar ao painel do instrutor</x-button>
        </div>
    </x-slot:header>

    <form method="GET" action="{{ route('dashboard.executive') }}" class="mb-8 grid gap-3 rounded-xl border border-stone-200 bg-white p-4 shadow-sm sm:grid-cols-3 lg:grid-cols-4">
        <div>
            <label for="date_from" class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-500">De</label>
            <input id="date_from" name="date_from" type="date" value="{{ $filter->dateFrom->toDateString() }}" class="mt-1 w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">
        </div>
        <div>
            <label for="date_to" class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-500">Até</label>
            <input id="date_to" name="date_to" type="date" value="{{ $filter->dateTo->toDateString() }}" class="mt-1 w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">
        </div>
        <div>
            <label for="status" class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-500">Status</label>
            <select id="status" name="status" class="mt-1 w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                <option value="">Todos</option>
                @foreach (['draft' => 'Rascunho', 'running' => 'Em execução', 'completed' => 'Concluída', 'cancelled' => 'Cancelada'] as $value => $label)
                    <option value="{{ $value }}" @selected($filter->status === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end"><x-button type="submit" variant="secondary">Aplicar filtros</x-button></div>
    </form>

    <section aria-labelledby="executive-risk-heading">
        <div class="mb-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-emergency-600">Acompanhamento</p>
            <h2 id="executive-risk-heading" class="mt-1 font-display text-xl font-semibold text-navy-950">Risco e pendências</h2>
            <p class="mt-1 text-sm leading-6 text-ink-500">Indicadores que pedem leitura imediata antes das métricas agregadas de desempenho.</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <x-stats-card label="Automatic fail" :value="$automatic_fail_count" hint="ocorrências finais" icon="M12 9v4m0 4h.01" accent="emergency" />
            <x-stats-card label="Ações vencidas" :value="$overdue_action_count" hint="exigem acompanhamento" icon="M12 9v4m0 4h.01" accent="emergency" />
            <x-stats-card label="Ações abertas" :value="$open_action_count" hint="open + in_progress" icon="M5 13l4 4L19 7" accent="alert" />
        </div>
    </section>

    <section aria-labelledby="executive-performance-heading" class="mt-8">
        <div class="mb-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-ink-500">Desempenho</p>
            <h2 id="executive-performance-heading" class="mt-1 font-display text-xl font-semibold text-navy-950">Visão institucional</h2>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stats-card label="Execuções" :value="$total_executions" :hint="$completed_executions . ' concluídas'" icon="M4 6h16M4 12h16" accent="navy" />
            <x-stats-card label="Avaliações finalizadas" :value="$finalized_assessments" hint="M4 + legado preservado" icon="M9 12l2 2 4-4" accent="clinical" />
            <x-stats-card label="Média final" :value="$average_final_score === null ? '—' : number_format($average_final_score, 1, ',', '.') . '/100'" hint="somente nota existente" icon="M3 12h18" accent="clinical" />
            <x-stats-card label="Taxa de aprovação" :value="$pass_rate === null ? '—' : number_format($pass_rate, 1, ',', '.') . '%'" hint="exclui resultado histórico desconhecido" icon="M5 13l4 4L19 7" accent="navy" />
        </div>
    </section>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <x-card title="Erros críticos observados" subtitle="Ocorrências reais, não catálogo do cenário" accent="emergency">
            @if ($top_observed_errors->isEmpty())
                <x-empty-state title="Nenhum erro crítico observado" description="Não há ocorrência crítica finalizada no período selecionado." icon="alert" class="py-8" />
            @else
                <ol class="space-y-3">
                    @foreach ($top_observed_errors as $label => $count)
                        <li class="flex items-start justify-between gap-4 rounded-lg border border-stone-200 bg-stone-50 px-4 py-3">
                            <span class="text-sm leading-6 text-ink-800">{{ $label }}</span>
                            <x-badge variant="emergency" size="sm">{{ $count }}×</x-badge>
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-card>

        <x-card title="Tendência mensal" subtitle="Execuções dentro do período selecionado" accent="navy">
            @if ($monthly_trend->isEmpty())
                <x-empty-state title="Sem tendência disponível" description="Não há execuções suficientes no período para compor a série mensal." icon="file" class="py-8" />
            @else
                <div class="space-y-3">
                    @foreach ($monthly_trend as $month => $count)
                        <div class="flex items-center justify-between border-b border-stone-100 pb-2 last:border-0">
                            <span class="text-sm font-medium text-ink-700">{{ $month }}</span>
                            <span class="font-mono text-sm font-semibold tabular-nums text-navy-950">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</x-layouts.app>
