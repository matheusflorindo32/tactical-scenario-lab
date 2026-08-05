<!doctype html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b1a2c">

    <title>{{ $title ?? 'Tactical Scenario Lab — Simulação clínica para instrutores de APH' }}</title>
    <meta name="description" content="Ferramenta educacional para instrutores gerarem, executarem e debriefarem cenários de atendimento pré-hospitalar em minutos.">

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&family=instrument-sans:500,600,700&family=jetbrains-mono:400,500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-stone-25 antialiased">
    <header class="sticky top-0 z-30 border-b border-stone-200 bg-white/85 backdrop-blur-md">
        <div class="tsl-container flex h-16 items-center justify-between">
            <x-brand />
            <nav class="hidden items-center gap-8 text-sm text-ink-700 md:flex" aria-label="Seções">
                <a href="#produto" class="hover:text-navy-900">Produto</a>
                <a href="#como-funciona" class="hover:text-navy-900">Como funciona</a>
                <a href="#publico" class="hover:text-navy-900">Público</a>
                <a href="#funcionalidades" class="hover:text-navy-900">Funcionalidades</a>
            </nav>
            <div class="flex items-center gap-2">
                <x-button href="{{ route('scenarios.index') }}" variant="ghost" size="sm">Painel</x-button>
                <x-button href="{{ route('scenarios.create') }}" size="sm">Começar</x-button>
            </div>
        </div>
    </header>

    <main id="main">
        {{ $slot }}
    </main>

    <x-footer variant="public" />
    <x-toast />
</body>
</html>
