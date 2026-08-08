@php
$stepLabels = [
    'Contexto',
    'Ambiente',
    'Ameaça',
    'Vítimas',
    'Trauma',
    'Recursos',
    'Objetivos',
    'Revisão',
];

$threatOptions = [
    'controlada' => 'Controlada — cena estabilizada, sem risco iminente.',
    'potencial'  => 'Potencial — risco identificado, sem confronto ativo.',
    'ativa'      => 'Ativa — hostilidade em curso ou perigo imediato.',
];

$resources = ['Kit IFAK','Maca','DEA','Oxigênio','Rádio','Viatura','Torniquete','Cobertor térmico'];
@endphp

<x-layouts.app :current="'new'" :title="'Novo cenário · Tactical Scenario Lab'">

    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => 'Painel',    'href'  => route('scenarios.index')],
            ['label' => 'Cenários',  'href'  => route('scenarios.index')],
            ['label' => 'Novo cenário'],
        ]" />
    </x-slot:breadcrumbs>

    <x-slot:header>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <x-badge variant="navy" size="sm" dot>Wizard · 8 passos</x-badge>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">Configurar novo cenário</h1>
                <p class="mt-1.5 max-w-2xl text-sm text-ink-500">Responda em blocos curtos. O rascunho é salvo automaticamente no navegador — se sair e voltar, retomamos de onde você parou.</p>
            </div>
        </div>
    </x-slot:header>

    @php
        // Erros vindos do backend precisam apontar para o passo correto.
        $firstErrorField = null;
        $firstErrorMessage = null;
        if ($errors->any()) {
            $firstErrorField = array_key_first($errors->messages());
            // Normaliza "resources.0" → "resources" para o mapa fieldToStep.
            $firstErrorField = strtok($firstErrorField, '.');
            $firstErrorMessage = $errors->first();
        }
    @endphp

    <div
        x-data="wizard({
            steps: 8,
            storageKey: 'tsl:new-scenario',
            version: 2,
            arrayFields: ['resources'],
            fieldToStep: {
                context_label: 1,
                audience: 1,
                environment: 2,
                threat_level: 3,
                casualties: 4,
                mechanism: 5,
                resources: 6,
                learning_extra: 7,
            },
            validators: {
                2: (d) => (d.environment && d.environment.trim().length > 0)
                    ? true : 'Descreva o ambiente da cena.',
                3: (d) => (d.threat_level && ['controlada','potencial','ativa'].includes(d.threat_level))
                    ? true : 'Selecione o nível de ameaça.',
                4: (d) => (Number.isInteger(d.casualties) && d.casualties >= 1 && d.casualties <= 10)
                    ? true : 'Informe entre 1 e 10 vítimas.',
                5: (d) => (d.mechanism && d.mechanism.trim().length > 0)
                    ? true : 'Descreva o mecanismo do trauma.',
            },
            initialErrorField: @json($firstErrorField),
            initialErrorMessage: @json($firstErrorMessage),
        })"
        x-init="
            if (!data.threat_level) data.threat_level = 'controlada';
            if (!data.casualties)   data.casualties   = 1;
            if (!data.audience)     data.audience     = 'aph';
        "
        class="space-y-6"
    >
        <x-card padding="lg">
            <x-slot:actions>
                <button
                    type="button"
                    x-on:click="if (confirm('Descartar rascunho salvo?')) reset()"
                    class="text-xs font-medium text-ink-500 hover:text-emergency-600"
                >
                    Descartar rascunho
                </button>
            </x-slot:actions>

            <nav aria-label="Progresso do wizard" class="mb-6">
                <ol class="flex items-center gap-1 overflow-x-auto pb-1">
                    @foreach ($stepLabels as $index => $label)
                        @php $n = $index + 1; @endphp
                        <li class="flex min-w-0 flex-1 items-center gap-3">
                            <button
                                type="button"
                                x-on:click="goTo({{ $n }})"
                                class="group flex min-w-0 items-center gap-2.5 rounded-md py-1.5 pl-1 pr-2 focus-visible:outline-none"
                                x-bind:aria-current="step === {{ $n }} ? 'step' : 'false'"
                            >
                                <span
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold transition-colors"
                                    x-bind:class="
                                        step === {{ $n }}
                                            ? 'bg-navy-900 text-white ring-4 ring-navy-100'
                                            : (step > {{ $n }}
                                                ? 'bg-clinical-500 text-white'
                                                : 'bg-stone-100 text-ink-500 ring-1 ring-stone-200')
                                    "
                                >
                                    <template x-if="step > {{ $n }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="h-3.5 w-3.5"><path d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                    <template x-if="step <= {{ $n }}">
                                        <span>{{ $n }}</span>
                                    </template>
                                </span>
                                <span
                                    class="truncate text-[11px] font-semibold uppercase tracking-[0.1em]"
                                    x-bind:class="
                                        step === {{ $n }}
                                            ? 'text-navy-900'
                                            : (step > {{ $n }} ? 'text-ink-500' : 'text-ink-300')
                                    "
                                >{{ $label }}</span>
                            </button>
                            @if (! $loop->last)
                                <span
                                    class="h-px flex-1 transition-colors"
                                    x-bind:class="step > {{ $n }} ? 'bg-clinical-500' : 'bg-stone-200'"
                                ></span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>

            <div>
                <div class="mb-1.5 flex items-center justify-between text-xs">
                    <span class="font-medium text-ink-700">Progresso</span>
                    <span class="font-mono text-ink-500" x-text="`${progress()}%`"></span>
                </div>
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-stone-100" role="progressbar" x-bind:aria-valuenow="progress()" aria-valuemin="0" aria-valuemax="100">
                    <div class="h-full bg-navy-600 transition-all" x-bind:style="`width: ${progress()}%`"></div>
                </div>
            </div>
        </x-card>

        <form
            method="POST"
            action="{{ route('scenarios.store') }}"
            x-on:submit="onSubmit($event)"
            class="space-y-6"
            novalidate
        >
            @csrf

            {{-- Erro do passo atual (client-side) — próximo ao formulário --}}
            <template x-if="stepErrors[step]">
                <div class="rounded-md border border-emergency-200 bg-emergency-50 p-3 text-sm text-emergency-700" role="alert">
                    <p x-text="stepErrors[step]"></p>
                </div>
            </template>

            {{-- =========================== Passo 1 · Contexto =========================== --}}
            <x-card padding="lg" x-show="step === 1" x-cloak accent="navy">
                <div class="mb-6">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-navy-700">Passo 1 de 8</p>
                    <h2 class="mt-1 font-display text-xl font-semibold text-navy-900">Contexto do treinamento</h2>
                    <p class="mt-1 text-sm text-ink-500">Enquadre o cenário para a turma. Isso vira o cabeçalho da ficha.</p>
                </div>

                <div class="grid gap-4">
                    <x-input
                        label="Nome interno do cenário"
                        name="context_label"
                        hint="Opcional. Ex.: Simulado 2º semestre, turma bombeiros civis."
                        placeholder="Simulado 2º semestre · turma A"
                        x-model="data.context_label"
                    />
                    <x-select
                        label="Público-alvo"
                        name="audience"
                        :options="[
                            'aph'         => 'APH — atendimento pré-hospitalar',
                            'brigadista'  => 'Brigadistas de emergência',
                            'seguranca'   => 'Segurança pública',
                            'tccc'        => 'Cuidados táticos em combate (TCCC)',
                            'primeiros'   => 'Primeiros socorros — leigos',
                        ]"
                        selected="aph"
                        x-model="data.audience"
                    />
                </div>
            </x-card>

            {{-- =========================== Passo 2 · Ambiente =========================== --}}
            <x-card padding="lg" x-show="step === 2" x-cloak accent="navy">
                <div class="mb-6">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-navy-700">Passo 2 de 8</p>
                    <h2 class="mt-1 font-display text-xl font-semibold text-navy-900">Ambiente da cena</h2>
                    <p class="mt-1 text-sm text-ink-500">Onde a ocorrência acontece — isso influencia a segurança da cena e o acesso.</p>
                </div>

                <x-input
                    label="Descrição do ambiente"
                    name="environment"
                    :value="old('environment')"
                    :error="$errors->first('environment')"
                    required
                    placeholder="Beco urbano, rodovia BR, edifício residencial, mata fechada..."
                    hint="Máximo 100 caracteres. Seja específico o bastante para a turma visualizar."
                    x-model="data.environment"
                    maxlength="100"
                />
            </x-card>

            {{-- =========================== Passo 3 · Ameaça =========================== --}}
            <x-card padding="lg" x-show="step === 3" x-cloak accent="alert">
                <div class="mb-6">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-alert-700">Passo 3 de 8</p>
                    <h2 class="mt-1 font-display text-xl font-semibold text-navy-900">Nível de ameaça</h2>
                    <p class="mt-1 text-sm text-ink-500">Determina se a primeira ação esperada será estabilizar a cena ou já iniciar a avaliação.</p>
                </div>

                <fieldset class="space-y-3">
                    <legend class="sr-only">Nível de ameaça</legend>
                    @foreach ($threatOptions as $value => $desc)
                        <label class="flex cursor-pointer items-start gap-3 rounded-md border border-stone-200 bg-white p-4 transition-colors hover:border-navy-300 has-[:checked]:border-navy-500 has-[:checked]:bg-navy-50">
                            <input
                                type="radio"
                                name="threat_level"
                                value="{{ $value }}"
                                x-model="data.threat_level"
                                class="mt-0.5 h-4 w-4 shrink-0 accent-navy-700"
                                @if($loop->first) checked @endif
                            >
                            <span>
                                <span class="block text-sm font-semibold text-navy-900 capitalize">{{ $value }}</span>
                                <span class="mt-0.5 block text-xs text-ink-500">{{ $desc }}</span>
                            </span>
                        </label>
                    @endforeach
                </fieldset>
                @error('threat_level')<p class="mt-2 text-xs text-emergency-600">{{ $message }}</p>@enderror
            </x-card>

            {{-- =========================== Passo 4 · Vítimas =========================== --}}
            <x-card padding="lg" x-show="step === 4" x-cloak accent="navy">
                <div class="mb-6">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-navy-700">Passo 4 de 8</p>
                    <h2 class="mt-1 font-display text-xl font-semibold text-navy-900">Vítimas envolvidas</h2>
                    <p class="mt-1 text-sm text-ink-500">Uma a dez. Cenários com múltiplas vítimas mudam a lógica de triagem.</p>
                </div>

                <div x-data="{ n: parseInt(($el.__x?.$data.data?.casualties) || 1) || 1 }">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center rounded-md ring-1 ring-inset ring-stone-300">
                            <button type="button" x-on:click="if (data.casualties > 1) data.casualties--" class="h-11 w-11 text-lg font-semibold text-ink-700 hover:bg-stone-100" aria-label="Diminuir">−</button>
                            <input
                                type="number"
                                name="casualties"
                                min="1" max="10"
                                x-model.number="data.casualties"
                                class="h-11 w-16 border-0 bg-transparent text-center font-display text-lg font-semibold text-navy-900 focus:outline-none focus:ring-0"
                                required
                            >
                            <button type="button" x-on:click="if (data.casualties < 10) data.casualties++" class="h-11 w-11 text-lg font-semibold text-ink-700 hover:bg-stone-100" aria-label="Aumentar">+</button>
                        </div>
                        <p class="text-sm text-ink-500" x-text="`${data.casualties} vítima${data.casualties > 1 ? 's' : ''} no cenário.`"></p>
                    </div>
                    @error('casualties')<p class="mt-2 text-xs text-emergency-600">{{ $message }}</p>@enderror
                </div>
            </x-card>

            {{-- =========================== Passo 5 · Trauma =========================== --}}
            <x-card padding="lg" x-show="step === 5" x-cloak accent="emergency">
                <div class="mb-6">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emergency-700">Passo 5 de 8</p>
                    <h2 class="mt-1 font-display text-xl font-semibold text-navy-900">Mecanismo do trauma</h2>
                    <p class="mt-1 text-sm text-ink-500">Descreva a cinemática. Ex.: ferimento penetrante, explosão, atropelamento, queda de altura.</p>
                </div>

                <x-input
                    label="Mecanismo"
                    name="mechanism"
                    :value="old('mechanism')"
                    :error="$errors->first('mechanism')"
                    required
                    placeholder="Ferimento penetrante em membro inferior..."
                    hint="Máximo 150 caracteres."
                    x-model="data.mechanism"
                    maxlength="150"
                />
            </x-card>

            {{-- =========================== Passo 6 · Recursos =========================== --}}
            <x-card padding="lg" x-show="step === 6" x-cloak accent="navy">
                <div class="mb-6">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-navy-700">Passo 6 de 8</p>
                    <h2 class="mt-1 font-display text-xl font-semibold text-navy-900">Recursos disponíveis</h2>
                    <p class="mt-1 text-sm text-ink-500">Marque o que a equipe tem em mãos. Recursos ausentes viram parte do desafio.</p>
                </div>

                <fieldset class="grid gap-2 sm:grid-cols-2" aria-describedby="resources-hint">
                    <legend class="sr-only">Recursos disponíveis para a equipe</legend>
                    <p id="resources-hint" class="sr-only">Selecione um ou mais recursos. Múltipla seleção permitida.</p>

                    @foreach ($resources as $index => $resource)
                        @php
                            // ID único por item — evita colisão entre labels/inputs
                            $slug = 'resource-' . $index . '-' . \Illuminate\Support\Str::slug($resource);
                        @endphp
                        <label
                            for="{{ $slug }}"
                            class="group flex cursor-pointer items-center gap-3 rounded-md border border-stone-200 bg-white px-4 py-3 transition-colors hover:border-navy-300 has-[:checked]:border-navy-500 has-[:checked]:bg-navy-50 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-navy-500"
                        >
                            <input
                                id="{{ $slug }}"
                                type="checkbox"
                                name="resources[]"
                                value="{{ $resource }}"
                                x-model="data.resources"
                                class="h-4 w-4 shrink-0 accent-navy-700"
                            >
                            <span class="text-sm font-medium text-ink-900">{{ $resource }}</span>
                            {{-- Marca dupla (checkmark) para não depender só de cor --}}
                            <svg
                                class="ms-auto h-4 w-4 text-navy-600 opacity-0 transition-opacity group-has-[:checked]:opacity-100"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                aria-hidden="true"
                            >
                                <path d="M5 13l4 4L19 7"/>
                            </svg>
                        </label>
                    @endforeach
                </fieldset>

                <p class="mt-4 text-xs text-ink-500">
                    <span x-text="`${(data.resources || []).length} recurso${(data.resources || []).length === 1 ? '' : 's'} selecionado${(data.resources || []).length === 1 ? '' : 's'}.`"></span>
                </p>
                @error('resources')<p class="mt-2 text-xs text-emergency-600">{{ $message }}</p>@enderror
            </x-card>

            {{-- =========================== Passo 7 · Objetivos =========================== --}}
            <x-card padding="lg" x-show="step === 7" x-cloak accent="clinical">
                <div class="mb-6">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-clinical-700">Passo 7 de 8</p>
                    <h2 class="mt-1 font-display text-xl font-semibold text-navy-900">Objetivos pedagógicos</h2>
                    <p class="mt-1 text-sm text-ink-500">O gerador cria três objetivos padrão baseados em MARCH. Aqui você pode acrescentar objetivos institucionais.</p>
                </div>

                <x-textarea
                    label="Objetivos adicionais (opcional)"
                    name="learning_extra"
                    rows="4"
                    placeholder="Ex.: aplicar protocolo interno de comunicação com regulação médica."
                    x-model="data.learning_extra"
                />

                <div class="mt-5 rounded-md bg-clinical-50 p-4 ring-1 ring-inset ring-clinical-100">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-clinical-700">Objetivos gerados automaticamente</p>
                    <x-checklist :items="[
                        'Reconhecer riscos e estabelecer prioridades de atendimento.',
                        'Aplicar avaliação MARCH de forma sequencial e documentada.',
                        'Comunicar achados, intervenções e necessidade de evacuação.',
                    ]" variant="clinical" class="mt-3" />
                </div>
            </x-card>

            {{-- =========================== Passo 8 · Revisão =========================== --}}
            <x-card padding="lg" x-show="step === 8" x-cloak accent="navy">
                <div class="mb-6">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-navy-700">Passo 8 de 8</p>
                    <h2 class="mt-1 font-display text-xl font-semibold text-navy-900">Revisão final</h2>
                    <p class="mt-1 text-sm text-ink-500">Confira os dados antes de gerar a ficha. Você poderá editar novos cenários — este ficará como registro.</p>
                </div>

                <dl class="divide-y divide-stone-100 rounded-md ring-1 ring-stone-200">
                    <div class="grid grid-cols-1 gap-1 px-4 py-3 sm:grid-cols-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Contexto</dt>
                        <dd class="col-span-2 text-sm text-ink-900" x-text="data.context_label || '— sem rótulo'"></dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 px-4 py-3 sm:grid-cols-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Público</dt>
                        <dd class="col-span-2 text-sm text-ink-900" x-text="data.audience || 'aph'"></dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 px-4 py-3 sm:grid-cols-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Ambiente</dt>
                        <dd class="col-span-2 text-sm text-ink-900" x-text="data.environment || '—'"></dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 px-4 py-3 sm:grid-cols-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Ameaça</dt>
                        <dd class="col-span-2 text-sm text-ink-900 capitalize" x-text="data.threat_level || 'controlada'"></dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 px-4 py-3 sm:grid-cols-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Vítimas</dt>
                        <dd class="col-span-2 text-sm text-ink-900 tabular-nums" x-text="`${data.casualties || 1}`"></dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 px-4 py-3 sm:grid-cols-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Trauma</dt>
                        <dd class="col-span-2 text-sm text-ink-900" x-text="data.mechanism || '—'"></dd>
                    </div>
                    <div class="grid grid-cols-1 gap-1 px-4 py-3 sm:grid-cols-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Recursos</dt>
                        <dd class="col-span-2 text-sm text-ink-900" x-text="(data.resources && data.resources.length) ? data.resources.join(', ') : 'Nenhum'"></dd>
                    </div>
                </dl>

                <div class="mt-6">
                    <x-alert variant="info" title="Antes de gerar">
                        A geração é determinística — mesmos parâmetros produzem o mesmo cenário. Use isso a favor da padronização entre turmas.
                    </x-alert>
                </div>
            </x-card>

            {{-- Navegação --}}
            <div class="sticky bottom-0 z-10 -mx-6 border-t border-stone-200 bg-white/95 px-6 py-4 backdrop-blur-md">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs text-ink-500">
                        Passo <span class="font-semibold text-navy-900" x-text="step"></span>
                        de {{ count($stepLabels) }} · rascunho salvo automaticamente
                    </p>
                    <div class="flex items-center gap-2">
                        <x-button variant="ghost" size="md" x-show="!isFirst()" x-on:click="prev()" type="button" x-cloak>Anterior</x-button>
                        <x-button size="md" x-show="!isLast()" x-on:click="next()" type="button" x-cloak>
                            Próximo
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-3.5 w-3.5"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                        </x-button>
                        <x-button size="md" x-show="isLast()" type="submit" variant="success" x-cloak>
                            Gerar cenário
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-3.5 w-3.5"><path d="M5 13l4 4L19 7"/></svg>
                        </x-button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-layouts.app>
