@php
$statusLabel = match ($execution->status) {
    'running' => 'Em execução',
    'completed' => 'Concluída',
    'cancelled' => 'Cancelada',
    default => 'Rascunho',
};
$statusVariant = match ($execution->status) {
    'running' => 'alert',
    'completed' => 'clinical',
    'cancelled' => 'emergency',
    default => 'navy',
};
$sectionItems = [
    ['label' => 'Situação', 'href' => '#lifecycle', 'state' => 'current'],
    ['label' => 'Timeline', 'href' => '#timeline'],
    ['label' => 'Equipes', 'href' => '#teams'],
    ['label' => 'Recursos', 'href' => '#resources'],
    ['label' => 'Injects', 'href' => '#injects'],
    ['label' => 'Avaliação', 'href' => '#assessment'],
];
@endphp

<x-layouts.app :current="'scenarios'" :title="'Execução ' . $execution->sequence_number . ' · Tactical Scenario Lab'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => 'Painel', 'href' => route('dashboard')],
            ['label' => 'Cenários', 'href' => route('scenarios.index')],
            ['label' => $execution->scenarioVersion->scenario->title, 'href' => route('scenarios.show', $execution->scenarioVersion->scenario)],
            ['label' => 'Execução ' . $execution->sequence_number],
        ]" />
    </x-slot:breadcrumbs>

    <x-slot:header>
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <x-badge :variant="$statusVariant" size="sm" dot>{{ $statusLabel }}</x-badge>
                    <x-badge variant="navy" size="sm">Versão {{ $execution->scenarioVersion->version_number }}</x-badge>
                    <x-badge variant="neutral" size="sm">Execução #{{ $execution->sequence_number }}</x-badge>
                </div>
                <h1 class="mt-3 font-display text-3xl font-semibold tracking-tight text-navy-950">Cockpit do instrutor</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-500">
                    {{ $execution->scenarioVersion->scenario->title }} · comando operacional, timeline, pessoas, recursos, injects e avaliação no contexto congelado desta execução.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-button href="{{ route('scenarios.show', $execution->scenarioVersion->scenario) }}" variant="secondary">Voltar ao cenário</x-button>

                @if ($canManage && $execution->canStart())
                    <form method="POST" action="{{ route('executions.start', $execution) }}">
                        @csrf
                        @method('PATCH')
                        <x-button type="submit">Iniciar execução</x-button>
                    </form>
                @elseif ($canManage && $execution->canComplete())
                    <form method="POST" action="{{ route('executions.complete', $execution) }}">
                        @csrf
                        @method('PATCH')
                        <x-button type="submit" variant="success">Concluir execução</x-button>
                    </form>
                @endif

                @if ($canManage && $execution->canCancel())
                    <form method="POST" action="{{ route('executions.cancel', $execution) }}">
                        @csrf
                        @method('PATCH')
                        <x-button type="submit" variant="danger">Cancelar</x-button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot:header>

    @if (session('success'))
        <div class="mb-6"><x-alert variant="success" title="Operação concluída">{{ session('success') }}</x-alert></div>
    @endif

    @if ($errors->any())
        <div class="mb-6"><x-alert variant="danger" title="Revise os dados enviados">{{ $errors->first() }}</x-alert></div>
    @endif

    <div class="mb-6">
        <x-section-nav :items="$sectionItems" label="Navegação do cockpit" />
    </div>

    <section id="lifecycle" data-cockpit-region="lifecycle" class="scroll-mt-24">
        <x-card title="Comando da execução" subtitle="Estado, escala e janela temporal da operação" accent="navy">
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Estado</p>
                    <div class="mt-2"><x-badge :variant="$statusVariant" dot>{{ $statusLabel }}</x-badge></div>
                </div>
                <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Escala estimada</p>
                    <p class="mt-1 font-display text-2xl font-semibold tabular-nums text-navy-950">{{ number_format((int) $execution->scenarioVersion->estimated_casualty_count, 0, ',', '.') }}</p>
                </div>
                <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Equipes</p>
                    <p class="mt-1 font-display text-2xl font-semibold tabular-nums text-navy-950">{{ $execution->teams->count() }}</p>
                </div>
                <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Participantes</p>
                    <p class="mt-1 font-display text-2xl font-semibold tabular-nums text-navy-950">{{ $execution->participants->count() }}</p>
                </div>
            </div>
            <dl class="mt-5 grid gap-4 border-t border-stone-100 pt-5 sm:grid-cols-3">
                <div><dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Início</dt><dd class="mt-1 text-sm font-medium text-ink-800">{{ $execution->started_at?->format('d/m/Y H:i:s') ?? 'Ainda não iniciada' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Conclusão</dt><dd class="mt-1 text-sm font-medium text-ink-800">{{ $execution->completed_at?->format('d/m/Y H:i:s') ?? '—' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Mecanismo</dt><dd class="mt-1 text-sm font-medium text-ink-800">{{ $execution->scenarioVersion->mechanism }}</dd></div>
            </dl>
        </x-card>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)]">
        <div class="space-y-6">
            <section id="timeline" data-cockpit-region="timeline" data-history-mode="append-only" class="scroll-mt-24" aria-labelledby="execution-timeline-heading">
                <x-card title="Timeline da execução" subtitle="Registro histórico · somente acréscimo" accent="navy">
                    <h2 id="execution-timeline-heading" class="sr-only">Timeline da execução</h2>
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-navy-100 bg-navy-50/60 px-4 py-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-navy-700">Verdade cronológica</p>
                            <p class="mt-1 text-xs leading-5 text-ink-600">Eventos históricos não são editados nem excluídos. Correções entram como novos registros.</p>
                        </div>
                        <x-badge variant="navy" size="sm">{{ $execution->events->count() }} evento(s)</x-badge>
                    </div>

                    @if ($execution->events->isEmpty())
                        <x-empty-state title="Nenhum evento registrado" description="A timeline começa quando a execução estiver em andamento." icon="clip" class="py-8" />
                    @else
                        <ol class="relative space-y-4 border-l border-stone-200 pl-5">
                            @foreach ($execution->events as $event)
                                <li class="relative rounded-lg border border-stone-200 bg-white px-4 py-4 shadow-sm before:absolute before:-left-[1.55rem] before:top-5 before:h-2.5 before:w-2.5 before:rounded-full before:bg-navy-700 before:ring-4 before:ring-stone-25">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-badge variant="navy" size="sm">{{ $event->kind }}</x-badge>
                                        <time class="text-xs font-semibold tabular-nums text-ink-500">{{ $event->occurred_at->format('d/m/Y H:i:s') }}</time>
                                    </div>
                                    <p class="mt-2 text-sm font-medium leading-6 text-ink-900">{{ $event->summary }}</p>
                                    @if ($event->team || $event->participant)
                                        <p class="mt-1 text-xs text-ink-500">{{ $event->team?->label }} @if ($event->team && $event->participant) · @endif {{ $event->participant?->person?->preferredName() }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    @if ($canManage && $execution->isRunning())
                        <form method="POST" action="{{ route('execution-events.store', $execution) }}" class="mt-6 grid gap-4 border-t border-stone-100 pt-5 md:grid-cols-2">
                            @csrf
                            <div>
                                <label for="event_kind" class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-600">Tipo</label>
                                <select id="event_kind" name="kind" class="mt-1 w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" required>
                                    <option value="observation">Observação</option><option value="action">Ação</option><option value="intervention">Intervenção</option><option value="system">Sistema</option><option value="resource">Recurso</option>
                                </select>
                            </div>
                            <div>
                                <label for="event_time" class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-600">Horário</label>
                                <input id="event_time" name="occurred_at" type="datetime-local" value="{{ now()->format('Y-m-d\TH:i') }}" class="mt-1 w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" required>
                            </div>
                            <div class="md:col-span-2">
                                <label for="event_summary" class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-600">Registro</label>
                                <textarea id="event_summary" name="summary" rows="2" maxlength="500" class="mt-1 w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" placeholder="Descreva objetivamente o que ocorreu." required></textarea>
                            </div>
                            <div class="md:col-span-2"><x-button type="submit">Adicionar à timeline</x-button></div>
                        </form>
                    @endif
                </x-card>
            </section>

            <section id="teams" data-cockpit-region="teams" class="scroll-mt-24">
                <x-card title="Equipes e participantes" subtitle="Composição institucional vinculada exclusivamente a esta execução" accent="clinical">
                    <div class="grid gap-5 lg:grid-cols-2">
                        <div>
                            <h2 class="text-sm font-semibold text-navy-950">Equipes</h2>
                            <div class="mt-3 space-y-2">
                                @forelse ($execution->teams as $team)
                                    <div class="rounded-lg border border-stone-200 bg-stone-50 px-4 py-3">
                                        <p class="text-sm font-semibold text-ink-900">{{ $team->label }}</p>
                                        @if ($team->description)<p class="mt-1 text-xs leading-5 text-ink-500">{{ $team->description }}</p>@endif
                                        <p class="mt-2 text-xs font-medium text-ink-500">{{ $team->participants->count() }} participante(s)</p>
                                    </div>
                                @empty
                                    <p class="text-sm text-ink-500">Nenhuma equipe configurada.</p>
                                @endforelse
                            </div>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-navy-950">Participantes</h2>
                            <div class="mt-3 space-y-2">
                                @forelse ($execution->participants as $participant)
                                    <div class="rounded-lg border border-stone-200 bg-stone-50 px-4 py-3">
                                        <p class="text-sm font-semibold text-ink-900">{{ $participant->person->preferredName() }}</p>
                                        <p class="mt-1 text-xs text-ink-500">{{ $participant->role ?: 'Participante' }} @if ($participant->team) · {{ $participant->team->label }} @endif</p>
                                        <p class="mt-1 text-xs text-ink-500">Vínculo histórico: {{ $participant->unit_name_snapshot ?: 'Sem unidade histórica' }} @if ($participant->position_snapshot) · {{ $participant->position_snapshot }} @endif</p>
                                    </div>
                                @empty
                                    <p class="text-sm text-ink-500">Nenhum participante vinculado.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    @if ($canManage && $execution->canConfigure())
                        <div class="mt-6 grid gap-6 border-t border-stone-100 pt-5 lg:grid-cols-2">
                            <form method="POST" action="{{ route('execution-teams.store', $execution) }}" class="space-y-3">
                                @csrf
                                <h3 class="text-sm font-semibold text-navy-950">Adicionar equipe</h3>
                                <input name="label" type="text" maxlength="100" class="w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" placeholder="Ex.: Equipe Alfa" required>
                                <input name="description" type="text" maxlength="500" class="w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" placeholder="Função ou missão da equipe">
                                <x-button type="submit" variant="secondary">Adicionar equipe</x-button>
                            </form>
                            <form method="POST" action="{{ route('execution-participants.store', $execution) }}" class="space-y-3">
                                @csrf
                                <h3 class="text-sm font-semibold text-navy-950">Adicionar participante</h3>
                                <select name="organization_membership_uuid" class="w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" required>
                                    <option value="">Selecione o vínculo representado</option>
                                    @foreach ($availableMemberships as $membership)
                                        <option value="{{ $membership->uuid }}">
                                            {{ $membership->person->preferredName() }} · {{ $membership->unit?->name ?? 'Sem unidade' }} @if ($membership->position) · {{ $membership->position }} @endif
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs leading-5 text-ink-500">O vínculo selecionado é congelado como contexto histórico desta execução; transferências futuras não reescrevem o registro.</p>
                                <select name="execution_team_uuid" class="w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                                    <option value="">Sem equipe</option>
                                    @foreach ($execution->teams as $team)<option value="{{ $team->uuid }}">{{ $team->label }}</option>@endforeach
                                </select>
                                <input name="role" type="text" maxlength="80" class="w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" placeholder="Função na execução">
                                <x-button type="submit" variant="secondary">Adicionar participante</x-button>
                            </form>
                        </div>
                    @endif
                </x-card>
            </section>
        </div>

        <aside class="space-y-6" aria-label="Controles operacionais da execução">
            <section id="assessment" data-cockpit-region="assessment" class="scroll-mt-24">
                <x-card title="Avaliação & Debriefing" subtitle="Rubrica, evidências, erros críticos, tempos-chave e plano de ação" accent="navy">
                    @if ($execution->assessment)
                        <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-ink-900">Avaliação {{ $execution->assessment->isFinalized() ? 'finalizada' : 'em elaboração' }}</p>
                                    <p class="mt-1 text-xs leading-5 text-ink-500">Registro exclusivo desta execução, com cálculo e debriefing rastreáveis.</p>
                                </div>
                                <x-badge :variant="$execution->assessment->isFinalized() ? 'clinical' : 'navy'" size="sm">{{ $execution->assessment->isFinalized() ? 'finalizada' : 'draft' }}</x-badge>
                            </div>
                            <div class="mt-4"><x-button href="{{ route('assessments.show', $execution->assessment) }}" variant="secondary">Abrir avaliação</x-button></div>
                        </div>
                    @elseif ($canEvaluate)
                        <p class="text-sm leading-6 text-ink-600">Crie a avaliação estruturada desta execução. O sistema iniciará a rubrica a partir dos objetivos da versão publicada.</p>
                        <form method="POST" action="{{ route('assessments.store', $execution) }}" class="mt-4">
                            @csrf
                            <x-button type="submit">Criar avaliação</x-button>
                        </form>
                    @else
                        <p class="text-sm leading-6 text-ink-500">Nenhuma avaliação criada. A criação exige permissão institucional de avaliação.</p>
                    @endif
                </x-card>
            </section>

            <section id="resources" data-cockpit-region="resources" class="scroll-mt-24">
                <x-card title="Recursos" subtitle="Snapshot logístico desta execução" accent="clinical">
                    <div class="space-y-3">
                        @forelse ($execution->resources as $resource)
                            <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-ink-900">{{ $resource->name }}</p>
                                    <x-badge :variant="$resource->status === 'available' ? 'clinical' : ($resource->status === 'depleted' ? 'emergency' : 'neutral')" size="sm">{{ $resource->status }}</x-badge>
                                </div>
                                <p class="mt-2 text-xs tabular-nums text-ink-500">Planejado {{ $resource->planned_quantity }} · disponível {{ $resource->available_quantity }} · usado {{ $resource->used_quantity }}</p>
                                @if ($canManage && $execution->canConfigure())
                                    <form method="POST" action="{{ route('execution-resources.update', $resource) }}" class="mt-3 grid grid-cols-3 gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input name="planned_quantity" type="number" min="0" value="{{ $resource->planned_quantity }}" aria-label="Quantidade planejada de {{ $resource->name }}" class="rounded-md border-stone-300 px-2 text-sm focus:border-navy-500 focus:ring-navy-500" required>
                                        <input name="available_quantity" type="number" min="0" value="{{ $resource->available_quantity }}" aria-label="Quantidade disponível de {{ $resource->name }}" class="rounded-md border-stone-300 px-2 text-sm focus:border-navy-500 focus:ring-navy-500" required>
                                        <input name="used_quantity" type="number" min="0" value="{{ $resource->used_quantity }}" aria-label="Quantidade usada de {{ $resource->name }}" class="rounded-md border-stone-300 px-2 text-sm focus:border-navy-500 focus:ring-navy-500" required>
                                        <select name="status" aria-label="Estado de {{ $resource->name }}" class="col-span-2 rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                                            <option value="available" @selected($resource->status === 'available')>Disponível</option><option value="unavailable" @selected($resource->status === 'unavailable')>Indisponível</option><option value="depleted" @selected($resource->status === 'depleted')>Esgotado</option>
                                        </select>
                                        <x-button type="submit" size="sm" variant="secondary">Salvar</x-button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-ink-500">Nenhum recurso previsto na versão publicada.</p>
                        @endforelse
                    </div>
                </x-card>
            </section>

            <section id="injects" data-cockpit-region="injects" class="scroll-mt-24">
                <x-card title="Injects do instrutor" subtitle="Mudanças controladas de contexto com rastreabilidade" accent="alert">
                    <div class="space-y-3">
                        @forelse ($execution->injects as $inject)
                            <div class="rounded-lg border border-stone-200 bg-white p-4 shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div><p class="text-sm font-semibold text-ink-900">{{ $inject->label }}</p><p class="mt-1 text-xs leading-5 text-ink-500">{{ $inject->content }}</p></div>
                                    <x-badge :variant="$inject->status === 'delivered' ? 'clinical' : ($inject->status === 'cancelled' ? 'neutral' : 'alert')" size="sm">{{ $inject->status }}</x-badge>
                                </div>
                                @if ($canManage && $inject->isPlanned())
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @if ($execution->isRunning())
                                            <form method="POST" action="{{ route('execution-injects.deliver', $inject) }}">@csrf @method('PATCH') <x-button type="submit" size="sm">Entregar inject</x-button></form>
                                        @endif
                                        @if ($execution->canConfigure())
                                            <form method="POST" action="{{ route('execution-injects.cancel', $inject) }}">@csrf @method('PATCH') <x-button type="submit" size="sm" variant="ghost">Cancelar</x-button></form>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-ink-500">Nenhum inject planejado.</p>
                        @endforelse
                    </div>

                    @if ($canManage && $execution->canConfigure())
                        <form method="POST" action="{{ route('execution-injects.store', $execution) }}" class="mt-5 space-y-3 border-t border-stone-100 pt-5">
                            @csrf
                            <input name="label" type="text" maxlength="150" class="w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" placeholder="Título do inject" required>
                            <textarea name="content" rows="3" maxlength="5000" class="w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" placeholder="Nova informação, restrição ou mudança de contexto" required></textarea>
                            <input name="planned_offset_seconds" type="number" min="0" class="w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" placeholder="Offset planejado em segundos (opcional)">
                            <x-button type="submit" variant="secondary">Planejar inject</x-button>
                        </form>
                    @endif
                </x-card>
            </section>
        </aside>
    </div>
</x-layouts.app>
