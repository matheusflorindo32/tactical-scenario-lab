<x-layouts.app :current="'people'" :title="'Pessoas · Tactical Scenario Lab'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => 'Painel', 'href' => route('dashboard')],
            ['label' => 'Pessoas'],
        ]" />
    </x-slot:breadcrumbs>

    <x-slot:header>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <x-badge variant="navy" size="sm" dot>Identidade institucional</x-badge>
                <h1 class="mt-3 font-display text-3xl font-semibold tracking-tight text-navy-950">Pessoas</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-500">Cadastros globais com papéis e vínculos contextuais. Documentos e contatos permanecem opcionais e protegidos.</p>
            </div>
            <x-button href="{{ route('people.create') }}">Cadastrar pessoa</x-button>
        </div>
    </x-slot:header>

    <form method="GET" action="{{ route('people.index') }}" class="mb-6 rounded-xl border border-stone-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 sm:grid-cols-[1fr_220px_180px_auto]">
            <label>
                <span class="sr-only">Buscar pessoa</span>
                <input name="q" value="{{ $search }}" class="w-full rounded-md border-stone-300 px-4 py-3 text-sm focus:border-navy-500 focus:ring-navy-500" placeholder="Nome, CPF, RG, matrícula, telefone ou e-mail" autocomplete="off">
            </label>
            <label>
                <span class="sr-only">Organização</span>
                <select name="organization_id" class="w-full rounded-md border-stone-300 bg-white px-4 py-3 text-sm focus:border-navy-500 focus:ring-navy-500">
                    <option value="">Todas as organizações</option>
                    @foreach ($organizations as $organization)
                        <option value="{{ $organization->id }}" @selected((string) $organizationId === (string) $organization->id)>{{ $organization->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="sr-only">Status</span>
                <select name="status" class="w-full rounded-md border-stone-300 bg-white px-4 py-3 text-sm focus:border-navy-500 focus:ring-navy-500">
                    <option value="">Todos os status</option>
                    <option value="active" @selected($status === 'active')>Ativo</option>
                    <option value="incomplete" @selected($status === 'incomplete')>Incompleto válido</option>
                    <option value="inactive" @selected($status === 'inactive')>Inativo</option>
                </select>
            </label>
            <x-button type="submit" variant="secondary">Buscar</x-button>
        </div>
        <div class="mt-3 flex flex-col gap-2 border-t border-stone-100 pt-3 text-xs text-ink-500 sm:flex-row sm:items-center sm:justify-between">
            <p>Nomes aceitam busca parcial. Documentos e contatos exigem valor completo e são comparados por fingerprint protegido.</p>
            @if ($search !== '' || $status || $organizationId)
                <a href="{{ route('people.index') }}" class="font-semibold text-navy-700 hover:text-navy-950">Limpar filtros</a>
            @endif
        </div>
    </form>

    @if ($search !== '')
        <div class="mb-4 flex items-center justify-between gap-4 rounded-lg border border-navy-100 bg-navy-50 px-4 py-3 text-sm text-navy-700">
            <span>Resultados protegidos para <strong>{{ $search }}</strong></span>
            <span>{{ $people->total() }} encontrado(s)</span>
        </div>
    @endif

    <x-table
        label="Pessoas da organização"
        :empty="$people->isEmpty()"
        empty-title="Nenhuma pessoa encontrada"
        empty-description="Confirme o valor completo de documentos e contatos ou ajuste os filtros."
    >
        <thead class="bg-stone-50 text-xs font-semibold uppercase tracking-[0.08em] text-ink-500">
            <tr>
                <th scope="col" class="px-5 py-3">Pessoa</th>
                <th scope="col" class="px-5 py-3">Status</th>
                <th scope="col" class="px-5 py-3">Vínculos recentes</th>
                <th scope="col" class="px-5 py-3"><span class="sr-only">Ação</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-stone-100 bg-white">
            @foreach ($people as $person)
                <tr class="transition-colors hover:bg-stone-50/70">
                    <td class="px-5 py-4 align-top">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-navy-950 text-sm font-bold text-white" aria-hidden="true">
                                {{ Str::upper(Str::substr($person->preferredName(), 0, 2)) }}
                            </div>
                            <div class="min-w-0">
                                <a href="{{ route('people.show', $person) }}" class="font-semibold text-navy-950 hover:text-navy-700">{{ $person->preferredName() }}</a>
                                @if ($person->social_name && $person->social_name !== $person->display_name)
                                    <p class="mt-1 text-xs text-ink-500">Cadastro: {{ $person->display_name }}</p>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4 align-top">
                        <x-badge :variant="$person->status === 'active' ? 'clinical' : 'alert'" size="sm" dot>
                            {{ $person->status === 'active' ? 'Ativo' : ($person->status === 'inactive' ? 'Inativo' : 'Incompleto válido') }}
                        </x-badge>
                    </td>
                    <td class="px-5 py-4 align-top">
                        <div class="flex flex-wrap gap-2">
                            @forelse ($person->memberships->take(2) as $membership)
                                <x-badge variant="neutral" size="sm">{{ $membership->organization->name }}</x-badge>
                            @empty
                                <span class="text-sm text-ink-500">Sem vínculo exibido</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="px-5 py-4 text-right align-top">
                        <x-button href="{{ route('people.show', $person) }}" variant="ghost" size="sm">Abrir</x-button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table>

    @if ($people->hasPages())
        <div class="mt-6">{{ $people->links() }}</div>
    @endif
</x-layouts.app>
