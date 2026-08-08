<x-layouts.app :current="'organizations'" :title="'Organizações · Tactical Medicine Academy'">
    <x-slot:header>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emergency-700">Estrutura institucional</p>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">Organizações</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-500">Instituições, unidades e vínculos operacionais centralizados em uma estrutura preparada para expansão.</p>
            </div>
            <x-button href="{{ route('organizations.create') }}">Nova organização</x-button>
        </div>
    </x-slot:header>

    <section class="overflow-hidden rounded-2xl border border-ink-200 bg-white shadow-sm">
        @forelse ($organizations as $organization)
            <a href="{{ route('organizations.show', $organization) }}" class="group flex flex-col gap-4 border-b border-ink-100 px-5 py-5 transition last:border-b-0 hover:bg-navy-50/50 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="truncate text-base font-semibold text-navy-950 group-hover:text-emergency-700">{{ $organization->name }}</h2>
                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $organization->status === 'active' ? 'bg-clinical-50 text-clinical-800' : 'bg-ink-100 text-ink-600' }}">
                            {{ $organization->status === 'active' ? 'Ativa' : 'Inativa' }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-ink-500">{{ ucfirst(str_replace('_', ' ', $organization->kind)) }}</p>
                </div>
                <div class="flex gap-6 text-sm text-ink-600">
                    <span><strong class="text-navy-950">{{ $organization->units_count }}</strong> unidades</span>
                    <span><strong class="text-navy-950">{{ $organization->memberships_count }}</strong> vínculos</span>
                </div>
            </a>
        @empty
            <div class="px-6 py-16 text-center">
                <h2 class="text-lg font-semibold text-navy-950">Nenhuma organização cadastrada</h2>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-ink-500">Cadastre a primeira instituição para habilitar pessoas, unidades e vínculos.</p>
                <div class="mt-6"><x-button href="{{ route('organizations.create') }}">Cadastrar organização</x-button></div>
            </div>
        @endforelse
    </section>

    <div class="mt-6">{{ $organizations->links() }}</div>
</x-layouts.app>
