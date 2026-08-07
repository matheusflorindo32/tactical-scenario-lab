<x-layouts.app :current="'people'" :title="'Cadastro rápido · Tactical Medicine Academy'">
    <x-slot:header>
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emergency-700">Fluxo operacional</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-navy-950">Cadastro rápido de pessoa</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-500">Salve o mínimo necessário agora. Documentos, contatos e informações complementares poderão ser inseridos depois.</p>
        </div>
    </x-slot:header>

    @if ($organizations->isEmpty())
        <section class="max-w-3xl rounded-2xl border border-alert-200 bg-alert-50 p-6">
            <h2 class="font-semibold text-alert-900">Cadastre uma organização primeiro</h2>
            <p class="mt-2 text-sm leading-6 text-alert-800">O vínculo institucional é necessário para definir o contexto inicial da pessoa.</p>
            <div class="mt-5"><x-button href="{{ route('organizations.create') }}">Cadastrar organização</x-button></div>
        </section>
    @else
        <form method="POST" action="{{ route('people.store') }}" class="max-w-4xl space-y-6" x-data="{ organization: '{{ old('organization_id', request('organization_id')) }}' }">
            @csrf

            <section class="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm">
                <div class="mb-6 flex items-start gap-3 rounded-xl border border-clinical-200 bg-clinical-50 p-4">
                    <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-clinical-700 text-sm font-bold text-white">✓</div>
                    <div>
                        <p class="text-sm font-semibold text-clinical-900">Cadastro mínimo válido</p>
                        <p class="mt-1 text-sm leading-5 text-clinical-800">CPF, RG, matrícula, e-mail e telefone não são exigidos. O sistema gerará um código temporário protegido.</p>
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <label class="sm:col-span-2">
                        <span class="text-sm font-semibold text-navy-950">Nome ou identificação operacional</span>
                        <input name="display_name" value="{{ old('display_name') }}" required maxlength="120" autofocus class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100" placeholder="Ex.: João Silva ou Aluno Alfa">
                        @error('display_name') <span class="mt-1 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                    </label>

                    <label>
                        <span class="text-sm font-semibold text-navy-950">Nome social <span class="font-normal text-ink-400">(opcional)</span></span>
                        <input name="social_name" value="{{ old('social_name') }}" maxlength="120" class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                    </label>

                    <label>
                        <span class="text-sm font-semibold text-navy-950">Data de nascimento <span class="font-normal text-ink-400">(opcional)</span></span>
                        <input type="date" name="birth_date" value="{{ old('birth_date') }}" max="{{ now()->toDateString() }}" class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                    </label>
                </div>
            </section>

            <section class="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm">
                <div>
                    <h2 class="text-lg font-semibold text-navy-950">Vínculo inicial</h2>
                    <p class="mt-1 text-sm text-ink-500">O papel é contextual e poderá coexistir com outros papéis no futuro.</p>
                </div>

                <div class="mt-6 grid gap-5 sm:grid-cols-2">
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
                        <span class="text-sm font-semibold text-navy-950">Papel inicial</span>
                        <select name="role" required class="mt-2 w-full rounded-xl border border-ink-300 bg-white px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                            @foreach (['student' => 'Aluno', 'instructor' => 'Instrutor', 'evaluator' => 'Avaliador', 'coordinator' => 'Coordenador', 'support' => 'Apoio', 'manager_org' => 'Gestor institucional', 'auditor' => 'Auditor', 'viewer' => 'Visualizador'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('role', 'student') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span class="text-sm font-semibold text-navy-950">Função, posto ou graduação <span class="font-normal text-ink-400">(opcional)</span></span>
                        <input name="position" value="{{ old('position') }}" maxlength="120" class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                    </label>

                    <label class="sm:col-span-2">
                        <span class="text-sm font-semibold text-navy-950">Observações <span class="font-normal text-ink-400">(opcional)</span></span>
                        <textarea name="notes" rows="4" maxlength="3000" class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">{{ old('notes') }}</textarea>
                    </label>
                </div>
            </section>

            <div class="flex flex-wrap gap-3">
                <x-button type="submit">Salvar e abrir ficha</x-button>
                <x-button href="{{ route('people.index') }}" variant="secondary">Cancelar</x-button>
            </div>
        </form>
    @endif
</x-layouts.app>
