<x-layouts.app :current="'organizations'" :title="'Nova organização · Tactical Medicine Academy'">
    <x-slot:header>
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emergency-700">Cadastro institucional</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">Nova organização</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-500">Comece com os dados essenciais. Informações complementares poderão ser adicionadas depois.</p>
        </div>
    </x-slot:header>

    <form method="POST" action="{{ route('organizations.store') }}" class="max-w-3xl space-y-6">
        @csrf

        <section class="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm">
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="sm:col-span-2">
                    <span class="text-sm font-semibold text-navy-950">Nome da organização</span>
                    <input name="name" value="{{ old('name') }}" required maxlength="160" class="mt-2 w-full rounded-xl border border-ink-300 bg-white px-4 py-3 text-sm text-navy-950 outline-none transition focus:border-navy-700 focus:ring-4 focus:ring-navy-100" placeholder="Ex.: Tactical Medicine Academy">
                    @error('name') <span class="mt-1 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                </label>

                <label>
                    <span class="text-sm font-semibold text-navy-950">Tipo</span>
                    <select name="kind" required class="mt-2 w-full rounded-xl border border-ink-300 bg-white px-4 py-3 text-sm text-navy-950 outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                        @foreach (['tma' => 'TMA', 'corporation' => 'Corporação', 'military' => 'Militar', 'school' => 'Escola', 'university' => 'Universidade', 'prefecture' => 'Prefeitura', 'hospital' => 'Hospital', 'clinic' => 'Clínica', 'company' => 'Empresa', 'partner' => 'Parceira', 'client' => 'Cliente', 'other' => 'Outra'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('kind', 'other') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('kind') <span class="mt-1 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                </label>

                <label>
                    <span class="text-sm font-semibold text-navy-950">Status</span>
                    <select name="status" required class="mt-2 w-full rounded-xl border border-ink-300 bg-white px-4 py-3 text-sm text-navy-950 outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                        <option value="active" @selected(old('status', 'active') === 'active')>Ativa</option>
                        <option value="inactive" @selected(old('status') === 'inactive')>Inativa</option>
                    </select>
                </label>

                <label class="sm:col-span-2">
                    <span class="text-sm font-semibold text-navy-950">Observações <span class="font-normal text-ink-400">(opcional)</span></span>
                    <textarea name="notes" rows="4" maxlength="3000" class="mt-2 w-full rounded-xl border border-ink-300 bg-white px-4 py-3 text-sm text-navy-950 outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100" placeholder="Contexto institucional, escopo ou informações úteis.">{{ old('notes') }}</textarea>
                </label>
            </div>
        </section>

        <div class="flex flex-wrap gap-3">
            <x-button type="submit">Salvar organização</x-button>
            <x-button href="{{ route('organizations.index') }}" variant="secondary">Cancelar</x-button>
        </div>
    </form>
</x-layouts.app>
