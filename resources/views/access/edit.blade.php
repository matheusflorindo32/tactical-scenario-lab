<x-layouts.app :current="'access'" :title="'Editar acesso · Tactical Medicine Academy'">
    <x-slot:header>
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emergency-700">Governança de acesso</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">Editar habilidades de acesso</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-500">
                Conta <strong class="font-semibold text-navy-950">{{ $access->user->name }}</strong> · papel <strong class="font-semibold text-navy-950">{{ $access->role }}</strong> · organização <strong class="font-semibold text-navy-950">{{ $organization->name }}</strong>.
            </p>
        </div>
    </x-slot:header>

    <form method="POST" action="{{ route('access.update', $access) }}" class="max-w-3xl rounded-2xl border border-ink-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        <fieldset>
            <legend class="text-sm font-semibold text-navy-950">Habilidades efetivas</legend>
            <p class="mt-1 text-xs leading-5 text-ink-500">A remoção de <span class="font-mono">access.manage</span> é bloqueada quando esta for a última concessão administrativa da organização.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach ($abilityLabels as $ability => $label)
                    <label class="flex cursor-pointer gap-3 rounded-xl border border-ink-200 p-4 transition hover:border-navy-300 hover:bg-navy-50/40">
                        <input type="checkbox" name="abilities[]" value="{{ $ability }}"
                            @checked(in_array($ability, old('abilities', $access->abilities ?? []), true))
                            class="mt-1 rounded border-ink-300 text-navy-900 focus:ring-navy-500">
                        <span>
                            <span class="block text-sm font-semibold text-navy-950">{{ $label }}</span>
                            <span class="mt-1 block font-mono text-xs text-ink-500">{{ $ability }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('abilities') <p class="mt-3 text-sm text-emergency-700">{{ $message }}</p> @enderror
            @error('abilities.*') <p class="mt-3 text-sm text-emergency-700">{{ $message }}</p> @enderror
            @error('access') <p class="mt-3 text-sm text-emergency-700">{{ $message }}</p> @enderror
        </fieldset>

        <div class="mt-7 flex flex-wrap gap-3">
            <button type="submit" class="rounded-xl bg-navy-950 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-navy-800">
                Salvar habilidades
            </button>
            <a href="{{ route('access.index') }}" class="rounded-xl border border-ink-200 bg-white px-5 py-3 text-sm font-semibold text-ink-700 transition hover:bg-ink-50">
                Voltar
            </a>
        </div>
    </form>
</x-layouts.app>
