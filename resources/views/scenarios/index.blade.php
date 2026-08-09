@php
$collection = $scenarios->getCollection();
$publishedDefinitions = $collection->filter(fn ($scenario) => $scenario->latestVersion?->publication_status === 'published')->count();
$draftDefinitions = $collection->filter(fn ($scenario) => $scenario->latestVersion?->publication_status === 'draft')->count();
$executionCount = $collection->sum(fn ($scenario) => (int) ($scenario->latestVersion?->executions_count ?? 0));
@endphp

<x-layouts.app :current="'scenarios'" :title="'Cenários · Tactical Scenario Lab'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => 'Painel', 'href' => route('dashboard')],
            ['label' => 'Cenários'],
        ]" />
    </x-slot:breadcrumbs>

    <x-slot:header>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <x-badge variant="navy" size="sm" dot>Workspace institucional</x-badge>
                    <x-badge variant="clinical" size="sm">Definições versionadas</x-badge>
                </div>
                <h1 class="mt-3 font-display text-3xl font-semibold tracking-tight text-navy-950">Cenários</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-500">Crie, publique e reutilize definições versionadas antes de iniciar execuções. Avaliações e histórico permanecem vinculados às execuções, não ao registro legado do cenário.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-button href="{{ route('scenario-templates.index') }}" variant="secondary">Templates</x-button>
                @if ($canManage)
                    <x-button href="{{ route('scenarios.create') }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-3.5 w-3.5" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                        Novo cenário
                    </x-button>
                @endif
            </div>
        </div>
    </x-slot:header>

    <section aria-labelledby="scenario-lifecycle-heading" class="rounded-xl border border-stone-200 bg-white p-5 shadow-xs">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-ink-500">Fluxo institucional</p>
                <h2 id="scenario-lifecycle-heading" class="mt-1 font-display text-xl font-semibold text-navy-950">Ciclo do cenário</h2>
                <p class="mt-1 max-w-2xl text-sm leading-6 text-ink-500">A publicação congela a definição daquela versão. Novas mudanças exigem revisão em um novo rascunho.</p>
            </div>
            <a href="{{ route('scenario-templates.index') }}" class="text-sm font-semibold text-navy-700 hover:text-navy-950">Abrir biblioteca de templates</a>
        </div>

        <ol class="mt-5 grid gap-2 sm:grid-cols-3 xl:grid-cols-6" aria-label="Etapas do ciclo do cenário">
            @foreach ([
                ['Rascunho', 'Definir e revisar'],
                ['Publicado', 'Congelar versão'],
                ['Preparar', 'Equipe e recursos'],
                ['Executar', 'Registrar operação'],
                ['Avaliar', 'Evidências e debrief'],
                ['Histórico', 'Preservar resultado'],
            ] as $index => [$label, $description])
                <li class="relative rounded-lg border border-stone-200 bg-stone-50 p-3">
                    <span class="font-mono text-[10px] font-semibold text-ink-500">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <p class="mt-1 text-sm font-semibold text-navy-950">{{ $label }}</p>
                    <p class="mt-1 text-xs leading-5 text-ink-500">{{ $description }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    <section aria-label="Resumo da página de cenários" class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stats-card label="Cenários" :value="$scenarios->total()" hint="na organização ativa" icon="M4 6h16M4 12h16M4 18h10" accent="navy" />
        <x-stats-card label="Versões publicadas" :value="$publishedDefinitions" hint="nesta página" icon="M9 12l2 2 4-4" accent="clinical" />
        <x-stats-card label="Versões em rascunho" :value="$draftDefinitions" hint="nesta página" icon="M12 20h9M16 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z" accent="alert" />
        <x-stats-card label="Execuções" :value="$executionCount" hint="da versão vigente nesta página" icon="M5 3v18l14-9L5 3z" accent="navy" />
    </section>

    <section aria-labelledby="scenario-list-heading" class="mt-8">
        <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 id="scenario-list-heading" class="font-display text-lg font-semibold text-navy-950">Definições recentes</h2>
                <p class="mt-1 text-xs text-ink-500">A situação abaixo vem da versão mais recente de cada cenário.</p>
            </div>
            <span class="text-xs text-ink-500">Página {{ $scenarios->currentPage() }} de {{ $scenarios->lastPage() ?: 1 }}</span>
        </div>

        <x-table
            label="Cenários da organização"
            :empty="$scenarios->isEmpty()"
            empty-title="Nenhum cenário criado ainda"
            empty-description="Crie um cenário para iniciar o ciclo de definição, publicação, execução e avaliação."
        >
            <thead class="bg-stone-50 text-xs font-semibold uppercase tracking-[0.08em] text-ink-500">
                <tr>
                    <th scope="col" class="px-5 py-3">Cenário</th>
                    <th scope="col" class="px-5 py-3">Definição vigente</th>
                    <th scope="col" class="px-5 py-3">Escala</th>
                    <th scope="col" class="px-5 py-3">Execuções</th>
                    <th scope="col" class="px-5 py-3"><span class="sr-only">Ação</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100 bg-white">
                @foreach ($scenarios as $scenario)
                    @php
                        $version = $scenario->latestVersion;
                        $published = $version?->publication_status === 'published';
                        $threatLevel = $version?->threat_level ?? $scenario->threat_level;
                        $casualties = (int) ($version?->estimated_casualty_count ?? $scenario->estimated_casualty_count ?? $scenario->casualties);
                    @endphp
                    <tr class="transition-colors hover:bg-stone-50/70">
                        <td class="px-5 py-4 align-top">
                            <a href="{{ route('scenarios.show', $scenario) }}" class="font-semibold text-navy-950 hover:text-navy-700">{{ $scenario->title }}</a>
                            <p class="mt-1 max-w-md text-xs leading-5 text-ink-500">{{ $version?->mechanism ?? $scenario->mechanism }}</p>
                        </td>
                        <td class="px-5 py-4 align-top">
                            <div class="flex flex-wrap gap-2">
                                <x-badge :variant="$published ? 'clinical' : 'alert'" size="sm" dot>{{ $published ? 'Publicado' : 'Rascunho' }}</x-badge>
                                @if ($version)<x-badge variant="neutral" size="sm">Versão {{ $version->version_number }}</x-badge>@endif
                            </div>
                            <p class="mt-2 text-xs text-ink-500">Ameaça {{ $threatLevel }}</p>
                        </td>
                        <td class="px-5 py-4 align-top">
                            <p class="font-mono text-sm font-semibold tabular-nums text-navy-950">{{ number_format($casualties, 0, ',', '.') }}</p>
                            <p class="mt-1 text-xs text-ink-500">vítima(s) estimada(s)</p>
                        </td>
                        <td class="px-5 py-4 align-top">
                            <p class="font-mono text-sm font-semibold tabular-nums text-navy-950">{{ (int) ($version?->executions_count ?? 0) }}</p>
                            <p class="mt-1 text-xs text-ink-500">na versão vigente</p>
                        </td>
                        <td class="px-5 py-4 text-right align-top">
                            <x-button href="{{ route('scenarios.show', $scenario) }}" variant="ghost" size="sm">Abrir</x-button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>

        @if ($scenarios->hasPages())
            <div class="mt-6">{{ $scenarios->links() }}</div>
        @endif
    </section>
</x-layouts.app>
