@php
$statusMap = [
    'draft'     => ['label' => 'Rascunho',     'idx' => 1],
    'running'   => ['label' => 'Em execução',  'idx' => 2],
    'completed' => ['label' => 'Concluído',    'idx' => 3],
];
$currentIdx = $statusMap[$scenario->status]['idx'] ?? 1;

$timeline = [
    [
        'title'    => 'Cenário criado',
        'subtitle' => 'Rascunho gerado a partir do wizard',
        'time'     => optional($scenario->created_at)->format('d/m · H:i'),
        'status'   => $currentIdx >= 1 ? 'done' : 'pending',
    ],
    [
        'title'    => 'Execução iniciada',
        'subtitle' => 'Instrutor conduz a simulação com a turma',
        'time'     => $scenario->started_at ? $scenario->started_at->format('d/m · H:i') : null,
        'status'   => $currentIdx === 2 ? 'current' : ($currentIdx > 2 ? 'done' : 'pending'),
    ],
    [
        'title'    => 'Avaliação e debrief',
        'subtitle' => 'Score de 0 a 100 e notas do debriefing',
        'time'     => $scenario->completed_at ? $scenario->completed_at->format('d/m · H:i') : null,
        'status'   => $currentIdx === 3 ? 'done' : 'pending',
    ],
];

$catalog  = is_array($scenario->critical_errors) ? $scenario->critical_errors : [];
$observed = is_array($scenario->observed_critical_errors) ? $scenario->observed_critical_errors : [];
@endphp

