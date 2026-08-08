@php
$statusLabel = match ($execution->status) {
    'running' => 'Em execução',
    'completed' => 'Concluída',
    'cancelled' => 'Cancelada',
    default => 'Rascunho',
};
$statusVariant = match ($execution->status) {
    'running' => 'alert',
    'completed' => 'clinical',
    'cancelled' => 'emergency',
    default => 'navy',
};
@endphp

<x-layouts.app :current="'scenarios'" :title="'Execução ' . $execution->sequence_number . ' · Tactical Scenario Lab'">
    <x-slot:breadcrumbs>
        <x-breadcrumb :items="[
            ['label' => 'Painel', 'href' => route('dashboard')],
            ['label' => 'Cenários', 'href' => route('scenarios.index')],
            ['label' => $execution->scenarioVersion->scenario->title, 'href' => route('scenarios.show', $execution->scenarioVersion->scenario)],
            ['label' => 'Execução ' . $execution->sequence_number],
        ]" />
    </x-slot:breadcrumbs>

    <x-slot:header>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <x-badge :variant="$statusVariant" size="sm" dot>{{ $statusLabel }}</x-badge>
                    <x-badge variant="navy" size="sm">Versão {{ $execution->scenarioVersion->version_number }}</x-badge>
                </div>
                <h1 class="mt-3 font-display text-3xl font-semibold tracking-tight text-navy-950">
                    Execução {{ $execution->sequence_number }}
                </h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-ink-500">
                    {{ $execution->scenarioVersion->scenario->title }}
                </p>
            </div>

            <x-button href="{{ route('scenarios.show', $execution->scenarioVersion->scenario) }}" variant="secondary">
                Voltar ao cenário
            </x-button>
        </div>
    </x-slot:header>

    @if (session('success'))
        <div class="mb-6">
            <x-alert variant="success" title="Operação concluída">{{ session('success') }}</x-alert>
        </div>
    @endif

    <x-card title="Fundação da execução" subtitle="Simulation Engine · M3" accent="navy">
        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Execução</dt>
                <dd class="mt-1 font-display text-2xl font-semibold text-navy-950">#{{ $execution->sequence_number }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Versão</dt>
                <dd class="mt-1 text-sm font-semibold text-ink-900">{{ $execution->scenarioVersion->version_number }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Estado</dt>
                <dd class="mt-1 text-sm font-semibold text-ink-900">{{ $statusLabel }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-500">Escala estimada</dt>
                <dd class="mt-1 text-sm font-semibold tabular-nums text-ink-900">
                    {{ number_format((int) $execution->scenarioVersion->estimated_casualty_count, 0, ',', '.') }} vítimas
                </dd>
            </div>
        </dl>

        <p class="mt-6 border-t border-stone-100 pt-5 text-sm leading-6 text-ink-600">
            A estrutura desta execução já está separada da definição versionada do cenário. Equipes, participantes, timeline, injects e recursos serão incorporados incrementalmente neste mesmo marco.
        </p>
    </x-card>
</x-layouts.app>
