@props(['variant' => 'public']) {{-- public | app --}}

@php
$muted = $variant === 'public' ? 'text-navy-200' : 'text-ink-500';
$bg    = $variant === 'public' ? 'bg-navy-950 text-white' : 'bg-white border-t border-stone-200 text-ink-700';
@endphp

<footer class="{{ $bg }}">
    <div class="tsl-container py-10">
        <div class="grid gap-8 md:grid-cols-4">
            <div class="md:col-span-2">
                <x-brand :inverse="$variant === 'public'" />
                <p class="mt-4 max-w-sm text-sm {{ $muted }}">
                    Plataforma educacional para instrutores de APH e treinamento operacional configurarem, executarem e debriefarem cenários de forma padronizada.
                </p>
            </div>

            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] {{ $variant === 'public' ? 'text-white' : 'text-navy-900' }}">Produto</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('scenarios.index') }}" class="{{ $muted }} hover:{{ $variant === 'public' ? 'text-white' : 'text-navy-900' }}">Cenários</a></li>
                    <li><a href="{{ route('scenarios.create') }}" class="{{ $muted }} hover:{{ $variant === 'public' ? 'text-white' : 'text-navy-900' }}">Criar cenário</a></li>
                    <li><a href="{{ url('/health') }}" class="{{ $muted }} hover:{{ $variant === 'public' ? 'text-white' : 'text-navy-900' }}">Status</a></li>
                </ul>
            </div>

            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] {{ $variant === 'public' ? 'text-white' : 'text-navy-900' }}">Recursos</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="https://github.com/matheusflorindo32/tactical-scenario-lab" class="{{ $muted }} hover:{{ $variant === 'public' ? 'text-white' : 'text-navy-900' }}">GitHub</a></li>
                    <li><a href="https://github.com/matheusflorindo32/tactical-scenario-lab/blob/main/docs/PRODUCT.md" class="{{ $muted }} hover:{{ $variant === 'public' ? 'text-white' : 'text-navy-900' }}">Documento do produto</a></li>
                    <li><a href="https://github.com/matheusflorindo32/tactical-scenario-lab/blob/main/docs/DESIGN_SYSTEM.md" class="{{ $muted }} hover:{{ $variant === 'public' ? 'text-white' : 'text-navy-900' }}">Design system</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-10 flex flex-col items-start justify-between gap-4 border-t {{ $variant === 'public' ? 'border-navy-800' : 'border-stone-200' }} pt-6 text-xs md:flex-row md:items-center">
            <p class="{{ $muted }}">© {{ now()->year }} Tactical Scenario Lab · Ferramenta educacional. Não substitui protocolos institucionais nem decisão clínica.</p>
            <p class="{{ $muted }}">Licença MIT · v{{ app()->version() }} (Laravel)</p>
        </div>
    </div>
</footer>
