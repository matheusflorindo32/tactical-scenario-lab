<x-layouts.app :current="'access'" :title="'Acessos institucionais · Tactical Scenario Lab'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => 'Painel', 'href' => route('dashboard')],
            ['label' => 'Acessos institucionais'],
        ]" />
    </x-slot:breadcrumbs>

    <x-slot:header>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <x-badge variant="navy" size="sm" dot>Governança de acesso</x-badge>
                <h1 class="mt-3 font-display text-3xl font-semibold tracking-tight text-navy-950">Acessos institucionais</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-500">
                    Concessões ativas em <strong class="font-semibold text-navy-950">{{ $organization->name }}</strong>. A autorização continua definida pelo backend; esta tela apenas apresenta e opera os fluxos permitidos.
                </p>
            </div>
            <x-button href="{{ route('access.create') }}">Conceder acesso</x-button>
        </div>
    </x-slot:header>

    @error('access')
        <div class="mb-5"><x-alert variant="danger" title="Acesso não alterado">{{ $message }}</x-alert></div>
    @enderror

    @error('account')
        <div class="mb-5"><x-alert variant="warning" title="Conta não alterada">{{ $message }}</x-alert></div>
    @enderror

    <div class="mb-5 rounded-xl border border-stone-200 bg-white px-5 py-4 text-sm leading-6 text-ink-700 shadow-sm">
        <strong class="font-semibold text-navy-950">Conta global × acesso local:</strong>
        revogar remove apenas a concessão desta organização. Inativar a conta bloqueia o login global e, por segurança, só é permitido aqui quando a conta não possui outra concessão ativa em outra organização.
    </div>

    <x-table
        label="Acessos institucionais"
        :empty="$accesses->isEmpty()"
        empty-title="Nenhum acesso ativo"
        empty-description="Não há concessões vigentes para a organização atual."
    >
        <thead class="bg-stone-50 text-xs font-semibold uppercase tracking-[0.08em] text-ink-500">
            <tr>
                <th scope="col" class="px-5 py-3">Conta</th>
                <th scope="col" class="px-5 py-3">Papel e validade</th>
                <th scope="col" class="px-5 py-3">Habilidades efetivas</th>
                <th scope="col" class="px-5 py-3">Ações</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-stone-100 bg-white">
            @foreach ($accesses as $access)
                <tr class="align-top transition-colors hover:bg-stone-50/70">
                    <td class="px-5 py-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-navy-950">{{ $access->user->name }}</p>
                            <x-badge :variant="$access->user->status === 'active' ? 'clinical' : 'neutral'" size="sm" dot>
                                {{ $access->user->status === 'active' ? 'Conta ativa' : 'Conta inativa' }}
                            </x-badge>
                        </div>
                        <p class="mt-1 text-sm text-ink-500">{{ $access->user->email }}</p>
                    </td>
                    <td class="px-5 py-4">
                        <p class="text-sm text-ink-700">Papel: <strong class="font-semibold text-navy-950">{{ $access->role }}</strong></p>
                        <p class="mt-1 text-xs text-ink-500">Validade: {{ $access->expires_at?->format('d/m/Y H:i') ?? 'sem prazo' }}</p>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex max-w-xl flex-wrap gap-2">
                            @forelse ($access->abilities ?? [] as $ability)
                                <x-badge variant="navy" size="sm">{{ $abilityLabels[$ability] ?? $ability }}</x-badge>
                            @empty
                                <span class="text-sm text-ink-500">Nenhuma habilidade atribuída.</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex min-w-56 flex-wrap gap-2">
                            <x-button href="{{ route('access.edit', $access) }}" variant="secondary" size="sm">Editar</x-button>

                            @if ($access->user->status === 'active')
                                <form method="POST" action="{{ route('access.accounts.deactivate', $access->user) }}" onsubmit="return confirm('Inativar esta conta globalmente? Use Revogar se a intenção for remover apenas o acesso desta organização.');">
                                    @csrf
                                    @method('PATCH')
                                    <x-button type="submit" variant="secondary" size="sm">Inativar conta</x-button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('access.accounts.reactivate', $access->user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-button type="submit" variant="success" size="sm">Reativar conta</x-button>
                                </form>
                            @endif

                            <form method="POST" action="{{ route('access.revoke', $access) }}" onsubmit="return confirm('Revogar este acesso institucional? O histórico será preservado.');">
                                @csrf
                                @method('PATCH')
                                <x-button type="submit" variant="danger" size="sm">Revogar</x-button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table>

    @if ($accesses->hasPages())
        <div class="mt-6">{{ $accesses->links() }}</div>
    @endif
</x-layouts.app>
