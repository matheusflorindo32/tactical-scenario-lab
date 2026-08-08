@php
$threatOptions = [
    'controlada' => 'Controlada — cena estabilizada, sem risco iminente.',
    'potencial' => 'Potencial — risco identificado, sem confronto ativo.',
    'ativa' => 'Ativa — hostilidade em curso ou perigo imediato.',
];

$resourceOptions = [
    'Kit IFAK',
    'Maca',
    'DEA',
    'Oxigênio',
    'Rádio',
    'Viatura',
    'Torniquete',
    'Cobertor térmico',
];
@endphp

<x-layouts.app :current="'new'" :title="'Novo cenário · Tactical Scenario Lab'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => 'Painel', 'href' => route('dashboard')],
            ['label' => 'Cenários', 'href' => route('scenarios.index')],
            ['label' => 'Novo cenário'],
        ]" />
    </x-slot:breadcrumbs>

    <x-slot:header>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="max-w-3xl">
                <div class="flex flex-wrap items-center gap-2">
                    <x-badge variant="navy" size="sm" dot>Scenario Core · versão 1</x-badge>
                    <x-badge variant="neutral" size="sm">Escala sem limite artificial</x-badge>
                </div>
                <h1 class="mt-3 font-display text-3xl font-semibold tracking-tight text-navy-950">Configurar novo cenário</h1>
                <p class="mt-2 text-sm leading-6 text-ink-500">
                    Defina a escala operacional do incidente sem transformar cada vítima estimada em um registro individual. A versão inicial do cenário será criada de forma atômica e poderá receber vítimas detalhadas e cohorts posteriormente.
                </p>
            </div>
        </div>
    </x-slot:header>

    @if ($errors->any())
        <div class="mb-6">
            <x-alert variant="danger" title="Revise os dados do cenário">
                Há campos inválidos ou incompletos. Corrija os itens destacados antes de salvar.
            </x-alert>
        </div>
    @endif

    <form method="POST" action="{{ route('scenarios.store') }}" class="space-y-6">
        @csrf

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,.65fr)]">
            <div class="space-y-6">
                <x-card title="1. Contexto operacional" subtitle="Definição que compõe a versão inicial do cenário" accent="navy">
                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <x-input
                                label="Ambiente da cena"
                                name="environment"
                                :value="old('environment')"
                                :error="$errors->first('environment')"
                                required
                                maxlength="100"
                                placeholder="Ex.: terminal rodoviário, rodovia, edifício, área rural..."
                                hint="Descreva o local com precisão suficiente para orientar segurança, acesso e recursos."
                            />
                        </div>

                        <div>
                            <label for="threat_level" class="mb-1.5 block text-sm font-medium text-ink-900">Nível de ameaça</label>
                            <select
                                id="threat_level"
                                name="threat_level"
                                required
                                class="block w-full rounded-md border-0 bg-white px-3 py-2.5 text-sm text-ink-900 shadow-sm ring-1 ring-inset ring-stone-300 focus:ring-2 focus:ring-inset focus:ring-navy-600"
                            >
                                @foreach ($threatOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('threat_level', 'controlada') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('threat_level')
                                <p class="mt-1.5 text-xs text-emergency-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <x-input
                                label="Mecanismo do trauma"
                                name="mechanism"
                                :value="old('mechanism')"
                                :error="$errors->first('mechanism')"
                                required
                                maxlength="150"
                                placeholder="Ex.: explosão, colisão múltipla, ferimento penetrante..."
                                hint="A cinemática orienta ações esperadas e riscos críticos."
                            />
                        </div>
                    </div>
                </x-card>

                <x-card title="2. Escala de vítimas" subtitle="Separar escala estimada de representação detalhada" accent="clinical">
                    <div class="grid gap-5 lg:grid-cols-[minmax(0,280px)_1fr] lg:items-start">
                        <div>
                            <x-input
                                label="Estimativa total de vítimas"
                                name="estimated_casualty_count"
                                type="number"
                                min="1"
                                step="1"
                                :value="old('estimated_casualty_count', 1)"
                                :error="$errors->first('estimated_casualty_count')"
                                required
                                hint="Informe um inteiro positivo. Não há teto operacional artificial."
                            />
                        </div>

                        <div class="rounded-lg border border-clinical-200 bg-clinical-50/70 p-4">
                            <p class="text-sm font-semibold text-navy-950">Como a escala é modelada</p>
                            <p class="mt-2 text-sm leading-6 text-ink-700">
                                Este número representa o tamanho estimado do incidente. Ele <strong>não cria automaticamente uma linha por vítima</strong>. Casos relevantes poderão ser detalhados individualmente em <strong>ScenarioVictim</strong>; grupos semelhantes poderão ser representados por <strong>VictimCohort</strong>.
                            </p>
                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-md bg-white p-3 ring-1 ring-inset ring-clinical-100">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Exemplo</p>
                                    <p class="mt-1 font-display text-xl font-semibold text-navy-950">1.000</p>
                                    <p class="text-xs text-ink-500">vítimas estimadas</p>
                                </div>
                                <div class="rounded-md bg-white p-3 ring-1 ring-inset ring-clinical-100">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Detalhadas</p>
                                    <p class="mt-1 font-display text-xl font-semibold text-navy-950">2</p>
                                    <p class="text-xs text-ink-500">vítimas individuais</p>
                                </div>
                                <div class="rounded-md bg-white p-3 ring-1 ring-inset ring-clinical-100">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Agrupadas</p>
                                    <p class="mt-1 font-display text-xl font-semibold text-navy-950">998</p>
                                    <p class="text-xs text-ink-500">em um ou mais cohorts</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-card>

                <x-card title="3. Recursos disponíveis" subtitle="Selecione apenas o que estará disponível no início do exercício" accent="navy">
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($resourceOptions as $index => $resource)
                            @php
                                $id = 'resource-' . $index;
                                $checked = in_array($resource, old('resources', []), true);
                            @endphp
                            <label for="{{ $id }}" class="flex cursor-pointer items-center gap-3 rounded-md border border-stone-200 bg-white px-3 py-3 text-sm text-ink-900 transition-colors hover:border-navy-300 has-[:checked]:border-navy-500 has-[:checked]:bg-navy-50">
                                <input
                                    id="{{ $id }}"
                                    type="checkbox"
                                    name="resources[]"
                                    value="{{ $resource }}"
                                    @checked($checked)
                                    class="h-4 w-4 rounded border-stone-300 accent-navy-700"
                                >
                                <span>{{ $resource }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('resources.*')
                        <p class="mt-2 text-xs text-emergency-600">{{ $message }}</p>
                    @enderror
                </x-card>
            </div>

            <aside class="space-y-6">
                <x-card title="O que será criado" accent="clinical">
                    <ol class="space-y-4 text-sm">
                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-navy-900 text-xs font-semibold text-white">1</span>
                            <div>
                                <p class="font-semibold text-navy-950">Scenario</p>
                                <p class="mt-0.5 text-xs leading-5 text-ink-500">Identidade institucional do cenário.</p>
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-clinical-600 text-xs font-semibold text-white">2</span>
                            <div>
                                <p class="font-semibold text-navy-950">ScenarioVersion · v1</p>
                                <p class="mt-0.5 text-xs leading-5 text-ink-500">Definição histórica e versionável do treinamento.</p>
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-stone-200 text-xs font-semibold text-ink-700">3</span>
                            <div>
                                <p class="font-semibold text-navy-950">Representações opcionais</p>
                                <p class="mt-0.5 text-xs leading-5 text-ink-500">Vítimas individuais e cohorts entram apenas quando forem úteis ao exercício.</p>
                            </div>
                        </li>
                    </ol>
                </x-card>

                <x-card title="Princípio de escala" accent="navy">
                    <p class="text-sm leading-6 text-ink-700">
                        Escala operacional e nível de detalhe são dimensões diferentes. O sistema preserva essa separação para suportar tanto um atendimento individual quanto incidentes de massa.
                    </p>
                </x-card>
            </aside>
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-stone-200 pt-5 sm:flex-row sm:items-center sm:justify-end">
            <x-button href="{{ route('scenarios.index') }}" variant="secondary">Cancelar</x-button>
            <x-button type="submit" variant="primary">Criar cenário e versão 1</x-button>
        </div>
    </form>
</x-layouts.app>
