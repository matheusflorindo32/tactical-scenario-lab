<x-layouts.app :current="'access'" :title="'Conceder acesso · Tactical Medicine Academy'">
    <x-slot:header>
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emergency-700">Governança de acesso</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">Conceder acesso institucional</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-500">
                A concessão será vinculada exclusivamente a <strong class="font-semibold text-navy-950">{{ $organization->name }}</strong>.
                Informe o e-mail exato de uma conta já existente e selecione somente as habilidades necessárias.
            </p>
        </div>
    </x-slot:header>

    <form method="POST" action="{{ route('access.store') }}" class="max-w-3xl rounded-2xl border border-ink-200 bg-white p-6 shadow-sm">
        @csrf

        <div class="grid gap-6">
            <div>
                <label for="email" class="text-sm font-semibold text-navy-950">E-mail da conta</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="off"
                    class="mt-2 w-full rounded-xl border border-ink-200 px-4 py-3 text-sm text-navy-950 outline-none transition focus:border-navy-600 focus:ring-2 focus:ring-navy-100">
                @error('email') <p class="mt-2 text-sm text-emergency-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="role" class="text-sm font-semibold text-navy-950">Papel de acesso</label>
                <input id="role" name="role" type="text" value="{{ old('role', 'operator') }}" required maxlength="50"
                    class="mt-2 w-full rounded-xl border border-ink-200 px-4 py-3 text-sm text-navy-950 outline-none transition focus:border-navy-600 focus:ring-2 focus:ring-navy-100">
                <p class="mt-2 text-xs leading-5 text-ink-500">Identificador técnico em minúsculas, por exemplo: instructor, evaluator ou manager_org.</p>
                @error('role') <p class="mt-2 text-sm text-emergency-700">{{ $message }}</p> @enderror
            </div>

            <fieldset>
                <legend class="text-sm font-semibold text-navy-950">Habilidades</legend>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    @foreach ($abilityLabels as $ability => $label)
                        <label class="flex cursor-pointer gap-3 rounded-xl border border-ink-200 p-4 transition hover:border-navy-300 hover:bg-navy-50/40">
                            <input type="checkbox" name="abilities[]" value="{{ $ability }}"
                                @checked(in_array($ability, old('abilities', []), true))
                                class="mt-1 rounded border-ink-300 text-navy-900 focus:ring-navy-500">
                            <span>
                                <span class="block text-sm font-semibold text-navy-950">{{ $label }}</span>
                                <span class="mt-1 block font-mono text-xs text-ink-500">{{ $ability }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('abilities') <p class="mt-2 text-sm text-emergency-700">{{ $message }}</p> @enderror
                @error('abilities.*') <p class="mt-2 text-sm text-emergency-700">{{ $message }}</p> @enderror
            </fieldset>
        </div>

        <div class="mt-7 flex flex-wrap gap-3">
            <button type="submit" class="rounded-xl bg-navy-950 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-navy-800">
                Conceder acesso
            </button>
            <a href="{{ route('access.index') }}" class="rounded-xl border border-ink-200 bg-white px-5 py-3 text-sm font-semibold text-ink-700 transition hover:bg-ink-50">
                Cancelar
            </a>
        </div>
    </form>
</x-layouts.app>
