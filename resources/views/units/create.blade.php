<x-layouts.app :current="'organizations'" :title="'Nova unidade · Tactical Medicine Academy'">
    <x-slot:header>
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emergency-700">Estrutura institucional</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">Nova unidade</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-500">Organização: <strong class="text-navy-950">{{ $organization->name }}</strong></p>
        </div>
    </x-slot:header>

    <form method="POST" action="{{ route('units.store') }}" class="max-w-3xl space-y-6">
        @csrf
        <input type="hidden" name="organization_id" value="{{ $organization->id }}">

        <section class="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm">
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="sm:col-span-2">
                    <span class="text-sm font-semibold text-navy-950">Nome da unidade</span>
                    <input name="name" value="{{ old('name') }}" required maxlength="160" autofocus class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100" placeholder="Ex.: Centro de Treinamento Operacional">
                    @error('name') <span class="mt-1 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                </label>

                <label>
                    <span class="text-sm font-semibold text-navy-950">Tipo</span>
                    <select name="kind" required class="mt-2 w-full rounded-xl border border-ink-300 bg-white px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                        @foreach (['headquarters' => 'Sede', 'regional' => 'Regional', 'department' => 'Departamento', 'division' => 'Divisão', 'battalion' => 'Batalhão', 'company' => 'Companhia', 'platoon' => 'Pelotão', 'station' => 'Posto', 'school' => 'Escola', 'clinic' => 'Clínica', 'other' => 'Outra'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('kind', 'other') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('kind') <span class="mt-1 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                </label>

                <label>
                    <span class="text-sm font-semibold text-navy-950">Status</span>
                    <select name="status" required class="mt-2 w-full rounded-xl border border-ink-300 bg-white px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                        <option value="active" @selected(old('status', 'active') === 'active')>Ativa</option>
                        <option value="inactive" @selected(old('status') === 'inactive')>Inativa</option>
                    </select>
                </label>

                <label class="sm:col-span-2">
                    <span class="text-sm font-semibold text-navy-950">Unidade superior <span class="font-normal text-ink-400">(opcional)</span></span>
                    <select name="parent_unit_id" class="mt-2 w-full rounded-xl border border-ink-300 bg-white px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                        <option value="">Sem unidade superior</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}" @selected((string) old('parent_unit_id') === (string) $parent->id)>{{ $parent->name }}</option>
                        @endforeach
                    </select>
                    @error('parent_unit_id') <span class="mt-1 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                </label>

                <label class="sm:col-span-2">
                    <span class="text-sm font-semibold text-navy-950">Observações <span class="font-normal text-ink-400">(opcional)</span></span>
                    <textarea name="notes" rows="4" maxlength="3000" class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">{{ old('notes') }}</textarea>
                </label>
            </div>
        </section>

        <div class="flex flex-wrap gap-3">
            <x-button type="submit">Salvar unidade</x-button>
            <x-button href="{{ route('organizations.show', $organization) }}" variant="secondary">Cancelar</x-button>
        </div>
    </form>
</x-layouts.app>
