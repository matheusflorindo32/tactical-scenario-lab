<header
    x-data="{ open: false, scrolled: false }"
    x-init="scrolled = window.scrollY > 12"
    x-on:scroll.window.throttle.100ms="scrolled = window.scrollY > 12"
    x-on:keydown.escape.window="if (open) { open = false; $nextTick(() => $refs.trigger.focus()) }"
    x-bind:class="scrolled ? 'public-header--scrolled' : ''"
    class="public-header"
>
    <div class="tsl-container flex min-h-18 items-center justify-between gap-3 py-3">
        <x-brand
            :href="route('home')"
            label="Tactical Scenario Lab — página inicial"
        />

        <nav class="hidden items-center gap-7 md:flex" aria-label="Navegação pública principal">
            <a href="#visao-geral" class="public-nav-link">Visão geral</a>
            <a href="#como-funciona" class="public-nav-link">Como funciona</a>
            <a href="#recursos" class="public-nav-link">Recursos</a>
        </nav>

        <div class="flex items-center gap-2">
            <a href="{{ route('login') }}" class="public-access-link hidden sm:inline-flex">Acessar</a>

            <button
                x-ref="trigger"
                type="button"
                class="public-menu-trigger md:hidden"
                x-bind:aria-expanded="open.toString()"
                aria-controls="public-navigation"
                aria-label="Abrir menu de navegação"
                x-bind:aria-label="open ? 'Fechar menu de navegação' : 'Abrir menu de navegação'"
                x-on:click="open = !open"
            >
                <svg x-show="!open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-5 w-5">
                    <path d="M4 7h16M4 12h16M4 17h16" />
                </svg>
                <svg x-show="open" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true" class="h-5 w-5">
                    <path d="m6 6 12 12M18 6 6 18" />
                </svg>
            </button>
        </div>
    </div>

    <nav
        id="public-navigation"
        x-show="open"
        x-cloak
        x-transition.origin.top
        x-on:click.outside="open = false"
        class="public-mobile-navigation md:hidden"
        aria-label="Navegação pública móvel"
    >
        <div class="tsl-container grid gap-1 py-3">
            <a href="#visao-geral" class="public-mobile-link" x-on:click="open = false">Visão geral</a>
            <a href="#como-funciona" class="public-mobile-link" x-on:click="open = false">Como funciona</a>
            <a href="#recursos" class="public-mobile-link" x-on:click="open = false">Recursos</a>
            <a href="{{ route('login') }}" class="public-mobile-link public-mobile-link--access sm:hidden">Acessar o ambiente</a>
        </div>
    </nav>
</header>
