<x-layouts.app :current="'knowledge'" title="{{ $article->title }} · Base de Conhecimento">
    @php
        $audienceLabels = [
            'instructor' => 'Instrutor',
            'evaluator' => 'Avaliador',
            'manager' => 'Gestor',
            'administrator' => 'Administrador',
        ];
        $categoryLabel = $categories[$article->category] ?? $article->category;
    @endphp

    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => 'Painel', 'href' => route('dashboard')],
            ['label' => 'Base de Conhecimento', 'href' => route('knowledge.index')],
            ['label' => $article->title],
        ]" />
    </x-slot:breadcrumbs>

    <x-slot:header>
        <div class="max-w-4xl">
            <div class="flex flex-wrap items-center gap-2">
                <x-badge variant="navy" size="sm" dot>{{ $categoryLabel }}</x-badge>
                @foreach ($article->audience as $audience)
                    <x-badge variant="neutral" size="sm">{{ $audienceLabels[$audience] ?? $audience }}</x-badge>
                @endforeach
            </div>

            <h1 class="mt-3 font-display text-3xl font-semibold tracking-tight text-navy-950 sm:text-4xl">{{ $article->title }}</h1>
            <p class="mt-3 max-w-3xl text-base leading-7 text-ink-600">{{ $article->summary }}</p>
            <p class="mt-4 text-xs font-medium text-ink-500">
                Revisado em {{ \Illuminate\Support\Carbon::parse($article->reviewedOn)->format('d/m/Y') }}
            </p>
        </div>
    </x-slot:header>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_18rem] xl:items-start">
        <article class="min-w-0 rounded-xl border border-stone-200 bg-white p-5 shadow-sm sm:p-8 print:border-0 print:p-0 print:shadow-none" aria-label="Conteúdo do guia">
            <div class="knowledge-article text-sm leading-7 text-ink-700
                [&_h2]:mb-3 [&_h2]:mt-9 [&_h2]:scroll-mt-24 [&_h2]:font-display [&_h2]:text-2xl [&_h2]:font-semibold [&_h2]:tracking-tight [&_h2]:text-navy-950
                [&_h3]:mb-2 [&_h3]:mt-7 [&_h3]:scroll-mt-24 [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:text-navy-900
                [&_p]:my-4 [&_ul]:my-4 [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-6
                [&_ol]:my-4 [&_ol]:list-decimal [&_ol]:space-y-1 [&_ol]:pl-6
                [&_strong]:font-semibold [&_strong]:text-ink-900
                [&_a]:font-semibold [&_a]:text-navy-700 [&_a]:underline [&_a]:decoration-navy-200 [&_a]:underline-offset-4 hover:[&_a]:decoration-navy-700
                [&_code]:rounded [&_code]:bg-stone-100 [&_code]:px-1.5 [&_code]:py-0.5 [&_code]:font-mono [&_code]:text-[0.9em] [&_code]:text-ink-900
                [&_blockquote]:my-5 [&_blockquote]:border-l-4 [&_blockquote]:border-navy-200 [&_blockquote]:bg-stone-50 [&_blockquote]:px-4 [&_blockquote]:py-2 [&_blockquote]:text-ink-600">
                {!! $article->html !!}
            </div>
        </article>

        <aside class="space-y-4 print:hidden" aria-label="Navegação e contexto do guia">
            @if ($article->toc !== [])
                <x-card title="Neste guia" accent="navy">
                    <nav aria-label="Índice deste guia">
                        <ol class="space-y-2 text-sm">
                            @foreach ($article->toc as $heading)
                                <li class="{{ $heading['level'] === 3 ? 'pl-4' : '' }}">
                                    <a href="#{{ $heading['id'] }}" class="block rounded-sm text-ink-700 underline-offset-4 hover:text-navy-700 hover:underline">
                                        {{ $heading['label'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                </x-card>
            @endif

            <x-card title="Sobre este conteúdo" accent="clinical">
                <p class="text-sm leading-6 text-ink-600">
                    Este guia explica o uso do Tactical Scenario Lab e seus invariantes de produto. Ele não substitui protocolos institucionais nem decisão clínica ou tática em campo.
                </p>
            </x-card>
        </aside>
    </div>

    @if ($relatedArticles->isNotEmpty())
        <section aria-labelledby="related-guides-heading" class="mt-8 print:hidden">
            <div class="mb-4">
                <h2 id="related-guides-heading" class="font-display text-xl font-semibold text-navy-950">Guias relacionados</h2>
                <p class="mt-1 text-sm text-ink-500">Continue pelo contexto que complementa este fluxo.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($relatedArticles as $related)
                    <a href="{{ route('knowledge.show', $related->slug) }}" class="group rounded-xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-navy-200 hover:shadow-md">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-500">{{ $categories[$related->category] ?? $related->category }}</p>
                        <h3 class="mt-2 text-base font-semibold text-navy-950 group-hover:text-navy-700">{{ $related->title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-ink-600">{{ $related->summary }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.app>
