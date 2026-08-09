<x-layouts.app :current="'knowledge'" title="Base de Conhecimento · Tactical Scenario Lab">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => 'Painel', 'href' => route('dashboard')],
            ['label' => 'Base de Conhecimento'],
        ]" />
    </x-slot:breadcrumbs>

    <x-slot:header>
        <div class="max-w-4xl">
            <div class="flex flex-wrap items-center gap-2">
                <x-badge variant="navy" size="sm" dot>Conhecimento do produto</x-badge>
                <x-badge variant="clinical" size="sm">Versionado no Git</x-badge>
            </div>
            <h1 class="mt-3 font-display text-3xl font-semibold tracking-tight text-navy-950">Base de Conhecimento</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-600">
                Encontre orientação sobre o uso do Tactical Scenario Lab, seus fluxos e invariantes institucionais sem sair do contexto autenticado.
            </p>
        </div>
    </x-slot:header>

    <section aria-labelledby="knowledge-discovery-heading">
        <x-card title="Encontrar um guia" subtitle="Pesquise por assunto ou filtre pela área do produto" accent="navy">
            <h2 id="knowledge-discovery-heading" class="sr-only">Busca e filtros da Base de Conhecimento</h2>

            <form method="GET" action="{{ route('knowledge.index') }}" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(14rem,18rem)_auto] lg:items-end">
                <div>
                    <label for="knowledge-q" class="mb-1.5 block text-sm font-medium text-ink-800">Pesquisar</label>
                    <input
                        id="knowledge-q"
                        name="q"
                        type="search"
                        value="{{ $query }}"
                        placeholder="Ex.: avaliação, cockpit, histórico"
                        class="min-h-11 w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-ink-900 placeholder:text-ink-300 focus:border-navy-500"
                    >
                </div>

                <div>
                    <label for="knowledge-category" class="mb-1.5 block text-sm font-medium text-ink-800">Categoria</label>
                    <select
                        id="knowledge-category"
                        name="category"
                        class="min-h-11 w-full rounded-md border border-stone-300 bg-white px-3 py-2 text-sm text-ink-900 focus:border-navy-500"
                    >
                        <option value="">Todas as categorias</option>
                        @foreach ($categories as $categoryId => $categoryLabel)
                            <option value="{{ $categoryId }}" @selected($selectedCategory === $categoryId)>{{ $categoryLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <x-button type="submit">Buscar guias</x-button>
            </form>
        </x-card>
    </section>

    @php
        $count = $articles->count();
        $audienceLabels = [
            'instructor' => 'Instrutor',
            'evaluator' => 'Avaliador',
            'manager' => 'Gestor',
            'administrator' => 'Administrador',
        ];
    @endphp

    <section aria-labelledby="knowledge-results-heading" class="mt-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 id="knowledge-results-heading" class="font-display text-xl font-semibold text-navy-950">Guias disponíveis</h2>
                <p class="mt-1 text-sm text-ink-500" aria-live="polite">
                    {{ $count === 1 ? '1 guia encontrado' : $count.' guias encontrados' }}
                </p>
            </div>

            @if ($query !== '' || $selectedCategory !== '')
                <a href="{{ route('knowledge.index') }}" class="text-sm font-semibold text-navy-700 underline-offset-4 hover:underline">Limpar filtros</a>
            @endif
        </div>

        @if ($articles->isEmpty())
            <x-card accent="navy">
                <x-empty-state
                    icon="search"
                    title="Nenhum guia encontrado"
                    description="Tente outro termo de pesquisa ou remova o filtro de categoria."
                    class="py-10"
                />
            </x-card>
        @else
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach ($articles as $article)
                    <article class="flex h-full flex-col rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-badge variant="navy" size="sm">
                                {{ $categories[$article->category] ?? $article->category }}
                            </x-badge>
                            @foreach ($article->audience as $audience)
                                <x-badge variant="neutral" size="sm">{{ $audienceLabels[$audience] ?? $audience }}</x-badge>
                            @endforeach
                        </div>

                        <h3 class="mt-4 text-lg font-semibold text-navy-950">
                            <a href="{{ url('/knowledge/'.$article->slug) }}" class="rounded-sm underline-offset-4 hover:text-navy-700 hover:underline">
                                {{ $article->title }}
                            </a>
                        </h3>
                        <p class="mt-2 flex-1 text-sm leading-6 text-ink-600">{{ $article->summary }}</p>
                        <p class="mt-4 border-t border-stone-100 pt-3 text-xs text-ink-500">
                            Revisado em {{ \Illuminate\Support\Carbon::parse($article->reviewedOn)->format('d/m/Y') }}
                        </p>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.app>
