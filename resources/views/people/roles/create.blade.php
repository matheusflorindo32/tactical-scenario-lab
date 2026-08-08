<x-layouts.app :current="'people'" :title="'Novo papel institucional · Tactical Medicine Academy'">
    <x-slot:header>
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emergency-700">Governança contextual</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">Atribuir papel institucional</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-500">Pessoa: <strong class="text-navy-950">{{ $person->preferredName() }}</strong>. O papel vale somente dentro da organização selecionada.</p>
        </div>
    </x-slot:header>

    @if ($organizations->isEmpty())
        <section class="max-w-3xl rounded-2xl border border-alert-200 bg-alert-50 p-6">
            <h2 class="font-semibold text-alert-900">Nenhum vínculo ativo disponível</h2>
            <p class="mt-2 text-sm leading-6 text-alert-800">Crie um vínculo institucional antes de atribuir um novo papel.</p>
            <div class="mt-5"><x-button href="{{ route('people.memberships.create', $person) }}">Criar vínculo</x-button></div>
        </section>
    @else
        <form method="POST" action="{{ route('people.roles.store', $person) }}" class="max-w-4xl space-y-6">
            @csrf

            <section class="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm">
                <div class="grid gap-5 sm:grid-cols-2">
                    <label>
                        <span class="text-sm font-semibold text-navy-950">Organização</span>
                        <select name="organization_id" required class="mt-2 w-full rounded-xl border border-ink-300 bg-white px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                            <option value="">Selecione</option>
                            @foreach ($organizations as $organization)
                                <option value="{{ $organization->id }}" @selected((string) old('organization_id') === (string) $organization->id)>{{ $organization->name }}</option>
                            @endforeach
                        </select>
                        @error('organization_id') <span class="mt-1 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                    </label>

                    <label>
                        <span class="text-sm font-semibold text-navy-950">Papel</span>
                        <select name="role" required class="mt-2 w-full rounded-xl border border-ink-300 bg-white px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                            @foreach ($roleOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('role') <span class="mt-1 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                    </label>

                    <fieldset class="sm:col-span-2">
                        <legend class="text-sm font-semibold text-navy-950">Habilidades específicas <span class="font-normal text-ink-400">(opcional)</span></legend>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            @foreach ($abilityOptions as $value => $label)
                                <label class="flex items-start gap-3 rounded-xl border border-ink-200 p-4 transition hover:bg-ink-50">
                                    <input type="checkbox" name="abilities[]" value="{{ $value }}" @checked(in_array($value, old('abilities', []), true)) class="mt-0.5 rounded border-ink-300 text-navy-900 focus:ring-navy-500">
                                    <span class="text-sm text-ink-700">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('abilities') <span class="mt-2 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                        @error('abilities.*') <span class="mt-2 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                    </fieldset>

                    <label class="sm:col-span-2">
                        <span class="text-sm font-semibold text-navy-950">Observações <span class="font-normal text-ink-400">(opcional)</span></span>
                        <textarea name="notes" rows="4" maxlength="3000" class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">{{ old('notes') }}</textarea>
                    </label>
                </div>
            </section>

            <div class="flex flex-wrap gap-3">
                <x-button type="submit">Atribuir papel</x-button>
                <x-button href="{{ route('people.show', $person) }}" variant="secondary">Cancelar</x-button>
            </div>
        </form>
    @endif
</x-layouts.app>
