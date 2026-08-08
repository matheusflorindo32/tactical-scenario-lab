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
$isEditable = $canEvaluate && $assessment->isDraft();
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
        <div class="mb-6"><x-alert variant="success" title="Operação concluída">{{ session('success') }}</x-alert></div>
    @endif

    @if ($errors->any())
        <div class="mb-6"><x-alert variant="danger" title="Revise os dados enviados">{{ $errors->first() }}</x-alert></div>
    @endif

    @if ($assessment->isFinalized())
        <div class="mb-6">
            <x-alert variant="success" title="Registro institucional finalizado">
                O conteúdo da avaliação está congelado. Apenas o status operacional das ações do plano pode evoluir nos fluxos autorizados.
            </x-alert>
        </div>
    @endif

    <div class="space-y-6">
        <x-card title="Resumo da avaliação" subtitle="Composição transparente e reproduzível do resultado" accent="navy">
            <dl class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ([
                    ['Nota-base', $assessment->base_score ?? '—'],
                    ['Penalidades', $assessment->penalty_points ?? '—'],
                    ['Ajuste do avaliador', ($assessment->evaluator_adjustment > 0 ? '+' : '').$assessment->evaluator_adjustment],
                    ['Nota final', $assessment->final_score ?? '—'],
                ] as [$label, $value])
                    <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">{{ $label }}</dt>
                        <dd class="mt-2 font-display text-2xl font-semibold tabular-nums text-navy-950">{{ $value }}</dd>
                    </div>
                @endforeach
                <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Resultado</dt>
                    <dd class="mt-2"><x-badge :variant="$resultVariant" dot>{{ $resultLabel }}</x-badge></dd>
                </div>
            </dl>

            @if ($assessment->automatic_fail)
                <div class="mt-4">
                    <x-alert variant="danger" title="Reprovação automática">
                        Uma ocorrência crítica determinou reprovação. A nota numérica permanece visível para preservar a transparência do cálculo.
                    </x-alert>
                </div>
            @endif

            @if ($isEditable)
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
            <x-card title="Rubrica" subtitle="Critérios ponderados, notas e evidências objetivas" accent="clinical">
                <div class="space-y-4">
                    @forelse ($assessment->criteria as $criterion)
                        <div class="rounded-lg border border-stone-200 bg-white p-4 shadow-sm">
                            @if ($isEditable)
                                <form method="POST" action="{{ route('assessment-criteria.update', $criterion) }}" class="space-y-3">
                                    @csrf
                                    @method('PATCH')
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <div class="md:col-span-2">
                                            <label class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-600">Critério</label>
                                            <input name="label" type="text" maxlength="200" value="{{ $criterion->label }}" class="mt-1 w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" required>
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-600">Peso (%)</label>
                                            <input name="weight" type="number" min="0.01" max="100" step="0.01" value="{{ $criterion->weight }}" class="mt-1 w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" required>
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-600">Nota (0–100)</label>
                                            <input name="score" type="number" min="0" max="100" step="0.01" value="{{ $criterion->score }}" class="mt-1 w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-600">Descrição</label>
                                            <textarea name="description" rows="2" maxlength="5000" class="mt-1 w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">{{ $criterion->description }}</textarea>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-600">Notas do avaliador</label>
                                            <textarea name="evaluator_notes" rows="2" maxlength="5000" class="mt-1 w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">{{ $criterion->evaluator_notes }}</textarea>
                                        </div>
                                    </div>
                                    <x-button type="submit" size="sm" variant="secondary">Salvar critério</x-button>
                                </form>
                                <form method="POST" action="{{ route('assessment-criteria.destroy', $criterion) }}" class="mt-2">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" size="sm" variant="ghost">Remover critério</x-button>
                                </form>
                            @else
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-ink-900">{{ $criterion->label }}</p>
                                        @if ($criterion->description)<p class="mt-1 text-xs leading-5 text-ink-500">{{ $criterion->description }}</p>@endif
                                    </div>
                                    <div class="flex gap-2">
                                        <x-badge variant="neutral" size="sm">Peso {{ $criterion->weight }}%</x-badge>
                                        <x-badge variant="navy" size="sm">Nota {{ $criterion->score ?? '—' }}</x-badge>
                                    </div>
                                </div>
                            @endif

                            <div class="mt-4 space-y-2 border-t border-stone-100 pt-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-600">Evidências</p>
                                @forelse ($criterion->evidence as $evidence)
                                    <div class="rounded-md bg-stone-50 p-3">
                                        <p class="text-xs leading-5 text-ink-700">{{ $evidence->statement }}</p>
                                        <p class="mt-1 text-[11px] text-ink-500">{{ $evidence->observed_at?->format('d/m/Y H:i:s') }} @if($evidence->event) · Timeline: {{ $evidence->event->summary }} @endif</p>
                                        @if ($isEditable)
                                            <form method="POST" action="{{ route('assessment-evidence.destroy', $evidence) }}" class="mt-2">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-semibold text-ink-500 underline">Remover evidência</button>
                                            </form>
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-xs text-ink-500">Nenhuma evidência registrada.</p>
                                @endforelse
                            </div>

                            @if ($isEditable)
                                <form method="POST" action="{{ route('assessment-evidence.store', $criterion) }}" class="mt-4 grid gap-3 border-t border-stone-100 pt-3 md:grid-cols-2">
                                    @csrf
                                    <div>
                                        <label class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-600">Evento da timeline (opcional)</label>
                                        <select name="execution_event_uuid" class="mt-1 w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                                            <option value="">Sem vínculo com evento</option>
                                            @foreach ($events as $event)
                                                <option value="{{ $event->uuid }}">{{ $event->occurred_at->format('H:i:s') }} · {{ $event->summary }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-600">Momento observado</label>
                                        <input name="observed_at" type="datetime-local" value="{{ ($execution->completed_at ?? now())->format('Y-m-d\TH:i') }}" class="mt-1 w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" required>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-600">Evidência objetiva</label>
                                        <textarea name="statement" rows="2" maxlength="5000" class="mt-1 w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" required></textarea>
                                    </div>
                                    <div class="md:col-span-2"><x-button type="submit" size="sm">Adicionar evidência</x-button></div>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-ink-500">Nenhum critério definido. A rubrica precisa ser completada antes da finalização.</p>
                    @endforelse
                </div>

                @if ($isEditable)
                    <form method="POST" action="{{ route('assessment-criteria.store', $assessment) }}" class="mt-5 grid gap-3 border-t border-stone-100 pt-5 md:grid-cols-2">
                        @csrf
                        <div class="md:col-span-2"><h3 class="text-sm font-semibold text-navy-950">Adicionar critério</h3></div>
                        <input name="label" type="text" maxlength="200" placeholder="Nome do critério" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" required>
                        <input name="weight" type="number" min="0.01" max="100" step="0.01" placeholder="Peso (%)" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" required>
                        <textarea name="description" rows="2" maxlength="5000" placeholder="Descrição" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500 md:col-span-2"></textarea>
                        <input name="score" type="number" min="0" max="100" step="0.01" placeholder="Nota opcional" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                        <input name="evaluator_notes" type="text" maxlength="5000" placeholder="Notas do avaliador" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                        <div class="md:col-span-2"><x-button type="submit" variant="secondary">Adicionar critério</x-button></div>
                    </form>
                @endif
            </x-card>

            <x-card title="Erros críticos observados" subtitle="Catálogo da versão separado das ocorrências desta execução" accent="alert">
                <div class="space-y-3">
                    @forelse ($assessment->criticalErrorOccurrences as $occurrence)
                        <div class="rounded-lg border border-stone-200 bg-white p-4 shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-ink-900">{{ $occurrence->catalog_label_snapshot }}</p>
                                    @if ($occurrence->notes)<p class="mt-1 text-xs leading-5 text-ink-500">{{ $occurrence->notes }}</p>@endif
                                </div>
                                <x-badge :variant="$occurrence->rule === 'automatic_fail' ? 'emergency' : ($occurrence->rule === 'penalty' ? 'alert' : 'neutral')" size="sm">{{ $occurrence->rule }}</x-badge>
                            </div>
                            @if ((float) $occurrence->penalty_points > 0)<p class="mt-2 text-xs font-medium text-ink-600">Penalidade: {{ $occurrence->penalty_points }} ponto(s)</p>@endif
                            @if ($isEditable)
                                <form method="POST" action="{{ route('critical-error-occurrences.destroy', $occurrence) }}" class="mt-2">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-ink-500 underline">Remover ocorrência</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-ink-500">Nenhum erro crítico observado foi registrado.</p>
                    @endforelse
                </div>

                @if ($isEditable)
                    <form method="POST" action="{{ route('critical-error-occurrences.store', $assessment) }}" class="mt-5 grid gap-3 border-t border-stone-100 pt-5 md:grid-cols-2">
                        @csrf
                        <select name="catalog_label_snapshot" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" required>
                            <option value="">Erro do catálogo</option>
                            @foreach ($criticalCatalog as $criticalError)<option value="{{ $criticalError }}">{{ $criticalError }}</option>@endforeach
                        </select>
                        <select name="rule" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" required>
                            <option value="record">Somente registrar</option>
                            <option value="penalty">Aplicar penalidade</option>
                            <option value="automatic_fail">Reprovação automática</option>
                        </select>
                        <input name="penalty_points" type="number" min="0" max="100" step="0.01" value="0" placeholder="Pontos de penalidade" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                        <select name="execution_event_uuid" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                            <option value="">Sem evento vinculado</option>
                            @foreach ($events as $event)<option value="{{ $event->uuid }}">{{ $event->occurred_at->format('H:i:s') }} · {{ $event->summary }}</option>@endforeach
                        </select>
                        <input name="observed_at" type="datetime-local" value="{{ ($execution->completed_at ?? now())->format('Y-m-d\TH:i') }}" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" required>
                        <input name="notes" type="text" maxlength="5000" placeholder="Observações" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                        <div class="md:col-span-2"><x-button type="submit" variant="secondary">Registrar erro observado</x-button></div>
                    </form>
                @endif
            </x-card>
        </div>

        <x-card title="Tempos-chave" subtitle="Marcos temporais calculados pelo servidor a partir do início da execução" accent="navy">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($assessment->keyTimes as $keyTime)
                    <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                        <p class="text-sm font-semibold text-ink-900">{{ $keyTime->label }}</p>
                        <p class="mt-2 text-xs tabular-nums text-ink-600">Tempo decorrido: {{ $keyTime->elapsed_seconds }} s</p>
                        @if ($keyTime->reference_seconds !== null)<p class="mt-1 text-xs tabular-nums text-ink-500">Referência: {{ $keyTime->reference_seconds }} s</p>@endif
                        @if ($isEditable)
                            <form method="POST" action="{{ route('key-times.destroy', $keyTime) }}" class="mt-2">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-semibold text-ink-500 underline">Remover tempo</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-ink-500">Nenhum tempo-chave registrado.</p>
                @endforelse
            </div>

            @if ($isEditable)
                <form method="POST" action="{{ route('key-times.store', $assessment) }}" class="mt-5 grid gap-3 border-t border-stone-100 pt-5 md:grid-cols-2 xl:grid-cols-4">
                    @csrf
                    <input name="label" type="text" maxlength="200" placeholder="Ex.: Primeiro contato" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" required>
                    <input name="occurred_at" type="datetime-local" value="{{ ($execution->completed_at ?? now())->format('Y-m-d\TH:i') }}" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" required>
                    <input name="reference_seconds" type="number" min="0" placeholder="Referência em segundos" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                    <input name="notes" type="text" maxlength="5000" placeholder="Notas" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                    <div class="xl:col-span-4"><x-button type="submit" variant="secondary">Registrar tempo-chave</x-button></div>
                </form>
            @endif
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
                            @if ($isEditable)
                                <form method="POST" action="{{ route('debrief-entries.update', $entry) }}" class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="kind" value="{{ $kind }}">
                                    <textarea name="content" rows="3" maxlength="5000" class="w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" required>{{ $entry->content }}</textarea>
                                    <div class="mt-2 flex gap-2"><x-button type="submit" size="sm" variant="secondary">Salvar</x-button></div>
                                </form>
                                <form method="POST" action="{{ route('debrief-entries.destroy', $entry) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-ink-500 underline">Remover registro</button>
                                </form>
                            @else
                                <p class="rounded-lg border border-stone-200 bg-stone-50 p-4 text-sm leading-6 text-ink-700">{{ $entry->content }}</p>
                            @endif
                        @empty
                            <p class="text-sm text-ink-500">Nenhum registro nesta categoria.</p>
                        @endforelse
                    </div>

                    @if ($isEditable)
                        <form method="POST" action="{{ route('debrief-entries.store', $assessment) }}" class="mt-4 border-t border-stone-100 pt-4">
                            @csrf
                            <input type="hidden" name="kind" value="{{ $kind }}">
                            <textarea name="content" rows="3" maxlength="5000" class="w-full rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" placeholder="Adicionar {{ strtolower($title) }}" required></textarea>
                            <div class="mt-2"><x-button type="submit" size="sm" variant="secondary">Adicionar</x-button></div>
                        </form>
                    @endif
                </x-card>
            @endforeach
        </div>

        <x-card title="Plano de ação" subtitle="Ações corretivas com responsável, prazo e acompanhamento operacional" accent="alert">
            <div class="space-y-4">
                @forelse ($assessment->debrief?->actionItems ?? collect() as $actionItem)
                    <div class="rounded-lg border border-stone-200 bg-white p-4 shadow-sm">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-ink-900">{{ $actionItem->action }}</p>
                                <p class="mt-1 text-xs text-ink-500">Responsável: {{ $actionItem->responsiblePerson?->preferredName() ?? $actionItem->responsible_label }} · Prazo: {{ $actionItem->due_date?->format('d/m/Y') }}</p>
                            </div>
                            <x-badge variant="neutral" size="sm">{{ $actionItem->status }}</x-badge>
                        </div>

                        @if ($isEditable)
                            <form method="POST" action="{{ route('action-items.update', $actionItem) }}" class="mt-4 grid gap-3 border-t border-stone-100 pt-4 md:grid-cols-2">
                                @csrf
                                @method('PATCH')
                                <textarea name="action" rows="2" maxlength="5000" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500 md:col-span-2" required>{{ $actionItem->action }}</textarea>
                                <select name="responsible_person_uuid" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                                    <option value="">Responsável por texto/equipe</option>
                                    @foreach ($people as $person)<option value="{{ $person->uuid }}" @selected($actionItem->responsible_person_id === $person->id)>{{ $person->preferredName() }}</option>@endforeach
                                </select>
                                <input name="responsible_label" type="text" maxlength="200" value="{{ $actionItem->responsible_label }}" placeholder="Ex.: Coordenação" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                                <input name="due_date" type="date" value="{{ $actionItem->due_date?->format('Y-m-d') }}" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" required>
                                <input name="notes" type="text" maxlength="5000" value="{{ $actionItem->notes }}" placeholder="Notas" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                                <div class="md:col-span-2"><x-button type="submit" size="sm" variant="secondary">Salvar ação</x-button></div>
                            </form>
                            <form method="POST" action="{{ route('action-items.destroy', $actionItem) }}" class="mt-2">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-semibold text-ink-500 underline">Remover ação</button>
                            </form>
                        @elseif ($canEvaluate && in_array($actionItem->status, ['open', 'in_progress'], true))
                            <form method="POST" action="{{ route('action-items.transition', $actionItem) }}" class="mt-4 flex flex-wrap items-end gap-2 border-t border-stone-100 pt-4">
                                @csrf
                                @method('PATCH')
                                <div>
                                    <label class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-600">Atualizar status</label>
                                    <select name="status" class="mt-1 rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                                        @if ($actionItem->status === 'open')<option value="in_progress">Em andamento</option>@endif
                                        <option value="completed">Concluída</option>
                                        <option value="cancelled">Cancelada</option>
                                    </select>
                                </div>
                                <x-button type="submit" size="sm" variant="secondary">Atualizar</x-button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-ink-500">Nenhuma ação corretiva registrada.</p>
                @endforelse
            </div>

            @if ($isEditable)
                <form method="POST" action="{{ route('action-items.store', $assessment) }}" class="mt-5 grid gap-3 border-t border-stone-100 pt-5 md:grid-cols-2">
                    @csrf
                    <textarea name="action" rows="2" maxlength="5000" placeholder="Ação corretiva ou de manutenção" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500 md:col-span-2" required></textarea>
                    <select name="responsible_person_uuid" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                        <option value="">Responsável por texto/equipe</option>
                        @foreach ($people as $person)<option value="{{ $person->uuid }}">{{ $person->preferredName() }}</option>@endforeach
                    </select>
                    <input name="responsible_label" type="text" maxlength="200" placeholder="Ex.: Coordenação de instrução" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                    <input name="due_date" type="date" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500" required>
                    <input name="notes" type="text" maxlength="5000" placeholder="Notas" class="rounded-md border-stone-300 text-sm focus:border-navy-500 focus:ring-navy-500">
                    <div class="md:col-span-2"><x-button type="submit" variant="secondary">Adicionar ação</x-button></div>
                </form>
            @endif
        </x-card>

        @if ($isEditable)
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
