<x-layouts.app :current="'people'" :title="'Editar pessoa · Tactical Medicine Academy'">
    <x-slot:header>
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emergency-700">Gestão protegida</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">Editar cadastro</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-500">Atualize apenas os dados gerais. Documentos e contatos continuam em fluxos separados e protegidos.</p>
        </div>
    </x-slot:header>

    <form method="POST" action="{{ route('people.update', $person) }}" class="max-w-4xl space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm">
            <div class="grid gap-5 sm:grid-cols-2">
                <label class="sm:col-span-2">
                    <span class="text-sm font-semibold text-navy-950">Nome ou identificação operacional</span>
                    <input name="display_name" value="{{ old('display_name', $person->display_name) }}" required maxlength="120" autofocus class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                    @error('display_name') <span class="mt-1 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                </label>

                <label>
                    <span class="text-sm font-semibold text-navy-950">Nome social <span class="font-normal text-ink-400">(opcional)</span></span>
                    <input name="social_name" value="{{ old('social_name', $person->social_name) }}" maxlength="120" class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                </label>

                <label>
                    <span class="text-sm font-semibold text-navy-950">Data de nascimento <span class="font-normal text-ink-400">(opcional)</span></span>
                    <input type="date" name="birth_date" value="{{ old('birth_date', optional($person->birth_date)->format('Y-m-d')) }}" max="{{ now()->toDateString() }}" class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                    @error('birth_date') <span class="mt-1 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                </label>

                <label>
                    <span class="text-sm font-semibold text-navy-950">Situação do cadastro</span>
                    <select name="status" required class="mt-2 w-full rounded-xl border border-ink-300 bg-white px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                        @foreach (['incomplete' => 'Cadastro mínimo válido', 'active' => 'Ativo', 'inactive' => 'Inativo'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $person->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status') <span class="mt-1 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                </label>

                <label class="sm:col-span-2">
                    <span class="text-sm font-semibold text-navy-950">Observações <span class="font-normal text-ink-400">(opcional)</span></span>
                    <textarea name="notes" rows="5" maxlength="3000" class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">{{ old('notes', $person->notes) }}</textarea>
                </label>
            </div>
        </section>

        <div class="flex flex-wrap gap-3">
            <x-button type="submit">Salvar alterações</x-button>
            <x-button href="{{ route('people.show', $person) }}" variant="secondary">Cancelar</x-button>
        </div>
    </form>
</x-layouts.app>
