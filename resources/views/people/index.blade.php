<x-layouts.app :current="'people'" :title="'Pessoas · Tactical Medicine Academy'">
    <x-slot:header>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emergency-700">Identidade operacional</p>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">Pessoas</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-500">Cadastros globais com papéis e vínculos contextuais. CPF, RG, telefone e e-mail permanecem opcionais.</p>
            </div>
            <x-button href="{{ route('people.create') }}">Cadastro rápido</x-button>
        </div>
    </x-slot:header>

    <form method="GET" action="{{ route('people.index') }}" class="mb-6 rounded-2xl border border-ink-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 sm:grid-cols-[1fr_220px_180px_auto]">
            <label>
                <span class="sr-only">Buscar pessoa</span>
                <input name="q" value="{{ $search }}" class="w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100" placeholder="Nome, CPF, RG, matrícula, telefone ou e-mail" autocomplete="off">
            </label>
            <label>
                <span class="sr-only">Organização</span>
                <select name="organization_id" class="w-full rounded-xl border border-ink-300 bg-white px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                    <option value="">Todas as organizações</option>
                    @foreach ($organizations as $organization)
                        <option value="{{ $organization->id }}" @selected((string) $organizationId === (string) $organization->id)>{{ $organization->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="sr-only">Status</span>
                <select name="status" class="w-full rounded-xl border border-ink-300 bg-white px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                    <option value="">Todos os status</option>
                    <option value="active" @selected($status === 'active')>Ativo</option>
                    <option value="incomplete" @selected($status === 'incomplete')>Incompleto válido</option>
                    <option value="inactive" @selected($status === 'inactive')>Inativo</option>
                </select>
            </label>
            <x-button type="submit" variant="secondary">Buscar</x-button>
        </div>
        <div class="mt-3 flex flex-col gap-2 border-t border-ink-100 pt-3 text-xs text-ink-500 sm:flex-row sm:items-center sm:justify-between">
            <p>Nomes aceitam busca parcial. Documentos e contatos exigem valor completo e são comparados por fingerprint protegido.</p>
            @if ($search !== '' || $status || $organizationId)
                <a href="{{ route('people.index') }}" class="font-semibold text-navy-800 hover:text-emergency-700">Limpar filtros</a>
            @endif
        </div>
    </form>

    @if ($search !== '')
        <div class="mb-4 flex items-center justify-between gap-4 rounded-xl border border-navy-100 bg-navy-50 px-4 py-3 text-sm text-navy-800">
            <span>Resultados protegidos para <strong>{{ $search }}</strong></span>
            <span>{{ $people->total() }} encontrado(s)</span>
        </div>
    @endif

    <section class="overflow-hidden rounded-2xl border border-ink-200 bg-white shadow-sm">
        @forelse ($people as $person)
            <a href="{{ route('people.show', $person) }}" class="group block border-b border-ink-100 px-5 py-5 transition last:border-b-0 hover:bg-navy-50/50">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-center gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-navy-950 text-sm font-bold text-white">
                            {{ Str::upper(Str::substr($person->preferredName(), 0, 2)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="truncate font-semibold text-navy-950 group-hover:text-emergency-700">{{ $person->preferredName() }}</h2>
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $person->status === 'active' ? 'bg-clinical-50 text-clinical-800' : 'bg-alert-50 text-alert-800' }}">
                                    {{ $person->status === 'active' ? 'Ativo' : 'Incompleto válido' }}
                                </span>
                            </div>
                            @if ($person->social_name && $person->social_name !== $person->display_name)
                                <p class="mt-1 text-sm text-ink-500">Cadastro: {{ $person->display_name }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 sm:justify-end">
                        @foreach ($person->memberships->take(2) as $membership)
                            <span class="rounded-full bg-ink-50 px-3 py-1.5 text-xs font-medium text-ink-700">{{ $membership->organization->name }}</span>
                        @endforeach
                    </div>
                </div>
            </a>
        @empty
            <div class="px-6 py-16 text-center">
                <h2 class="text-lg font-semibold text-navy-950">Nenhuma pessoa encontrada</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-ink-500">Confirme o valor completo de documentos e contatos ou ajuste os filtros.</p>
                <div class="mt-6"><x-button href="{{ route('people.create') }}">Cadastrar pessoa</x-button></div>
            </div>
        @endforelse
    </section>

    <div class="mt-6">{{ $people->links() }}</div>
</x-layouts.app>
