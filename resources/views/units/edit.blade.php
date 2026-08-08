<x-layouts.app :current="'organizations'" :title="'Editar unidade · Tactical Medicine Academy'">
    <x-slot:header>
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emergency-700">Gestão institucional</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">Editar unidade</h1>
            <p class="mt-2 text-sm text-ink-500">{{ $unit->organization->name }} · alterações preservam vínculos e histórico.</p>
        </div>
    </x-slot:header>

    <form method="POST" action="{{ route('units.update', $unit) }}" class="max-w-4xl space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm">
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="sm:col-span-2">
                    <span class="text-sm font-semibold text-navy-950">Nome da unidade</span>
                    <input name="name" value="{{ old('name', $unit->name) }}" required maxlength="160" autofocus class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                    @error('name') <span class="mt-1 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                </label>

                <label>
                    <span class="text-sm font-semibold text-navy-950">Tipo</span>
                    <select name="kind" required class="mt-2 w-full rounded-xl border border-ink-300 bg-white px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                        @foreach (['headquarters' => 'Sede', 'regional' => 'Regional', 'department' => 'Departamento', 'division' => 'Divisão', 'battalion' => 'Batalhão', 'company' => 'Companhia', 'platoon' => 'Pelotão', 'station' => 'Posto', 'school' => 'Escola', 'clinic' => 'Clínica', 'other' => 'Outro'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('kind', $unit->kind) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('kind') <span class="mt-1 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                </label>

                <label>
                    <span class="text-sm font-semibold text-navy-950">Status</span>
                    <select name="status" required class="mt-2 w-full rounded-xl border border-ink-300 bg-white px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                        <option value="active" @selected(old('status', $unit->status) === 'active')>Ativa</option>
                        <option value="inactive" @selected(old('status', $unit->status) === 'inactive')>Inativa</option>
                    </select>
                    @error('status') <span class="mt-1 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                </label>

                <label class="sm:col-span-2">
                    <span class="text-sm font-semibold text-navy-950">Unidade superior <span class="font-normal text-ink-400">(opcional)</span></span>
                    <select name="parent_unit_id" class="mt-2 w-full rounded-xl border border-ink-300 bg-white px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                        <option value="">Sem unidade superior</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}" @selected((string) old('parent_unit_id', $unit->parent_unit_id) === (string) $parent->id)>{{ $parent->name }}</option>
                        @endforeach
                    </select>
                    @error('parent_unit_id') <span class="mt-1 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                    <p class="mt-2 text-xs leading-5 text-ink-500">O sistema bloqueia autorreferência e ciclos hierárquicos.</p>
                </label>

                <label class="sm:col-span-2">
                    <span class="text-sm font-semibold text-navy-950">Observações <span class="font-normal text-ink-400">(opcional)</span></span>
                    <textarea name="notes" rows="5" maxlength="3000" class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">{{ old('notes', $unit->notes) }}</textarea>
                    @error('notes') <span class="mt-1 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                </label>
            </div>
        </section>

        <div class="flex flex-wrap gap-3">
            <x-button type="submit">Salvar alterações</x-button>
            <x-button href="{{ route('organizations.show', $unit->organization) }}" variant="secondary">Cancelar</x-button>
        </div>
    </form>
</x-layouts.app>
