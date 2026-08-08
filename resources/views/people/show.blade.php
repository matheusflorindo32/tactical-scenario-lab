<x-layouts.app :current="'people'" :title="$person->preferredName().' · Tactical Medicine Academy'">
    <x-slot:header>
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex min-w-0 items-center gap-4">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-navy-950 text-xl font-bold text-white shadow-sm">
                    {{ Str::upper(Str::substr($person->preferredName(), 0, 2)) }}
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $person->status === 'active' ? 'bg-clinical-50 text-clinical-800' : 'bg-alert-50 text-alert-800' }}">
                            {{ $person->status === 'active' ? 'Ativo' : ($person->status === 'inactive' ? 'Inativo' : 'Cadastro mínimo válido') }}
                        </span>
                        <span class="text-xs text-ink-400">UUID {{ Str::limit($person->uuid, 13, '…') }}</span>
                    </div>
                    <h1 class="mt-2 truncate font-display text-3xl font-semibold tracking-tight text-navy-950">{{ $person->preferredName() }}</h1>
                    @if ($person->social_name && $person->social_name !== $person->display_name)
                        <p class="mt-1 text-sm text-ink-500">Nome de cadastro: {{ $person->display_name }}</p>
                    @endif
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                <x-button href="{{ route('people.edit', $person) }}" variant="secondary">Editar dados gerais</x-button>
                <x-button href="{{ route('people.index') }}" variant="secondary">Voltar para pessoas</x-button>
            </div>
        </div>
    </x-slot:header>

    @php
        $pending = $person->pendingFields();
        $completedGroups = 4 - count($pending);
        $completion = max(25, (int) round(($completedGroups / 4) * 100));
    @endphp

    <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
        <div class="space-y-6">
            <section class="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-navy-950">Completude do cadastro</h2>
                        <p class="mt-1 text-sm text-ink-500">Pendências opcionais não bloqueiam o uso operacional.</p>
                    </div>
                    <strong class="text-2xl text-navy-950">{{ $completion }}%</strong>
                </div>
                <div class="mt-5 h-2 overflow-hidden rounded-full bg-ink-100" role="progressbar" aria-valuenow="{{ $completion }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="h-full rounded-full bg-clinical-600 transition-all" style="width: {{ $completion }}%"></div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    @forelse ($pending as $field)
                        <span class="rounded-full bg-alert-50 px-3 py-1.5 text-xs font-medium text-alert-800">
                            {{ ['birth_date' => 'Nascimento opcional', 'photo_path' => 'Foto opcional', 'documents' => 'Documento opcional', 'contacts' => 'Contato opcional'][$field] ?? $field }}
                        </span>
                    @empty
                        <span class="rounded-full bg-clinical-50 px-3 py-1.5 text-xs font-medium text-clinical-800">Cadastro enriquecido</span>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-navy-950">Documentos e códigos</h2>
                        <p class="mt-1 text-sm text-ink-500">Valores integrais permanecem criptografados. Esta tela mostra somente máscaras.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="rounded-full bg-navy-50 px-3 py-1 text-xs font-semibold text-navy-800">{{ $person->identifiers->count() }}</span>
                        <x-button href="{{ route('people.identifiers.create', $person) }}" variant="secondary">Adicionar documento</x-button>
                    </div>
                </div>
                <div class="mt-5 divide-y divide-ink-100">
                    @forelse ($person->identifiers as $identifier)
                        <div class="flex flex-col gap-2 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-wide text-ink-500">{{ str_replace('_', ' ', $identifier->type) }}</p>
                                <p class="mt-1 font-mono text-sm text-navy-950">{{ $identifier->masked() }}</p>
                            </div>
                            @if ($identifier->is_primary)
                                <span class="w-fit rounded-full bg-clinical-50 px-2.5 py-1 text-xs font-medium text-clinical-800">Principal</span>
                            @endif
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-ink-500">Nenhum documento informado. O cadastro continua válido.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-navy-950">Contatos</h2>
                        <p class="mt-1 text-sm text-ink-500">Canais protegidos e vinculados ao contexto institucional.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="rounded-full bg-navy-50 px-3 py-1 text-xs font-semibold text-navy-800">{{ $person->contacts->count() }}</span>
                        <x-button href="{{ route('people.contacts.create', $person) }}" variant="secondary">Adicionar contato</x-button>
                    </div>
                </div>
                <div class="mt-5 divide-y divide-ink-100">
                    @forelse ($person->contacts as $contact)
                        <div class="flex items-center justify-between gap-4 py-4 first:pt-0 last:pb-0">
                            <div>
                                <p class="text-sm font-semibold text-navy-950">{{ ucfirst($contact->label ?: $contact->type) }}</p>
                                <p class="mt-1 text-sm text-ink-500">{{ $contact->masked() }}</p>
                            </div>
                            @if ($contact->is_primary)
                                <span class="rounded-full bg-clinical-50 px-2.5 py-1 text-xs font-medium text-clinical-800">Principal</span>
                            @endif
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-ink-500">Nenhum contato informado. O cadastro continua válido.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-ink-500">Vínculos institucionais</h2>
                    <x-button href="{{ route('people.memberships.create', $person) }}" variant="secondary">Novo vínculo</x-button>
                </div>
                <div class="mt-5 space-y-4">
                    @forelse ($person->memberships as $membership)
                        <div class="rounded-xl border p-4 {{ $membership->isActive() ? 'border-clinical-200 bg-clinical-50/40' : 'border-ink-100 bg-ink-50/60' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-navy-950">{{ $membership->organization->name }}</p>
                                    <p class="mt-1 text-sm text-ink-500">{{ $membership->unit?->name ?? 'Sem unidade definida' }}</p>
                                </div>
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $membership->isActive() ? 'bg-clinical-100 text-clinical-800' : 'bg-ink-200 text-ink-700' }}">
                                    {{ $membership->isActive() ? 'Ativo' : 'Encerrado' }}
                                </span>
                            </div>
                            @if ($membership->position)
                                <p class="mt-2 text-sm font-medium text-ink-700">{{ $membership->position }}</p>
                            @endif
                            <p class="mt-3 text-xs text-ink-500">
                                Início: {{ $membership->started_at?->format('d/m/Y') ?? 'não informado' }}
                                @if ($membership->ended_at)
                                    · Encerramento: {{ $membership->ended_at->format('d/m/Y') }}
                                @endif
                            </p>
                            @if ($membership->isActive())
                                <form method="POST" action="{{ route('people.memberships.close', [$person, $membership]) }}" class="mt-4" onsubmit="return confirm('Confirmar o encerramento deste vínculo institucional? Papéis poderão ser revogados quando não houver outro vínculo ativo na organização.');">
                                    @csrf
                                    @method('PATCH')
                                    <x-button type="submit" variant="danger">Encerrar vínculo</x-button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-ink-500">Nenhum vínculo cadastrado.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-ink-500">Papéis contextuais</h2>
                    <x-button href="{{ route('people.roles.create', $person) }}" variant="secondary">Novo papel</x-button>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    @forelse ($person->roles as $role)
                        <span class="rounded-full px-3 py-1.5 text-xs font-medium {{ $role->revoked_at ? 'bg-ink-100 text-ink-500 line-through' : 'bg-navy-50 text-navy-800' }}">
                            {{ ucfirst(str_replace('_', ' ', $role->role)) }}
                        </span>
                    @empty
                        <span class="text-sm text-ink-500">Nenhum papel atribuído.</span>
                    @endforelse
                </div>
            </section>

            @if ($person->status !== 'inactive')
                <section class="rounded-2xl border border-emergency-200 bg-emergency-50 p-6">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-emergency-800">Zona de controle</h2>
                    <p class="mt-3 text-sm leading-6 text-emergency-800">A inativação preserva documentos, contatos, vínculos, papéis e histórico.</p>
                    <form method="POST" action="{{ route('people.deactivate', $person) }}" class="mt-5" onsubmit="return confirm('Confirmar a inativação desta pessoa?');">
                        @csrf
                        @method('PATCH')
                        <x-button type="submit" variant="danger">Inativar pessoa</x-button>
                    </form>
                </section>
            @endif

            @if ($person->notes)
                <section class="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-ink-500">Observações</h2>
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-ink-600">{{ $person->notes }}</p>
                </section>
            @endif
        </aside>
    </div>
</x-layouts.app>
