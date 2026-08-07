<x-layouts.app :current="'people'" :title="'Novo contato · Tactical Medicine Academy'">
    <x-slot:header>
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emergency-700">Contato protegido</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">Adicionar contato</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-500">O valor integral será criptografado. A ficha exibirá apenas uma versão mascarada.</p>
        </div>
    </x-slot:header>

    <form method="POST" action="{{ route('people.contacts.store', $person) }}" class="max-w-3xl space-y-6">
        @csrf

        @if (session('duplicate_warning'))
            <section class="rounded-2xl border border-alert-300 bg-alert-50 p-5">
                <h2 class="font-semibold text-alert-900">Possível duplicidade</h2>
                <p class="mt-2 text-sm leading-6 text-alert-800">{{ session('duplicate_warning') }}</p>
                <label class="mt-4 flex items-start gap-3">
                    <input type="checkbox" name="confirmed_duplicate" value="1" class="mt-1 rounded border-alert-400 text-alert-700 focus:ring-alert-300">
                    <span class="text-sm font-medium text-alert-900">Confirmo que revisei o alerta e desejo criar outro registro sem mesclagem automática.</span>
                </label>
            </section>
        @endif

        <section class="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm">
            <div class="mb-6 rounded-xl border border-clinical-200 bg-clinical-50 p-4">
                <p class="text-sm font-semibold text-clinical-900">Pessoa: {{ $person->preferredName() }}</p>
                <p class="mt-1 text-sm text-clinical-800">O contato não é obrigatório para manter o cadastro válido.</p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <label>
                    <span class="text-sm font-semibold text-navy-950">Organização</span>
                    <select name="organization_id" required class="mt-2 w-full rounded-xl border border-ink-300 bg-white px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                        <option value="">Selecione</option>
                        @foreach ($person->memberships->unique('organization_id') as $membership)
                            <option value="{{ $membership->organization_id }}" @selected((string) old('organization_id') === (string) $membership->organization_id)>{{ $membership->organization->name }}</option>
                        @endforeach
                    </select>
                    @error('organization_id') <span class="mt-1 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                </label>

                <label>
                    <span class="text-sm font-semibold text-navy-950">Tipo</span>
                    <select name="type" required class="mt-2 w-full rounded-xl border border-ink-300 bg-white px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                        @foreach (['email' => 'E-mail', 'phone' => 'Telefone', 'whatsapp' => 'WhatsApp', 'emergency' => 'Contato de emergência', 'other' => 'Outro'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', 'phone') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="sm:col-span-2">
                    <span class="text-sm font-semibold text-navy-950">Contato</span>
                    <input name="value" value="{{ old('value') }}" required maxlength="255" autocomplete="off" class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100" placeholder="Telefone, WhatsApp ou e-mail">
                    @error('value') <span class="mt-1 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                </label>

                <label>
                    <span class="text-sm font-semibold text-navy-950">Rótulo <span class="font-normal text-ink-400">(opcional)</span></span>
                    <input name="label" value="{{ old('label') }}" maxlength="60" class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100" placeholder="Ex.: Pessoal, Serviço, Emergência">
                </label>

                <label class="flex items-center gap-3 self-end rounded-xl border border-ink-200 px-4 py-3">
                    <input type="checkbox" name="is_primary" value="1" @checked(old('is_primary')) class="rounded border-ink-300 text-navy-900 focus:ring-navy-300">
                    <span class="text-sm font-medium text-navy-950">Definir como principal deste tipo</span>
                </label>

                <label class="sm:col-span-2">
                    <span class="text-sm font-semibold text-navy-950">Observações <span class="font-normal text-ink-400">(opcional)</span></span>
                    <textarea name="notes" rows="4" maxlength="2000" class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">{{ old('notes') }}</textarea>
                </label>
            </div>
        </section>

        <div class="flex flex-wrap gap-3">
            <x-button type="submit">Salvar contato protegido</x-button>
            <x-button href="{{ route('people.show', $person) }}" variant="secondary">Cancelar</x-button>
        </div>
    </form>
</x-layouts.app>
