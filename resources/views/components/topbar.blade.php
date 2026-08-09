@props(['current' => null])

@php
$user = auth()->user();
$activeOrganizationId = session('active_organization_id');
$activeAccess = $user?->activeOrganizationAccesses()
    ->with('organization')
    ->where('organization_id', $activeOrganizationId)
    ->first();
$activeOrganization = $activeAccess?->organization;
$abilities = $activeAccess?->abilities ?? [];
$canManageScenarios = in_array(\App\Support\Auth\AccessAbility::SCENARIOS_MANAGE, $abilities, true);
@endphp

<header class="sticky top-0 z-30 border-b border-stone-200 bg-white/90 backdrop-blur-md">
    <div class="tsl-container flex h-16 items-center justify-between gap-4">
        <div class="flex min-w-0 items-center gap-3 sm:gap-5">
            <button
                type="button"
                x-on:click="$dispatch('toggle-sidebar')"
                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-md text-ink-700 hover:bg-stone-100 lg:hidden"
                aria-label="Abrir menu"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <x-brand />

            @if ($activeOrganization)
                <div class="hidden min-w-0 border-l border-stone-200 pl-5 md:block">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-ink-500">Organização ativa</p>
                    <p class="max-w-64 truncate text-sm font-semibold text-navy-950" title="{{ $activeOrganization->name }}">
                        {{ $activeOrganization->name }}
                    </p>
                </div>
            @endif
        </div>

        <div class="flex shrink-0 items-center gap-2">
            @if ($canManageScenarios)
                <x-button href="{{ route('scenarios.create') }}" size="sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-3.5 w-3.5"><path d="M12 5v14M5 12h14"/></svg>
                    <span class="hidden sm:inline">Novo cenário</span>
                    <span class="sm:hidden">Novo</span>
                </x-button>
            @endif

            <x-dropdown width="64">
                <x-slot:trigger>
                    <button class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-navy-100 text-sm font-semibold text-navy-900 ring-1 ring-inset ring-navy-200 hover:bg-navy-200" aria-label="Abrir menu da conta">
                        {{ strtoupper(substr($user?->name ?: 'IN', 0, 2)) }}
                    </button>
                </x-slot:trigger>
                <x-slot:content>
                    <div class="border-b border-stone-100 px-3 py-3">
                        <p class="truncate text-xs font-semibold text-navy-900">{{ $user?->name ?: 'Instrutor' }}</p>
                        @if ($activeAccess)
                            <p class="mt-0.5 truncate text-[11px] text-ink-500">{{ $activeAccess->role }} · {{ $activeOrganization?->name }}</p>
                        @endif
                    </div>
                    <a href="{{ url('/health') }}" target="_blank" rel="noopener" class="block px-3 py-2.5 text-ink-700 hover:bg-stone-50" role="menuitem">Status da aplicação</a>
                    <form method="POST" action="{{ route('logout') }}" class="border-t border-stone-100 p-2">
                        @csrf
                        <button type="submit" class="flex min-h-10 w-full items-center rounded-md px-2 text-left text-sm font-medium text-ink-700 hover:bg-stone-50 hover:text-navy-950" role="menuitem">
                            Encerrar sessão
                        </button>
                    </form>
                </x-slot:content>
            </x-dropdown>
        </div>
    </div>
</header>
