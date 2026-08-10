<!doctype html>
<html lang="pt-BR" class="h-full" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b1a2c">
    <meta name="color-scheme" content="light dark">

    <title>@yield('title', 'Tactical Scenario Lab')</title>

    <meta
        name="description"
        content="@yield('description', 'Plataforma educacional para instrutores de APH configurarem, executarem e debriefarem cenários de treinamento.')"
    >

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>

    <link
        href="https://fonts.bunny.net/css?family=inter:400,500,600,700&family=instrument-sans:500,600,700&family=jetbrains-mono:400,500&display=swap"
        rel="stylesheet"
    >

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body x-data class="min-h-full bg-stone-25 antialiased">
    <a
        href="#main"
        class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-navy-900 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white"
    >
        Pular para o conteúdo
    </a>

    <x-topbar :current="$current ?? null" />

    <div class="tsl-container flex gap-8">
        <x-sidebar :current="$current ?? null" />

        <main
            id="main"
            class="min-h-[calc(100vh-4rem)] flex-1 py-8"
        >
            @hasSection('breadcrumbs')
                <div class="mb-4">
                    @yield('breadcrumbs')
                </div>
            @endif

            @hasSection('header')
                <header class="mb-8">
                    @yield('header')
                </header>
            @endif

            @yield('content')
        </main>
    </div>

    <x-toast />
</body>
</html>
