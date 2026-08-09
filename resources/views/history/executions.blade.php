<x-layouts.app :current="'history'" :title="'Histórico de execuções · Tactical Scenario Lab'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => 'Painel', 'href' => route('dashboard')],
            ['label' => 'Histórico de execuções'],
        ]" />
    </x-slot:breadcrumbs>

    <x-slot:header>
        <div>
            <x-badge variant="navy" size="sm" dot>Relatório institucional</x-badge>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">Histórico de execuções</h1>
            <p class="mt-1.5 max-w-3xl text-sm leading-6 text-ink-500">Execuções da organização ativa com avaliação, unidades históricas e pendências corretivas preservadas.</p>
        </div>
    </x-slot:header>

    <form method="GET" action="{{ route('execution-history.index') }}" class="mb-6 grid gap-3 rounded-xl border border-stone-200 bg-white p-4 shadow-sm sm:grid-cols-3 lg:grid-cols-4">
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

    <section aria-labelledby="execution-history-heading">
        <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-ink-500">Registro histórico</p>
                <h2 id="execution-history-heading" class="mt-1 font-display text-xl font-semibold text-navy-950">Execuções</h2>
                <p class="mt-1 text-sm text-ink-500">Mais recentes primeiro; unidades exibidas são snapshots históricos.</p>
            </div>
            <span class="text-xs font-medium text-ink-500">{{ $executions->total() }} registro(s)</span>
        </div>

        <x-table
            label="Histórico de execuções"
            :empty="$executions->isEmpty()"
            empty-title="Nenhuma execução encontrada"
            empty-description="Ajuste o período ou os filtros para consultar outro recorte institucional."
        >
            <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-[0.08em] text-ink-500">
                <tr>
                    <th scope="col" class="px-5 py-3">Execução</th>
                    <th scope="col" class="px-5 py-3">Unidades históricas</th>
                    <th scope="col" class="px-5 py-3">Estado</th>
                    <th scope="col" class="px-5 py-3">Avaliação</th>
                    <th scope="col" class="px-5 py-3">Erros</th>
                    <th scope="col" class="px-5 py-3">Ações abertas</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100 bg-white">
                @foreach ($executions as $execution)
                    @php
                        $units = $execution->participants
                            ->pluck('unit_name_snapshot')
                            ->filter()
                            ->unique()
                            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                            ->values();
                    @endphp
                    <tr class="transition-colors hover:bg-stone-50/70">
                        <td class="px-5 py-4 align-top">
                            <a href="{{ route('executions.show', $execution) }}" class="font-semibold text-navy-900 hover:text-navy-700">{{ $execution->scenarioVersion->scenario->title }}</a>
                            <p class="mt-1 text-xs text-ink-500">Execução #{{ $execution->sequence_number }} · versão {{ $execution->scenarioVersion->version_number }}</p>
                        </td>
                        <td class="px-5 py-4 align-top text-ink-700">
                            @if ($units->isEmpty())
                                <span>Sem unidade histórica</span>
                            @else
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($units as $unit)<x-badge variant="neutral" size="sm">{{ $unit }}</x-badge>@endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-5 py-4 align-top"><x-status-pill :status="$execution->status" /></td>
                        <td class="px-5 py-4 align-top">
                            @if ($execution->assessment)
                                <p class="font-medium text-ink-900">{{ $execution->assessment->status }}</p>
                                <p class="mt-1 text-xs text-ink-500">{{ $execution->assessment->final_score !== null ? number_format((float) $execution->assessment->final_score, 2, ',', '.') . '/100' : 'Sem nota' }} · {{ $execution->assessment->result ?: 'Sem classificação histórica' }}</p>
                            @else
                                <span class="text-ink-500">Sem avaliação</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 align-top font-mono tabular-nums text-ink-800">{{ (int) $execution->critical_error_count }}</td>
                        <td class="px-5 py-4 align-top font-mono tabular-nums text-ink-800">{{ (int) $execution->open_action_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>

        @if ($executions->hasPages())
            <div class="mt-5">{{ $executions->links() }}</div>
        @endif
    </section>
</x-layouts.app>
