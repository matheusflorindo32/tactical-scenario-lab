<!doctype html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b1a2c">

    <title>
        @yield('title', 'Tactical Scenario Lab — Simulação clínica para instrutores de APH')
    </title>

    <meta
        name="description"
        content="@yield('description', 'Ferramenta educacional para instrutores gerarem, executarem e debriefarem cenários de atendimento pré-hospitalar em minutos.')"
    >

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>

    <link
        href="https://fonts.bunny.net/css?family=inter:400,500,600,700&family=instrument-sans:500,600,700&family=jetbrains-mono:400,500&display=swap"
        rel="stylesheet"
    >

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-full bg-stone-25 antialiased">
    <a
        href="#main"
        class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-3 focus:font-semibold focus:text-navy-900 focus:shadow-lg"
    >
        Ir para o conteúdo principal
    </a>

    <header class="sticky top-0 z-30 border-b border-stone-200 bg-white/85 backdrop-blur-md">
        <div class="tsl-container flex h-16 items-center justify-between">
            <a href="{{ route('home') }}" aria-label="Página inicial">
                <x-brand />
            </a>

            <nav
                class="hidden items-center gap-8 text-sm text-ink-700 md:flex"
                aria-label="Seções principais"
            >
                <a
                    href="{{ route('home') }}#produto"
                    class="transition hover:text-navy-900"
                >
                    Produto
                </a>

                <a
                    href="{{ route('home') }}#como-funciona"
                    class="transition hover:text-navy-900"
                >
                    Como funciona
                </a>

                <a
                    href="{{ route('home') }}#publico"
                    class="transition hover:text-navy-900"
                >
                    Público
                </a>

                <a
                    href="{{ route('home') }}#funcionalidades"
                    class="transition hover:text-navy-900"
                >
                    Funcionalidades
                </a>
            </nav>

            <div class="flex items-center gap-2">
                <x-button
                    href="{{ route('scenarios.index') }}"
                    variant="ghost"
                    size="sm"
                >
                    Painel
                </x-button>

                <x-button
                    href="{{ route('scenarios.create') }}"
                    size="sm"
                >
                    Começar
                </x-button>
            </div>
        </div>
    </header>

    <main id="main">
        @yield('content')
    </main>

    <x-footer variant="public" />
    <x-toast />
</body>
</html>
