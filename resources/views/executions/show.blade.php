<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">Simulation Engine</p>
            <h1 class="text-2xl font-bold text-navy-950">
                Execução {{ $execution->sequence_number }}
            </h1>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="rounded-lg border border-clinical-200 bg-clinical-50 px-4 py-3 text-sm text-clinical-800" role="status">
                {{ session('success') }}
            </div>
        @endif

        <section class="rounded-xl border border-stone-200 bg-white p-6 shadow-sm" aria-labelledby="execution-summary-heading">
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-start">
                <div>
                    <h2 id="execution-summary-heading" class="text-lg font-semibold text-navy-950">
                        {{ $execution->scenarioVersion->scenario->title }}
                    </h2>
                    <p class="mt-1 text-sm text-stone-600">
                        Versão {{ $execution->scenarioVersion->version_number }} · execução {{ $execution->sequence_number }}
                    </p>
                </div>

                <x-badge :variant="$execution->status === 'running' ? 'alert' : ($execution->status === 'completed' ? 'clinical' : 'navy')" dot>
                    {{ $execution->status }}
                </x-badge>
            </div>

            <p class="mt-5 text-sm leading-6 text-stone-600">
                Esta é a fundação do cockpit da execução. Equipes, participantes, timeline, injects e recursos serão incorporados de forma incremental neste marco.
            </p>
        </section>
    </div>
</x-app-layout>