<x-layouts.app :current="'scenarios'" :title="$scenario->title . ' · Tactical Scenario Lab'">

    {{-- A view de um cenário existente prova que a criação deu certo:
         aqui é seguro descartar o rascunho do wizard. --}}
    <script>
        try { localStorage.removeItem('tsl:new-scenario'); } catch (_) {}
    </script>


    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => 'Painel',    'href'  => route('dashboard')],
            ['label' => 'Cenários',  'href'  => route('scenarios.index')],
            ['label' => 'Cenário #' . $scenario->id],
        ]" />
    </x-slot:breadcrumbs>

    <x-slot:header>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <x-status-pill :status="$scenario->status" />
                    <x-badge variant="{{ $scenario->threat_level === 'ativa' ? 'emergency' : ($scenario->threat_level === 'potencial' ? 'alert' : 'neutral') }}" size="sm">
                        Ameaça {{ $scenario->threat_level }}
                    </x-badge>
                    <span class="font-mono text-xs text-ink-500">#{{ str_pad($scenario->id, 4, '0', STR_PAD_LEFT) }}</span>
                </div>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">{{ $scenario->title }}</h1>
                <p class="mt-1.5 text-sm text-ink-500">
                    Ambiente: <span class="font-medium text-ink-900">{{ $scenario->environment }}</span>
                    · Mecanismo: <span class="font-medium text-ink-900">{{ $scenario->mechanism }}</span>
                    · {{ $scenario->casualties }} vítima{{ $scenario->casualties > 1 ? 's' : '' }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                @if ($scenario->isDraft())
                    <form
                        method="POST"
                        action="{{ route('scenarios.execute', $scenario) }}"
                        x-data="{ busy: false }"
                        x-on:submit="busy = true"
                    >
                        @csrf
                        <x-button type="submit" variant="danger" x-bind:disabled="busy">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M5 3v18l14-9L5 3z"/></svg>
                            <span x-text="busy ? 'Iniciando…' : 'Iniciar execução'">Iniciar execução</span>
                        </x-button>
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

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- ============ Coluna principal (2/3) ============ --}}
        <div class="space-y-6 lg:col-span-2">

            <x-card title="Objetivos de aprendizagem" subtitle="Baseados em MARCH" accent="clinical">
                <x-checklist :items="$scenario->learning_objectives ?? []" variant="clinical" />
            </x-card>

            <x-card title="Ações esperadas" subtitle="Sequência sugerida pela cinemática do trauma" accent="navy">
                <ol class="space-y-3">
                    @foreach (($scenario->expected_actions ?? []) as $index => $action)
                        <li class="flex items-start gap-3 rounded-md bg-stone-25 p-3 ring-1 ring-inset ring-stone-100">
                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-navy-900 text-xs font-semibold text-white tabular-nums">{{ $index + 1 }}</span>
                            <p class="text-sm leading-relaxed text-ink-900">{{ $action }}</p>
                        </li>
                    @endforeach
                </ol>
            </x-card>

            {{-- Erros críticos — separando CATÁLOGO (a monitorar) vs OBSERVADOS --}}
            <x-card title="Erros críticos a monitorar" subtitle="Catálogo gerado — o que a equipe NÃO pode fazer" accent="emergency">
                <ul class="space-y-2.5">
                    @foreach ($catalog as $err)
                        <li class="flex items-start gap-3">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-emergency-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
                            <span class="text-sm leading-relaxed text-ink-700">{{ $err }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-card>

            @if ($scenario->isCompleted() && count($observed))
                <x-card title="Erros observados durante a execução" subtitle="Marcados no debrief" accent="alert">
                    <ul class="space-y-2.5">
                        @foreach ($observed as $err)
                            <li class="flex items-start gap-3">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-alert-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M6 6l12 12M6 18L18 6"/></svg>
                                <span class="text-sm leading-relaxed text-ink-900">{{ $err }}</span>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endif

            {{-- ============ Avaliação ============
                 Só aparece a partir de `running`. Em `draft`, a UX manda
                 o usuário para o botão "Iniciar execução" no cabeçalho.
            --}}
            @if ($scenario->canBeEvaluated())
                <x-card title="Avaliação e debriefing" accent="clinical">
                    @if ($scenario->isCompleted())
                        <div class="mb-6">
                            <x-score-indicator :score="$scenario->score ?? 0" label="Pontuação final" />
                        </div>
                    @endif

                    <form
                        method="POST"
                        action="{{ route('scenarios.evaluate', $scenario) }}"
                        x-data="{ confirmFinal: false, busy: false }"
                        x-on:submit="if (! confirmFinal) { $event.preventDefault(); confirmFinal = true; } else { busy = true; }"
                        class="space-y-5"
                    >
                        @csrf

                        <x-input
                            label="Nota da execução (0 a 100)"
                            name="score"
                            type="number"
                            min="0" max="100" step="1"
                            required
                            :value="old('score', $scenario->score)"
                            :error="$errors->first('score')"
                            hint="Considere aderência ao MARCH, sequência de ações e ausência dos erros críticos."
                        />

                        {{-- Erros observados: seleção a partir do catálogo. Distinct + Rule::in no controller. --}}
                        @if (count($catalog))
                            <fieldset aria-describedby="observed-hint">
                                <legend class="mb-1.5 block text-sm font-medium text-ink-900">
                                    Erros observados nesta execução
                                </legend>
                                <p id="observed-hint" class="mb-3 text-xs text-ink-500">
                                    Marque apenas os erros que realmente ocorreram. Nada aqui altera o catálogo original.
                                </p>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    @foreach ($catalog as $i => $err)
                                        @php
                                            $obsId = 'observed-' . $i;
                                            $checked = in_array($err, old('observed_critical_errors', $observed), true);
                                        @endphp
                                        <label
                                            for="{{ $obsId }}"
                                            class="group flex cursor-pointer items-start gap-3 rounded-md border border-stone-200 bg-white px-3 py-2.5 transition-colors hover:border-alert-300 has-[:checked]:border-alert-500 has-[:checked]:bg-alert-50"
                                        >
                                            <input
                                                id="{{ $obsId }}"
                                                type="checkbox"
                                                name="observed_critical_errors[]"
                                                value="{{ $err }}"
                                                @checked($checked)
                                                class="mt-0.5 h-4 w-4 shrink-0 accent-alert-600"
                                            >
                                            <span class="text-sm text-ink-900">{{ $err }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('observed_critical_errors.*')
                                    <p class="mt-2 text-xs text-emergency-600">{{ $message }}</p>
                                @enderror
                            </fieldset>
                        @endif

                        <x-textarea
                            label="Notas do debriefing"
                            name="debrief_notes"
                            rows="6"
                            placeholder="Registre pontos fortes, oportunidades de melhoria e decisões-chave observadas."
                            :value="old('debrief_notes', $scenario->debrief_notes)"
                            :error="$errors->first('debrief_notes')"
                        />

                        <div class="flex flex-col-reverse items-stretch justify-end gap-3 border-t border-stone-100 pt-4 sm:flex-row sm:items-center">
                            <template x-if="confirmFinal">
                                <p class="text-xs text-emergency-700" role="status">
                                    Confirma o fechamento da avaliação? Clique novamente em <strong>Finalizar</strong>.
                                </p>
                            </template>
                            <x-button
                                type="submit"
                                variant="success"
                                x-bind:disabled="busy"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-4 w-4"><path d="M5 13l4 4L19 7"/></svg>
                                <span x-text="busy ? 'Enviando…' : (confirmFinal ? 'Finalizar' : '{{ $scenario->isCompleted() ? 'Atualizar avaliação' : 'Finalizar avaliação' }}')">
                                    {{ $scenario->isCompleted() ? 'Atualizar avaliação' : 'Finalizar avaliação' }}
                                </span>
                            </x-button>
                        </div>
                    </form>
                </x-card>
            @else
                <x-alert variant="info" title="Avaliação bloqueada">
                    Enquanto o cenário estiver em rascunho, a avaliação fica desabilitada. Clique em <strong>Iniciar execução</strong> acima para liberar o formulário.
                </x-alert>
            @endif
        </div>

        {{-- ============ Coluna lateral (1/3) ============ --}}
        <aside class="space-y-6">

            <x-card title="Progresso do cenário" accent="navy">
                <x-timeline :items="$timeline" />
            </x-card>

            <x-card title="Ficha resumida" padding="none">
                <dl class="divide-y divide-stone-100 text-sm">
                    <div class="grid grid-cols-3 gap-3 px-6 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Ambiente</dt>
                        <dd class="col-span-2 text-ink-900">{{ $scenario->environment }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-3 px-6 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Ameaça</dt>
                        <dd class="col-span-2 text-ink-900 capitalize">{{ $scenario->threat_level }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-3 px-6 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Vítimas</dt>
                        <dd class="col-span-2 text-ink-900 tabular-nums">{{ $scenario->casualties }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-3 px-6 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Trauma</dt>
                        <dd class="col-span-2 text-ink-900">{{ $scenario->mechanism }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-3 px-6 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Recursos</dt>
                        <dd class="col-span-2 text-ink-900">
                            @if (is_array($scenario->resources) && count($scenario->resources))
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($scenario->resources as $r)
                                        <x-badge variant="neutral" size="sm">{{ $r }}</x-badge>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-ink-500">Nenhum recurso declarado</span>
                            @endif
                        </dd>
                    </div>
                    <div class="grid grid-cols-3 gap-3 px-6 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Criado</dt>
                        <dd class="col-span-2 font-mono text-xs text-ink-500">{{ optional($scenario->created_at)->format('d/m/Y H:i') }}</dd>
                    </div>
                    @if ($scenario->started_at)
                        <div class="grid grid-cols-3 gap-3 px-6 py-3">
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Iniciado</dt>
                            <dd class="col-span-2 font-mono text-xs text-ink-500">{{ $scenario->started_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    @endif
                    @if ($scenario->completed_at)
                        <div class="grid grid-cols-3 gap-3 px-6 py-3">
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Concluído</dt>
                            <dd class="col-span-2 font-mono text-xs text-ink-500">{{ $scenario->completed_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>

            <x-alert variant="warning" title="Uso educacional">
                Ferramenta de simulação. Não substitui protocolos institucionais, treinamento certificado nem decisão clínica em campo.
            </x-alert>
        </aside>
    </div>

</x-layouts.app>
