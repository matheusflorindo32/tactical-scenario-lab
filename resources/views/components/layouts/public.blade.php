@props([
    'title'       => 'Tactical Scenario Lab — Treinamento orientado por evidências',
    'description' => 'Plataforma institucional para planejar, executar, avaliar e debriefar treinamentos baseados em cenários.',
])

<!doctype html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b1a2c">
    <meta name="color-scheme" content="light">

    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&family=instrument-sans:500,600,700&family=jetbrains-mono:400,500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-full bg-stone-25 antialiased">
    <a
        href="#main"
        class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-3 focus:font-semibold focus:text-navy-900 focus:shadow-lg"
    >
        Ir para o conteúdo principal
    </a>

    <x-public-header />

    <main id="main">
        {{ $slot }}
    </main>

    <x-footer variant="public" />
    <x-toast />
</body>
</html>
