<x-layouts.app :current="'people'" :title="'Novo vínculo · Tactical Medicine Academy'">
    <x-slot:header>
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emergency-700">Vínculo institucional</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">Adicionar vínculo</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-500">Associe {{ $person->preferredName() }} a outra organização ou unidade sem substituir os vínculos já existentes.</p>
        </div>
    </x-slot:header>

    <form method="POST" action="{{ route('people.memberships.store', $person) }}" class="max-w-4xl space-y-6" x-data="{ organization: '{{ old('organization_id') }}' }">
        @csrf

        <section class="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm">
            <div class="grid gap-5 sm:grid-cols-2">
                <label>
                    <span class="text-sm font-semibold text-navy-950">Organização</span>
                    <select name="organization_id" x-model="organization" required class="mt-2 w-full rounded-xl border border-ink-300 bg-white px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                        <option value="">Selecione</option>
                        @foreach ($organizations as $organization)
                            <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                        @endforeach
                    </select>
                    @error('organization_id') <span class="mt-1 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                </label>

                <label>
                    <span class="text-sm font-semibold text-navy-950">Unidade <span class="font-normal text-ink-400">(opcional)</span></span>
                    <select name="unit_id" class="mt-2 w-full rounded-xl border border-ink-300 bg-white px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                        <option value="">Sem unidade definida</option>
                        @foreach ($organizations as $organization)
                            @foreach ($organization->units as $unit)
                                <option value="{{ $unit->id }}" x-show="organization === '{{ $organization->id }}'" @selected((string) old('unit_id') === (string) $unit->id)>{{ $unit->name }}</option>
                            @endforeach
                        @endforeach
                    </select>
                    @error('unit_id') <span class="mt-1 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                </label>

                <label>
                    <span class="text-sm font-semibold text-navy-950">Função, posto ou graduação <span class="font-normal text-ink-400">(opcional)</span></span>
                    <input name="position" value="{{ old('position') }}" maxlength="120" class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                </label>

                <label>
                    <span class="text-sm font-semibold text-navy-950">Status</span>
                    <select name="status" required class="mt-2 w-full rounded-xl border border-ink-300 bg-white px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                        <option value="active" @selected(old('status', 'active') === 'active')>Ativo</option>
                        <option value="inactive" @selected(old('status') === 'inactive')>Inativo</option>
                    </select>
                </label>

                <label>
                    <span class="text-sm font-semibold text-navy-950">Início <span class="font-normal text-ink-400">(opcional)</span></span>
                    <input type="date" name="started_at" value="{{ old('started_at') }}" class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                </label>

                <label>
                    <span class="text-sm font-semibold text-navy-950">Término <span class="font-normal text-ink-400">(opcional)</span></span>
                    <input type="date" name="ended_at" value="{{ old('ended_at') }}" class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                    @error('ended_at') <span class="mt-1 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                </label>

                <label class="sm:col-span-2">
                    <span class="text-sm font-semibold text-navy-950">Observações <span class="font-normal text-ink-400">(opcional)</span></span>
                    <textarea name="notes" rows="4" maxlength="3000" class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">{{ old('notes') }}</textarea>
                </label>
            </div>
        </section>

        <div class="flex flex-wrap gap-3">
            <x-button type="submit">Salvar vínculo</x-button>
            <x-button href="{{ route('people.show', $person) }}" variant="secondary">Cancelar</x-button>
        </div>
    </form>
</x-layouts.app>
