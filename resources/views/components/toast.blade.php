@props([])

<div
    x-data
    x-show="$store.ui.toast.visible"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    role="status"
    aria-live="polite"
    class="fixed bottom-6 right-6 z-50 w-full max-w-sm"
    x-cloak
>
    <template x-if="$store.ui.toast.kind === 'success'">
        <div class="flex items-start gap-3 rounded-md bg-clinical-500 p-4 text-white shadow-lg">
            <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M5 13l4 4L19 7"/></svg>
            <p class="flex-1 text-sm font-medium" x-text="$store.ui.toast.message"></p>
            <button x-on:click="$store.ui.toast.hide()" class="text-white/80 hover:text-white" aria-label="Fechar aviso">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M6 6l12 12M6 18L18 6"/></svg>
            </button>
        </div>
    </template>
    <template x-if="$store.ui.toast.kind === 'error'">
        <div class="flex items-start gap-3 rounded-md bg-emergency-500 p-4 text-white shadow-lg">
            <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 9v4m0 4h.01"/></svg>
            <p class="flex-1 text-sm font-medium" x-text="$store.ui.toast.message"></p>
            <button x-on:click="$store.ui.toast.hide()" class="text-white/80 hover:text-white" aria-label="Fechar aviso">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M6 6l12 12M6 18L18 6"/></svg>
            </button>
        </div>
    </template>
</div>

@if (session('success'))
    <script>
        document.addEventListener('alpine:initialized', () => {
            Alpine.store('ui').toast.show('success', @json(session('success')));
        });
    </script>
@endif
@if (session('error'))
    <script>
        document.addEventListener('alpine:initialized', () => {
            Alpine.store('ui').toast.show('error', @json(session('error')));
        });
    </script>
@endif
@if ($errors->any())
    @php
        // Mostra o primeiro erro concreto no toast em vez do texto genérico.
        $firstError = $errors->first();
        $firstField = array_key_first($errors->messages());
        $friendlyLabels = [
            'environment'                => 'Ambiente',
            'threat_level'               => 'Nível de ameaça',
            'casualties'                 => 'Vítimas',
            'mechanism'                  => 'Mecanismo do trauma',
            'resources'                  => 'Recursos',
            'score'                      => 'Nota',
            'debrief_notes'              => 'Notas do debriefing',
            'observed_critical_errors'   => 'Erros observados',
        ];
        $fieldRoot = strtok((string) $firstField, '.');
        $label     = $friendlyLabels[$fieldRoot] ?? $fieldRoot;
        $toastMsg  = $label ? "{$label}: {$firstError}" : $firstError;
    @endphp
    <script>
        document.addEventListener('alpine:initialized', () => {
            Alpine.store('ui').toast.show('error', @json($toastMsg));
        });
    </script>
@endif
