@php
$catalog = is_array($scenario->critical_errors) ? $scenario->critical_errors : [];
$observed = is_array($scenario->observed_critical_errors) ? $scenario->observed_critical_errors : [];
$estimate = $version?->estimated_casualty_count ?? $scenario->estimated_casualty_count ?? $scenario->casualties;
$individualCount = (int) ($version?->victims_count ?? 0);
$cohortCount = (int) ($version?->cohorts_count ?? 0);
$versionStatus = $version?->publication_status ?? 'draft';
@endphp

<x-layouts.app :current="'scenarios'" :title="$scenario->title . ' · Tactical Scenario Lab'">
    <script>
        try { localStorage.removeItem('tsl:new-scenario'); } catch (_) {}
    </script>

    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => 'Painel', 'href' => route('dashboard')],
            ['label' => 'Cenários', 'href' => route('scenarios.index')],
            ['label' => 'Detalhes'],
        ]" />
    </x-slot:breadcrumbs>

    <x-slot:header>
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <x-badge variant="navy" size="sm" dot>Scenario Core</x-badge>
                    @if ($version)
                        <x-badge :variant="$versionStatus === 'published' ? 'clinical' : 'alert'" size="sm">
                            Versão {{ $version->version_number }} · {{ $versionStatus === 'published' ? 'publicada' : 'rascunho' }}
                        </x-badge>
                    @endif
                </div>
                <h1 class="mt-3 font-display text-3xl font-semibold tracking-tight text-navy-950">{{ $scenario->title }}</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-500">
                    {{ $scenario->environment }} · {{ $scenario->mechanism }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-button href="{{ route('scenario-templates.index') }}" variant="secondary">Templates</x-button>
                <x-button href="{{ route('scenarios.index') }}" variant="secondary">Voltar aos cenários</x-button>
            </div>
        </div>
    </x-slot:header>

    @if (session('error'))
        <div class="mb-6"><x-alert variant="danger" title="Ação não permitida">{{ session('error') }}</x-alert></div>
    @endif
    @if (session('success'))
        <div class="mb-6"><x-alert variant="success" title="Operação concluída">{{ session('success') }}</x-alert></div>
    @endif
    @if ($errors->any())
        <div class="mb-6"><x-alert variant="danger" title="Revise os dados">{{ $errors->first() }}</x-alert></div>
    @endif

    @if ($version)
        <div class="mb-6">
            <x-card title="Controle de versão e execuções" subtitle="Uma definição publicada pode originar múltiplos treinamentos independentes" accent="navy">
                <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-badge :variant="$versionStatus === 'published' ? 'clinical' : 'alert'" dot>
                                {{ $versionStatus === 'published' ? 'Pronta para execução' : 'Aguardando publicação' }}
                            </x-badge>
                            <span class="text-sm text-ink-500">Versão {{ $version->version_number }}</span>
                        </div>
                        <p class="mt-3 max-w-3xl text-sm leading-6 text-ink-600">
                            @if ($versionStatus === 'published')
                                A definição está congelada como referência histórica. Cada nova execução terá seu próprio estado, participantes, timeline, injects e recursos.
                            @else
                                Revise a definição antes de publicar. Após a publicação, alterações de conteúdo devem gerar uma nova versão em vez de reescrever o histórico.
                            @endif
                        </p>
                    </div>

                    @if ($canManage)
                        @if ($versionStatus === 'draft')
                            <form method="POST" action="{{ route('scenario-versions.publish', $version) }}">
                                @csrf
                                @method('PATCH')
                                <x-button type="submit">Publicar versão</x-button>
                            </form>
                        @elseif ($versionStatus === 'published')
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('executions.store', $version) }}">
                                    @csrf
                                    <x-button type="submit">Nova execução</x-button>
                                </form>
                                <form method="POST" action="{{ route('scenario-templates.store', $version) }}">
                                    @csrf
                                    <input type="hidden" name="name" value="{{ $scenario->title }}">
                                    <x-button type="submit" variant="secondary">Salvar como template</x-button>
                                </form>
                            </div>
                        @endif
                    @endif
                </div>

                <div class="mt-6 border-t border-stone-100 pt-5">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="text-sm font-semibold text-navy-950">Histórico de execuções</h3>
                        <span class="text-xs font-medium text-ink-500">{{ $version->executions->count() }} registro(s)</span>
                    </div>

                    @if ($version->executions->isEmpty())
                        <div class="mt-3 rounded-lg border border-dashed border-stone-300 bg-stone-50 px-5 py-6 text-center">
                            <p class="text-sm font-semibold text-ink-700">Nenhuma execução desta versão.</p>
                            <p class="mt-1 text-xs text-ink-500">Publique a definição e crie a primeira execução quando o treinamento estiver planejado.</p>
                        </div>
                    @else
                        <div class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($version->executions as $execution)
                                @php
                                $executionLabel = match ($execution->status) {
                                    'running' => 'Em execução',
                                    'completed' => 'Concluída',
                                    'cancelled' => 'Cancelada',
                                    default => 'Rascunho',
                                };
                                $executionVariant = match ($execution->status) {
                                    'running' => 'alert',
                                    'completed' => 'clinical',
                                    'cancelled' => 'emergency',
                                    default => 'navy',
                                };
                                @endphp
                                <a href="{{ route('executions.show', $execution) }}" class="rounded-lg border border-stone-200 bg-white p-4 shadow-sm transition hover:border-navy-300 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy-500">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm font-semibold text-navy-950">Execução #{{ $execution->sequence_number }}</span>
                                        <x-badge :variant="$executionVariant" size="sm" dot>{{ $executionLabel }}</x-badge>
                                    </div>
                                    <p class="mt-2 text-xs text-ink-500">Criada em {{ $execution->created_at->format('d/m/Y H:i') }}</p>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </x-card>
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
                <ol class="space-y-3">
                    @forelse (($version?->learning_objectives ?? $scenario->learning_objectives ?? []) as $index => $objective)
                        <li class="flex gap-3 rounded-md bg-stone-25 p-3 ring-1 ring-inset ring-stone-100">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-clinical-600 text-xs font-semibold text-white">{{ $index + 1 }}</span>
                            <span class="text-sm leading-6 text-ink-800">{{ $objective }}</span>
                        </li>
                    @empty
                        <li class="text-sm text-ink-500">Nenhum objetivo definido.</li>
                    @endforelse
                </ol>
            </x-card>

            <x-card title="Ações esperadas" subtitle="Sequência operacional sugerida" accent="navy">
                <ol class="space-y-3">
                    @forelse (($version?->expected_actions ?? $scenario->expected_actions ?? []) as $index => $action)
                        <li class="flex gap-3">
                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-navy-900 text-xs font-semibold text-white">{{ $index + 1 }}</span>
                            <span class="text-sm leading-6 text-ink-800">{{ $action }}</span>
                        </li>
                    @empty
                        <li class="text-sm text-ink-500">Nenhuma ação esperada definida.</li>
                    @endforelse
                </ol>
            </x-card>

            <x-card title="Erros críticos a monitorar" subtitle="Catálogo previsto; ocorrências observadas permanecem separadas" accent="emergency">
                <ul class="space-y-2.5">
                    @forelse (($version?->critical_errors ?? $catalog) as $error)
                        <li class="rounded-md border border-emergency-100 bg-emergency-50/50 px-3 py-2.5 text-sm leading-6 text-ink-800">{{ $error }}</li>
                    @empty
                        <li class="text-sm text-ink-500">Nenhum erro crítico catalogado.</li>
                    @endforelse
                </ul>
            </x-card>

            @if ($scenario->isCompleted() && count($observed))
                <x-card title="Erros observados" subtitle="Registro histórico legado preservado somente para consulta" accent="alert">
                    <ul class="space-y-2.5">
                        @foreach ($observed as $error)
                            <li class="rounded-md border border-alert-100 bg-alert-50/60 px-3 py-2.5 text-sm leading-6 text-ink-800">{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-card>
            @endif

            @if ($scenario->isCompleted() && ($scenario->score !== null || $scenario->debrief_notes))
                <x-card title="Avaliação histórica" subtitle="Registro legado somente para consulta; novas avaliações pertencem a cada execução" accent="clinical">
                    @if ($scenario->score !== null)
                        <div class="rounded-lg border border-clinical-200 bg-clinical-50/70 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.13em] text-clinical-700">Pontuação histórica</p>
                            <p class="mt-1 font-display text-3xl font-semibold text-navy-950">{{ $scenario->score }}<span class="text-base font-medium text-ink-500">/100</span></p>
                        </div>
                    @endif
                    @if ($scenario->debrief_notes)
                        <div class="mt-4 rounded-lg border border-stone-200 bg-white p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.13em] text-ink-500">Debrief histórico</p>
                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-ink-700">{{ $scenario->debrief_notes }}</p>
                        </div>
                    @endif
                    <p class="mt-4 text-sm leading-6 text-ink-600">Para registrar ou consultar avaliação estruturada, abra a execução correspondente no histórico acima e acesse Avaliação &amp; Debriefing.</p>
                </x-card>
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
                <div class="flex flex-wrap gap-2">
                    @forelse ($resources as $resource)
                        <x-badge variant="neutral" size="sm">{{ $resource }}</x-badge>
                    @empty
                        <p class="text-sm text-ink-500">Nenhum recurso declarado.</p>
                    @endforelse
                </div>
            </x-card>

            <x-card title="Arquitetura ativa" accent="clinical">
                <ol class="space-y-3 text-sm leading-6 text-ink-700">
                    <li><span class="font-semibold text-navy-950">1. Scenario</span> · identidade institucional.</li>
                    <li><span class="font-semibold text-navy-950">2. ScenarioVersion</span> · definição histórica imutável após publicação.</li>
                    <li><span class="font-semibold text-navy-950">3. ScenarioExecution</span> · realização concreta, repetível e independente.</li>
                </ol>
            </x-card>
        </aside>
    </div>
</x-layouts.app>