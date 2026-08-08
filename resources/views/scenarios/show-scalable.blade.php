@php
$catalog = is_array($scenario->critical_errors) ? $scenario->critical_errors : [];
$observed = is_array($scenario->observed_critical_errors) ? $scenario->observed_critical_errors : [];
$estimate = $version?->estimated_casualty_count ?? $scenario->estimated_casualty_count ?? $scenario->casualties;
$individualCount = (int) ($version?->victims_count ?? 0);
$cohortCount = (int) ($version?->cohorts_count ?? 0);
$statusLabel = match ($scenario->status) {
    'running' => 'Em execução',
    'completed' => 'Concluído',
    default => 'Rascunho',
};
$statusVariant = match ($scenario->status) {
    'running' => 'alert',
    'completed' => 'clinical',
    default => 'neutral',
};
$threatVariant = match ($scenario->threat_level) {
    'ativa' => 'emergency',
    'potencial' => 'alert',
    default => 'neutral',
};
@endphp

<x-layouts.app :current="'scenarios'" :title="$scenario->title . ' · Tactical Scenario Lab'">
    <script>
        try { localStorage.removeItem('tsl:new-scenario'); } catch (_) {}
    </script>

    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => 'Painel', 'href' => route('dashboard')],
            ['label' => 'Cenários', 'href' => route('scenarios.index')],
            ['label' => 'Cenário #' . $scenario->id],
        ]" />
    </x-slot:breadcrumbs>

    <x-slot:header>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <x-badge :variant="$statusVariant" size="sm" dot>{{ $statusLabel }}</x-badge>
                    <x-badge :variant="$threatVariant" size="sm">Ameaça {{ $scenario->threat_level }}</x-badge>
                    @if ($version)
                        <x-badge variant="navy" size="sm">Versão {{ $version->version_number }}</x-badge>
                    @endif
                </div>
                <h1 class="mt-3 font-display text-3xl font-semibold tracking-tight text-navy-950">{{ $scenario->title }}</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-500">
                    {{ $scenario->environment }} · {{ $scenario->mechanism }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @if ($scenario->isDraft())
                    <form method="POST" action="{{ route('scenarios.execute', $scenario) }}">
                        @csrf
                        <x-button type="submit" variant="danger">Iniciar execução</x-button>
                    </form>
                @endif
                <x-button href="{{ route('scenarios.index') }}" variant="secondary">Voltar</x-button>
            </div>
        </div>
    </x-slot:header>

    @if (session('error'))
        <div class="mb-6">
            <x-alert variant="danger" title="Ação não permitida">{{ session('error') }}</x-alert>
        </div>
    @endif

    @if (session('success'))
        <div class="mb-6">
            <x-alert variant="success" title="Operação concluída">{{ session('success') }}</x-alert>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,.65fr)]">
        <div class="space-y-6">
            <x-card title="Escala do incidente" subtitle="Estimativa operacional separada do nível de detalhe" accent="clinical">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-clinical-200 bg-clinical-50/70 p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.13em] text-clinical-700">Estimativa total de vítimas</p>
                        <p class="mt-2 font-display text-4xl font-semibold tabular-nums text-navy-950">{{ number_format((int) $estimate, 0, ',', '.') }}</p>
                        <p class="mt-2 text-sm leading-5 text-ink-600">Escala estimada do incidente. Este valor não equivale à quantidade de registros individuais.</p>
                    </div>

                    <div class="rounded-lg border border-stone-200 bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.13em] text-ink-500">Representações detalhadas</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <x-badge variant="navy">{{ $individualCount }} individuais</x-badge>
                            <x-badge variant="neutral">{{ $cohortCount }} cohorts</x-badge>
                        </div>
                        <p class="mt-3 text-sm leading-5 text-ink-600">Vítimas críticas podem ser individualizadas; grupos semelhantes podem ser agregados em cohorts sem explosão de registros.</p>
                    </div>
                </div>
            </x-card>

            <x-card title="Objetivos de aprendizagem" subtitle="Resultados esperados para a versão atual" accent="clinical">
                @if (is_array($scenario->learning_objectives) && count($scenario->learning_objectives))
                    <ol class="space-y-3">
                        @foreach ($scenario->learning_objectives as $index => $objective)
                            <li class="flex gap-3 rounded-md bg-stone-25 p-3 ring-1 ring-inset ring-stone-100">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-clinical-600 text-xs font-semibold text-white">{{ $index + 1 }}</span>
                                <span class="text-sm leading-6 text-ink-800">{{ $objective }}</span>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="text-sm text-ink-500">Nenhum objetivo definido.</p>
                @endif
            </x-card>

            <x-card title="Ações esperadas" subtitle="Sequência operacional sugerida" accent="navy">
                @if (is_array($scenario->expected_actions) && count($scenario->expected_actions))
                    <ol class="space-y-3">
                        @foreach ($scenario->expected_actions as $index => $action)
                            <li class="flex gap-3">
                                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-navy-900 text-xs font-semibold text-white">{{ $index + 1 }}</span>
                                <span class="text-sm leading-6 text-ink-800">{{ $action }}</span>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="text-sm text-ink-500">Nenhuma ação esperada definida.</p>
                @endif
            </x-card>

            <x-card title="Erros críticos a monitorar" subtitle="Catálogo previsto; erros observados permanecem separados" accent="emergency">
                @if (count($catalog))
                    <ul class="space-y-2.5">
                        @foreach ($catalog as $error)
                            <li class="rounded-md border border-emergency-100 bg-emergency-50/50 px-3 py-2.5 text-sm leading-6 text-ink-800">{{ $error }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-ink-500">Nenhum erro crítico catalogado.</p>
                @endif
            </x-card>

            @if ($scenario->isCompleted() && count($observed))
                <x-card title="Erros observados" subtitle="Ocorrências efetivamente marcadas durante a execução" accent="alert">
                    <ul class="space-y-2.5">
                        @foreach ($observed as $error)
                            <li class="rounded-md border border-alert-100 bg-alert-50/60 px-3 py-2.5 text-sm leading-6 text-ink-800">{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-card>
            @endif

            @if ($scenario->canBeEvaluated())
                <x-card title="Avaliação e debriefing" subtitle="Fluxo legado preservado até o M4" accent="clinical">
                    @if ($scenario->isCompleted())
                        <div class="mb-6 rounded-lg border border-clinical-200 bg-clinical-50/70 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.13em] text-clinical-700">Pontuação final</p>
                            <p class="mt-1 font-display text-3xl font-semibold text-navy-950">{{ $scenario->score ?? 0 }}<span class="text-base font-medium text-ink-500">/100</span></p>
                            @if ($scenario->debrief_notes)
                                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-ink-700">{{ $scenario->debrief_notes }}</p>
                            @endif
                        </div>
                    @endif

                    <form method="POST" action="{{ route('scenarios.evaluate', $scenario) }}" class="space-y-5">
                        @csrf

                        <x-input
                            label="Nota da execução (0 a 100)"
                            name="score"
                            type="number"
                            min="0"
                            max="100"
                            step="1"
                            required
                            :value="old('score', $scenario->score)"
                            :error="$errors->first('score')"
                            hint="Avalie a execução; a separação estrutural completa será feita no M4."
                        />

                        @if (count($catalog))
                            <fieldset>
                                <legend class="mb-1 text-sm font-medium text-ink-900">Erros observados nesta execução</legend>
                                <p class="mb-3 text-xs leading-5 text-ink-500">Marque somente itens do catálogo que realmente ocorreram.</p>
                                <div class="grid gap-2 md:grid-cols-2">
                                    @foreach ($catalog as $index => $error)
                                        @php
                                            $id = 'observed-error-' . $index;
                                            $checked = in_array($error, old('observed_critical_errors', $observed), true);
                                        @endphp
                                        <label for="{{ $id }}" class="flex cursor-pointer items-start gap-3 rounded-md border border-stone-200 bg-white px-3 py-3 text-sm text-ink-800 hover:border-alert-300 has-[:checked]:border-alert-500 has-[:checked]:bg-alert-50">
                                            <input id="{{ $id }}" type="checkbox" name="observed_critical_errors[]" value="{{ $error }}" @checked($checked) class="mt-0.5 h-4 w-4 shrink-0 accent-alert-600">
                                            <span>{{ $error }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endif

                        <x-textarea
                            label="Notas do debriefing"
                            name="debrief_notes"
                            rows="6"
                            placeholder="Pontos fortes, oportunidades de melhoria e decisões-chave."
                            :value="old('debrief_notes', $scenario->debrief_notes)"
                            :error="$errors->first('debrief_notes')"
                        />

                        <div class="flex justify-end border-t border-stone-100 pt-4">
                            <x-button type="submit" variant="success">{{ $scenario->isCompleted() ? 'Atualizar avaliação' : 'Finalizar avaliação' }}</x-button>
                        </div>
                    </form>
                </x-card>
            @else
                <x-alert variant="info" title="Avaliação bloqueada">
                    Inicie a execução para liberar a avaliação deste cenário.
                </x-alert>
            @endif
        </div>

        <aside class="space-y-6">
            <x-card title="Ficha da versão" accent="navy">
                <dl class="space-y-4 text-sm">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Ambiente</dt>
                        <dd class="mt-1 text-ink-900">{{ $version?->environment ?? $scenario->environment }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Ameaça</dt>
                        <dd class="mt-1 capitalize text-ink-900">{{ $version?->threat_level ?? $scenario->threat_level }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Mecanismo</dt>
                        <dd class="mt-1 text-ink-900">{{ $version?->mechanism ?? $scenario->mechanism }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Estimativa</dt>
                        <dd class="mt-1 font-semibold tabular-nums text-navy-950">{{ number_format((int) $estimate, 0, ',', '.') }} vítimas</dd>
                    </div>
                </dl>
            </x-card>

            <x-card title="Recursos declarados" accent="navy">
                @php $resources = $version?->resources ?? $scenario->resources ?? []; @endphp
                @if (is_array($resources) && count($resources))
                    <div class="flex flex-wrap gap-2">
                        @foreach ($resources as $resource)
                            <x-badge variant="neutral" size="sm">{{ $resource }}</x-badge>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-ink-500">Nenhum recurso declarado.</p>
                @endif
            </x-card>

            <x-card title="Fronteira do M2" accent="clinical">
                <p class="text-sm leading-6 text-ink-700">
                    Esta fase versiona a definição e a escala das vítimas. Execuções independentes, equipes, timeline e injects entram no M3; assessment estruturado e debriefing avançado entram no M4.
                </p>
            </x-card>
        </aside>
    </div>
</x-layouts.app>
