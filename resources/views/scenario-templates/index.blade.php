<x-layouts.app :current="'scenarios'" title="Templates de cenário · Tactical Scenario Lab">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => 'Painel', 'href' => route('dashboard')],
            ['label' => 'Cenários', 'href' => route('scenarios.index')],
            ['label' => 'Templates'],
        ]" />
    </x-slot:breadcrumbs>

    <x-slot:header>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <x-badge variant="navy" size="sm" dot>Institutional Library</x-badge>
                    <x-badge variant="clinical" size="sm">Tenant-safe</x-badge>
                </div>
                <h1 class="mt-3 font-display text-3xl font-semibold tracking-tight text-navy-950">Templates de cenário</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-500">
                    Reutilize definições publicadas sem copiar execuções, avaliações, evidências ou histórico de debriefing.
                </p>
            </div>

            <x-button href="{{ route('scenarios.index') }}" variant="secondary">Voltar aos cenários</x-button>
        </div>
    </x-slot:header>

    @if (session('success'))
        <div class="mb-6"><x-alert variant="success" title="Operação concluída">{{ session('success') }}</x-alert></div>
    @endif
    @if ($errors->any())
        <div class="mb-6"><x-alert variant="danger" title="Revise os dados">{{ $errors->first() }}</x-alert></div>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        <x-card title="Fonte congelada" accent="navy">
            <p class="text-sm leading-6 text-ink-600">Somente versões publicadas podem virar template. A definição de origem permanece imutável.</p>
        </x-card>
        <x-card title="Clone limpo" accent="clinical">
            <p class="text-sm leading-6 text-ink-600">O uso do template cria Scenario + versão 1 em rascunho, com novos UUIDs e sem histórico operacional.</p>
        </x-card>
        <x-card title="Arquivamento seguro" accent="alert">
            <p class="text-sm leading-6 text-ink-600">Templates arquivados ficam preservados para referência e não podem originar novos cenários.</p>
        </x-card>
    </div>

    <div class="mt-6">
        <x-card title="Biblioteca institucional" subtitle="{{ $templates->total() }} template(s) no contexto ativo" accent="navy">
            @if ($templates->isEmpty())
                <div class="rounded-lg border border-dashed border-stone-300 bg-stone-50 px-6 py-10 text-center">
                    <p class="text-sm font-semibold text-navy-950">Nenhum template cadastrado.</p>
                    <p class="mt-2 text-sm text-ink-500">Abra uma versão publicada de cenário e salve-a como template institucional.</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($templates as $template)
                        @php
                            $sourceScenario = $template->sourceVersion?->scenario;
                            $active = $template->status === 'active';
                        @endphp
                        <article class="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-badge :variant="$active ? 'clinical' : 'neutral'" size="sm" dot>
                                            {{ $active ? 'Ativo' : 'Arquivado' }}
                                        </x-badge>
                                        @if ($template->sourceVersion)
                                            <x-badge variant="navy" size="sm">Fonte v{{ $template->sourceVersion->version_number }}</x-badge>
                                        @endif
                                    </div>
                                    <h2 class="mt-3 text-lg font-semibold text-navy-950">{{ $template->name }}</h2>
                                    @if ($template->description)
                                        <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-600">{{ $template->description }}</p>
                                    @endif
                                    <div class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-xs text-ink-500">
                                        <span>Origem: {{ $sourceScenario?->title ?? 'Fonte indisponível' }}</span>
                                        <span>Criado em {{ $template->created_at->format('d/m/Y H:i') }}</span>
                                    </div>
                                </div>

                                @if ($canManage)
                                    <div class="flex shrink-0 flex-wrap gap-2">
                                        @if ($active)
                                            <form method="POST" action="{{ route('scenario-templates.use', $template) }}">
                                                @csrf
                                                <x-button type="submit">Usar template</x-button>
                                            </form>
                                            <form method="POST" action="{{ route('scenario-templates.archive', $template) }}">
                                                @csrf
                                                @method('PATCH')
                                                <x-button type="submit" variant="secondary">Arquivar</x-button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                @if ($templates->hasPages())
                    <div class="mt-6">{{ $templates->links() }}</div>
                @endif
            @endif
        </x-card>
    </div>
</x-layouts.app>
