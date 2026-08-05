@props(['current' => null])

<header class="sticky top-0 z-30 border-b border-stone-200 bg-white/85 backdrop-blur-md">
    <div class="tsl-container flex h-16 items-center justify-between gap-6">
        <div class="flex items-center gap-6">
            <button
                type="button"
                x-on:click="$dispatch('toggle-sidebar')"
                class="rounded-md p-2 text-ink-700 hover:bg-stone-100 lg:hidden"
                aria-label="Abrir menu"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <x-brand />
        </div>

        <nav class="hidden items-center gap-1 md:flex" aria-label="Principal">
            @php
            $links = [
                ['label' => 'Painel',    'route' => 'scenarios.index',  'key' => 'dashboard'],
                ['label' => 'Cenários',  'route' => 'scenarios.index',  'key' => 'scenarios'],
            ];
            @endphp
            @foreach ($links as $link)
                @php $active = $current === $link['key']; @endphp
                <a
                    href="{{ route($link['route']) }}"
                    class="rounded-md px-3 py-2 text-sm font-medium transition-colors {{ $active ? 'bg-navy-50 text-navy-900' : 'text-ink-700 hover:bg-stone-100 hover:text-navy-900' }}"
                    @if($active) aria-current="page" @endif
                >
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2">
            <x-button href="{{ route('scenarios.create') }}" size="sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-3.5 w-3.5"><path d="M12 5v14M5 12h14"/></svg>
                Novo cenário
            </x-button>

            <x-dropdown>
                <x-slot:trigger>
                    <button class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-navy-100 text-sm font-semibold text-navy-900 ring-1 ring-inset ring-navy-200 hover:bg-navy-200" aria-label="Conta">
                        IN
                    </button>
                </x-slot:trigger>
                <x-slot:content>
                    <div class="px-3 py-2 border-b border-stone-100">
                        <p class="text-xs font-semibold text-navy-900">Instrutor</p>
                        <p class="text-[11px] text-ink-500">Sessão local · MVP</p>
                    </div>
                    <a href="#" class="block px-3 py-2 text-ink-700 hover:bg-stone-50">Preferências</a>
                    <a href="{{ url('/health') }}" target="_blank" rel="noopener" class="block px-3 py-2 text-ink-700 hover:bg-stone-50">Status</a>
                </x-slot:content>
            </x-dropdown>
        </div>
    </div>
</header>
