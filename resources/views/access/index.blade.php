<x-layouts.app :current="'access'" :title="'Acessos institucionais · Tactical Medicine Academy'">
    <x-slot:header>
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emergency-700">Governança de acesso</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">Acessos institucionais</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-500">
                Contas com concessão ativa em <strong class="font-semibold text-navy-950">{{ $organization->name }}</strong>. Esta visão é sempre limitada ao contexto institucional ativo.
            </p>
        </div>
    </x-slot:header>

    <section class="overflow-hidden rounded-2xl border border-ink-200 bg-white shadow-sm">
        @forelse ($accesses as $access)
            <article class="border-b border-ink-100 px-5 py-5 last:border-b-0">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="truncate text-base font-semibold text-navy-950">{{ $access->user->name }}</h2>
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $access->user->status === 'active' ? 'bg-clinical-50 text-clinical-800' : 'bg-ink-100 text-ink-600' }}">
                                {{ $access->user->status === 'active' ? 'Conta ativa' : 'Conta inativa' }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-ink-500">{{ $access->user->email }}</p>
                        <p class="mt-2 text-sm text-ink-600">Papel de acesso: <strong class="font-semibold text-navy-950">{{ $access->role }}</strong></p>
                    </div>

                    <div class="max-w-2xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-ink-500">Habilidades efetivas</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @forelse ($access->abilities ?? [] as $ability)
                                <span class="rounded-full bg-navy-50 px-2.5 py-1 text-xs font-medium text-navy-800">
                                    {{ $abilityLabels[$ability] ?? $ability }}
                                </span>
                            @empty
                                <span class="text-sm text-ink-500">Nenhuma habilidade atribuída.</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </article>
        @empty
            <div class="px-6 py-16 text-center">
                <h2 class="text-lg font-semibold text-navy-950">Nenhum acesso ativo</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-ink-500">Não há concessões ativas para a organização atual.</p>
            </div>
        @endforelse
    </section>

    <div class="mt-6">{{ $accesses->links() }}</div>
</x-layouts.app>
