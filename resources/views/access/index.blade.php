<x-layouts.app :current="'access'" :title="'Acessos institucionais · Tactical Medicine Academy'">
    <x-slot:header>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emergency-700">Governança de acesso</p>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">Acessos institucionais</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-500">
                    Contas com concessão ativa em <strong class="font-semibold text-navy-950">{{ $organization->name }}</strong>. Acessos vencidos deixam automaticamente de autorizar e não aparecem nesta visão operacional.
                </p>
            </div>
            <a href="{{ route('access.create') }}" class="inline-flex items-center justify-center rounded-xl bg-navy-950 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-navy-800">
                Conceder acesso
            </a>
        </div>
    </x-slot:header>

    @error('access')
        <div class="mb-5 rounded-xl border border-emergency-200 bg-emergency-50 px-4 py-3 text-sm text-emergency-800">
            {{ $message }}
        </div>
    @enderror

    @error('account')
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            {{ $message }}
        </div>
    @enderror

    <div class="mb-5 rounded-2xl border border-ink-200 bg-white px-5 py-4 text-sm leading-6 text-ink-600 shadow-sm">
        <strong class="font-semibold text-navy-950">Conta global x acesso local:</strong>
        revogar remove apenas a concessão desta organização. Inativar a conta bloqueia o login global e, por segurança, só é permitido aqui quando a conta não possui outra concessão ativa em outra organização.
    </div>

    <section class="overflow-hidden rounded-2xl border border-ink-200 bg-white shadow-sm">
        @forelse ($accesses as $access)
            <article class="border-b border-ink-100 px-5 py-5 last:border-b-0">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="truncate text-base font-semibold text-navy-950">{{ $access->user->name }}</h2>
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $access->user->status === 'active' ? 'bg-clinical-50 text-clinical-800' : 'bg-ink-100 text-ink-600' }}">
                                {{ $access->user->status === 'active' ? 'Conta ativa' : 'Conta inativa' }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-ink-500">{{ $access->user->email }}</p>
                        <p class="mt-2 text-sm text-ink-600">Papel de acesso: <strong class="font-semibold text-navy-950">{{ $access->role }}</strong></p>
                        <p class="mt-1 text-xs text-ink-500">
                            Validade: <strong class="font-semibold text-ink-700">{{ $access->expires_at?->format('d/m/Y H:i') ?? 'sem prazo' }}</strong>
                        </p>
                    </div>

                    <div class="max-w-2xl flex-1">
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

                    <div class="flex shrink-0 flex-wrap gap-2">
                        <a href="{{ route('access.edit', $access) }}" class="rounded-xl border border-ink-200 bg-white px-4 py-2.5 text-sm font-semibold text-ink-700 transition hover:bg-ink-50">
                            Editar
                        </a>

                        @if ($access->user->status === 'active')
                            <form method="POST" action="{{ route('access.accounts.deactivate', $access->user) }}" onsubmit="return confirm('Inativar esta conta globalmente? Use Revogar se a intenção for remover apenas o acesso desta organização.');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-900 transition hover:bg-amber-100">
                                    Inativar conta
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('access.accounts.reactivate', $access->user) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded-xl border border-clinical-200 bg-clinical-50 px-4 py-2.5 text-sm font-semibold text-clinical-800 transition hover:bg-clinical-100">
                                    Reativar conta
                                </button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('access.revoke', $access) }}" onsubmit="return confirm('Revogar este acesso institucional? O histórico será preservado.');">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="rounded-xl border border-emergency-200 bg-emergency-50 px-4 py-2.5 text-sm font-semibold text-emergency-800 transition hover:bg-emergency-100">
                                Revogar
                            </button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="px-6 py-16 text-center">
                <h2 class="text-lg font-semibold text-navy-950">Nenhum acesso ativo</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-ink-500">Não há concessões vigentes para a organização atual.</p>
                <a href="{{ route('access.create') }}" class="mt-6 inline-flex rounded-xl bg-navy-950 px-5 py-3 text-sm font-semibold text-white">Conceder primeiro acesso</a>
            </div>
        @endforelse
    </section>

    <div class="mt-6">{{ $accesses->links() }}</div>
</x-layouts.app>
