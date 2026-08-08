<x-layouts.app :current="'organizations'" :title="'Editar organização · Tactical Medicine Academy'">
    <x-slot:header>
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emergency-700">Gestão institucional</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">Editar organização</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-500">Atualize os dados institucionais sem apagar unidades, vínculos, papéis ou o histórico de auditoria.</p>
        </div>
    </x-slot:header>

    <form method="POST" action="{{ route('organizations.update', $organization) }}" class="max-w-4xl space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm">
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="sm:col-span-2">
                    <span class="text-sm font-semibold text-navy-950">Nome institucional</span>
                    <input name="name" value="{{ old('name', $organization->name) }}" required maxlength="160" autofocus class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                    @error('name') <span class="mt-1 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                </label>

                <label>
                    <span class="text-sm font-semibold text-navy-950">Tipo</span>
                    <select name="kind" required class="mt-2 w-full rounded-xl border border-ink-300 bg-white px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                        @foreach (['tma' => 'TMA', 'corporation' => 'Corporação', 'military' => 'Militar', 'school' => 'Escola', 'university' => 'Universidade', 'prefecture' => 'Prefeitura', 'hospital' => 'Hospital', 'clinic' => 'Clínica', 'company' => 'Empresa', 'partner' => 'Parceiro', 'client' => 'Cliente', 'other' => 'Outro'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('kind', $organization->kind) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="text-sm font-semibold text-navy-950">Status</span>
                    <select name="status" required class="mt-2 w-full rounded-xl border border-ink-300 bg-white px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                        <option value="active" @selected(old('status', $organization->status) === 'active')>Ativa</option>
                        <option value="inactive" @selected(old('status', $organization->status) === 'inactive')>Inativa</option>
                    </select>
                </label>

                <label class="sm:col-span-2">
                    <span class="text-sm font-semibold text-navy-950">Observações <span class="font-normal text-ink-400">(opcional)</span></span>
                    <textarea name="notes" rows="5" maxlength="3000" class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">{{ old('notes', $organization->notes) }}</textarea>
                </label>
            </div>
        </section>

        <div class="flex flex-wrap gap-3">
            <x-button type="submit">Salvar alterações</x-button>
            <x-button href="{{ route('organizations.show', $organization) }}" variant="secondary">Cancelar</x-button>
        </div>
    </form>
</x-layouts.app>
