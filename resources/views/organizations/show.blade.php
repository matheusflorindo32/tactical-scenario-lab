<x-layouts.app :current="'organizations'" :title="$organization->name.' · Tactical Medicine Academy'">
    <x-slot:header>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emergency-700">Organização</p>
                    <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $organization->status === 'active' ? 'bg-clinical-50 text-clinical-800' : 'bg-ink-100 text-ink-600' }}">{{ $organization->status === 'active' ? 'Ativa' : 'Inativa' }}</span>
                </div>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">{{ $organization->name }}</h1>
                <p class="mt-2 text-sm text-ink-500">{{ ucfirst(str_replace('_', ' ', $organization->kind)) }} · Código público {{ Str::limit($organization->uuid, 13, '…') }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <x-button href="{{ route('people.create', ['organization_id' => $organization->id]) }}">Cadastrar pessoa</x-button>
                <x-button href="{{ route('organizations.units.create', $organization) }}" variant="secondary">Nova unidade</x-button>
                <x-button href="{{ route('organizations.index') }}" variant="secondary">Voltar</x-button>
            </div>
        </div>
    </x-slot:header>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-navy-950">Unidades</h2>
                <span class="text-sm text-ink-500">{{ $organization->units->count() }} cadastradas</span>
            </div>
            <div class="mt-5 divide-y divide-ink-100">
                @forelse ($organization->units as $unit)
                    <div class="flex items-center justify-between gap-4 py-4 first:pt-0 last:pb-0">
                        <div>
                            <p class="font-semibold text-navy-950">{{ $unit->name }}</p>
                            <p class="mt-1 text-sm text-ink-500">{{ ucfirst(str_replace('_', ' ', $unit->kind)) }}</p>
                            @if ($unit->parent)
                                <p class="mt-1 text-xs text-ink-400">Vinculada a {{ $unit->parent->name }}</p>
                            @endif
                        </div>
                        <span class="rounded-full bg-navy-50 px-2.5 py-1 text-xs font-medium text-navy-800">{{ $unit->status === 'active' ? 'Ativa' : 'Inativa' }}</span>
                    </div>
                @empty
                    <div class="py-8 text-center">
                        <p class="text-sm text-ink-500">Nenhuma unidade cadastrada.</p>
                        <div class="mt-4"><x-button href="{{ route('organizations.units.create', $organization) }}" variant="secondary">Cadastrar primeira unidade</x-button></div>
                    </div>
                @endforelse
            </div>
        </section>

        <aside class="space-y-6">
            <section class="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-ink-500">Resumo</h2>
                <dl class="mt-5 space-y-4">
                    <div class="flex items-center justify-between"><dt class="text-sm text-ink-500">Unidades</dt><dd class="font-semibold text-navy-950">{{ $organization->units->count() }}</dd></div>
                    <div class="flex items-center justify-between"><dt class="text-sm text-ink-500">Vínculos</dt><dd class="font-semibold text-navy-950">{{ $organization->memberships->count() }}</dd></div>
                    <div class="flex items-center justify-between"><dt class="text-sm text-ink-500">Status</dt><dd class="font-semibold text-navy-950">{{ $organization->status === 'active' ? 'Ativa' : 'Inativa' }}</dd></div>
                </dl>
            </section>

            @if ($organization->notes)
                <section class="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-ink-500">Observações</h2>
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-ink-600">{{ $organization->notes }}</p>
                </section>
            @endif
        </aside>
    </div>
</x-layouts.app>
