<x-layouts.app :current="'people'" :title="'Adicionar identificador · Tactical Medicine Academy'">
    <x-slot:header>
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emergency-700">Dados protegidos</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">Adicionar identificador</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-500">Pessoa: <strong class="text-navy-950">{{ $person->preferredName() }}</strong>. O valor integral será criptografado e não aparecerá nas listagens.</p>
        </div>
    </x-slot:header>

    <form method="POST" action="{{ route('people.identifiers.store', $person) }}" class="max-w-3xl space-y-6">
        @csrf

        @if (request()->boolean('duplicate'))
            <section class="rounded-2xl border border-alert-300 bg-alert-50 p-5">
                <h2 class="font-semibold text-alert-900">Possível duplicidade detectada</h2>
                <p class="mt-2 text-sm leading-6 text-alert-800">Já existe outro cadastro com a mesma impressão digital nesta organização. Confirme somente após verificar que se trata de uma pessoa distinta ou de uma situação operacional legítima.</p>
                <label class="mt-4 flex items-start gap-3">
                    <input type="checkbox" name="confirm_duplicate" value="1" class="mt-1 rounded border-alert-400 text-emergency-700 focus:ring-emergency-200">
                    <span class="text-sm font-medium text-alert-900">Revisei o alerta e desejo continuar conscientemente.</span>
                </label>
            </section>
        @endif

        <section class="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm">
            <div class="mb-6 rounded-xl border border-clinical-200 bg-clinical-50 p-4 text-sm leading-6 text-clinical-900">
                <strong>Proteção ativa:</strong> criptografia do valor original, fingerprint HMAC para busca exata e máscara para exibição.
            </div>

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
                    <span class="text-sm font-semibold text-navy-950">Tipo</span>
                    <select name="type" required class="mt-2 w-full rounded-xl border border-ink-300 bg-white px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                        @foreach (['cpf' => 'CPF', 'rg' => 'RG', 'id_funcional' => 'Identidade funcional', 'matricula' => 'Matrícula', 'passaporte' => 'Passaporte', 'registro_profissional' => 'Registro profissional', 'temp_code' => 'Código temporário', 'qr' => 'Código QR', 'other' => 'Outro'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', 'matricula') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type') <span class="mt-1 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                </label>

                <label class="sm:col-span-2">
                    <span class="text-sm font-semibold text-navy-950">Valor do identificador</span>
                    <input name="value" value="{{ old('value') }}" required maxlength="255" autocomplete="off" class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 font-mono text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                    @error('value') <span class="mt-1 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                </label>

                <label>
                    <span class="text-sm font-semibold text-navy-950">Órgão emissor <span class="font-normal text-ink-400">(opcional)</span></span>
                    <input name="issuer" value="{{ old('issuer') }}" maxlength="60" class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                </label>

                <label>
                    <span class="text-sm font-semibold text-navy-950">Validade <span class="font-normal text-ink-400">(opcional)</span></span>
                    <input type="date" name="expires_at" value="{{ old('expires_at') }}" class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                </label>

                <label class="flex items-start gap-3 sm:col-span-2">
                    <input type="checkbox" name="is_primary" value="1" @checked(old('is_primary')) class="mt-1 rounded border-ink-300 text-navy-900 focus:ring-navy-200">
                    <span><strong class="block text-sm text-navy-950">Definir como principal</strong><span class="mt-1 block text-sm text-ink-500">Substitui o principal anterior apenas para o mesmo tipo e organização.</span></span>
                </label>
            </div>
        </section>

        <div class="flex flex-wrap gap-3">
            <x-button type="submit">Salvar identificador protegido</x-button>
            <x-button href="{{ route('people.show', $person) }}" variant="secondary">Cancelar</x-button>
        </div>
    </form>
</x-layouts.app>
