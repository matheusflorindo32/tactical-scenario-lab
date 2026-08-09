<x-layouts.app :current="'organizations'" :title="'Organizações · Tactical Scenario Lab'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => 'Painel', 'href' => route('dashboard')],
            ['label' => 'Organizações'],
        ]" />
    </x-slot:breadcrumbs>

    <x-slot:header>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <x-badge variant="navy" size="sm" dot>Estrutura institucional</x-badge>
                <h1 class="mt-3 font-display text-3xl font-semibold tracking-tight text-navy-950">Organizações</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-500">Instituições acessíveis, suas unidades e vínculos operacionais dentro da governança multi-organização.</p>
            </div>
            <x-button href="{{ route('organizations.create') }}">Nova organização</x-button>
        </div>
    </x-slot:header>

    <x-table
        label="Organizações acessíveis"
        :empty="$organizations->isEmpty()"
        empty-title="Nenhuma organização cadastrada"
        empty-description="Cadastre a primeira instituição para habilitar pessoas, unidades e vínculos."
    >
        <thead class="bg-stone-50 text-xs font-semibold uppercase tracking-[0.08em] text-ink-500">
            <tr>
                <th scope="col" class="px-5 py-3">Organização</th>
                <th scope="col" class="px-5 py-3">Status</th>
                <th scope="col" class="px-5 py-3">Unidades</th>
                <th scope="col" class="px-5 py-3">Vínculos</th>
                <th scope="col" class="px-5 py-3"><span class="sr-only">Ação</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-stone-100 bg-white">
            @foreach ($organizations as $organization)
                <tr class="transition-colors hover:bg-stone-50/70">
                    <td class="px-5 py-4 align-top">
                        <a href="{{ route('organizations.show', $organization) }}" class="font-semibold text-navy-950 hover:text-navy-700">{{ $organization->name }}</a>
                        <p class="mt-1 text-xs text-ink-500">{{ ucfirst(str_replace('_', ' ', $organization->kind)) }}</p>
                    </td>
                    <td class="px-5 py-4 align-top">
                        <x-badge :variant="$organization->status === 'active' ? 'clinical' : 'neutral'" size="sm" dot>
                            {{ $organization->status === 'active' ? 'Ativa' : 'Inativa' }}
                        </x-badge>
                    </td>
                    <td class="px-5 py-4 align-top">
                        <span class="font-mono text-sm font-semibold tabular-nums text-navy-950">{{ $organization->units_count }}</span>
                    </td>
                    <td class="px-5 py-4 align-top">
                        <span class="font-mono text-sm font-semibold tabular-nums text-navy-950">{{ $organization->memberships_count }}</span>
                    </td>
                    <td class="px-5 py-4 text-right align-top">
                        <x-button href="{{ route('organizations.show', $organization) }}" variant="ghost" size="sm">Abrir</x-button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-table>

    @if ($organizations->hasPages())
        <div class="mt-6">{{ $organizations->links() }}</div>
    @endif
</x-layouts.app>
