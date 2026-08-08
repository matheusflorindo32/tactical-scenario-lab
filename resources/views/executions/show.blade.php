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
                    {{ $execution->scenarioVersion->scenario->title }} · coordenação operacional, pessoas, recursos, injects, timeline e avaliação no contexto desta execução.
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

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)]">
        <div class="space-y-6">
            <x-card title="Situação da execução" subtitle="Contexto operacional congelado na versão publicada" accent="navy">
                <dl class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Estado</dt>
                        <dd class="mt-2"><x-badge :variant="$statusVariant" dot>{{ $statusLabel }}</x-badge></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Escala estimada</dt>
                        <dd class="mt-1 font-display text-2xl font-semibold tabular-nums text-navy-950">{{ number_format((int) $execution->scenarioVersion->estimated_casualty_count, 0, ',', '.') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Equipes</dt>
                        <dd class="mt-1 font-display text-2xl font-semibold tabular-nums text-navy-950">{{ $execution->teams->count() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Participantes</dt>
                        <dd class="mt-1 font-display text-2xl font-semibold tabular-nums text-navy-950">{{ $execution->participants->count() }}</dd>
                    </div>
                </dl>
                <div class="mt-6 grid gap-4 border-t border-stone-100 pt-5 sm:grid-cols-3">
                    <div><p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Início</p><p class="mt-1 text-sm font-medium text-ink-800">{{ $execution->started_at?->format('d/m/Y H:i:s') ?? 'Ainda não iniciada' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Conclusão</p><p class="mt-1 text-sm font-medium text-ink-800">{{ $execution->completed_at?->format('d/m/Y H:i:s') ?? '—' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Mecanismo</p><p class="mt-1 text-sm font-medium text-ink-800">{{ $execution->scenarioVersion->mechanism }}</p></div>
                </div>
            </x-card>

            <x-card title="Timeline da execução" subtitle="Registro cronológico append-only; eventos históricos não são reescritos" accent="navy">
                @if ($execution->events->isEmpty())
                    <div class="rounded-lg border border-dashed border-stone-300 bg-stone-50 px-5 py-8 text-center">
                        <p class="text-sm font-semibold text-ink-700">Nenhum evento registrado.</p>
                        <p class="mt-1 text-xs text-ink-500">A timeline começa quando a execução estiver em andamento.</p>
                    </div>
                @else
                    <ol class="space-y-4">
                        @foreach ($execution->events as $event)
                            <li class="rounded-lg border border-stone-200 bg-white px-4 py-4 shadow-sm">
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

            <x-card title="Equipes e participantes" subtitle="Composição institucional vinculada exclusivamente a esta execução" accent="clinical">
                <div class="grid gap-5 lg:grid-cols-2">
                    <div>
                        <h3 class="text-sm font-semibold text-navy-950">Equipes</h3>
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
                        <h3 class="text-sm font-semibold text-navy-950">Participantes</h3>
                        <div class="mt-3 space-y-2">
                            @forelse ($execution->participants as $participant)
                                <div class="rounded-lg border border-stone-200 bg-stone-50 px-4 py-3">
                                    <p class="text-sm font-semibold text-ink-900">{{ $participant->person->preferredName() }}</p>
                                    <p class="mt-1 text-xs text-ink-500">{{ $participant->role ?: 'Participante' }} @if ($participant->team) · {{ $participant->team->label }} @endif</p>
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
                            <select name="person_uuid" class="w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" required>
                                <option value="">Selecione uma pessoa</option>
                                @foreach ($people as $person)<option value="{{ $person->uuid }}">{{ $person->preferredName() }}</option>@endforeach
                            </select>
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
        </div>

        <aside class="space-y-6">
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
        </aside>
    </div>
</x-layouts.app>
