@props(['code' => 'ERR', 'title' => 'Algo deu errado', 'description' => null])

<!doctype html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} · Tactical Scenario Lab</title>
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&family=instrument-sans:500,600,700&family=jetbrains-mono:400,500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-full bg-stone-25 antialiased">
    <div class="flex min-h-screen flex-col">
        <header class="border-b border-stone-200 bg-white">
            <div class="tsl-container flex h-16 items-center">
                <x-brand />
            </div>
        </header>

        <main class="flex flex-1 items-center">
            <div class="tsl-container">
                <div class="mx-auto max-w-xl text-center">
                    <span class="inline-flex items-center gap-2 rounded-full bg-navy-900 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-white">
                        <span class="h-1.5 w-1.5 rounded-full bg-emergency-500"></span>
                        Código {{ $code }}
                    </span>
                    <h1 class="mt-6 font-display text-4xl font-semibold tracking-tight text-navy-950 sm:text-5xl">{{ $title }}</h1>
                    @if ($description)
                        <p class="mt-4 text-base text-ink-700">{{ $description }}</p>
                    @endif

                    @if ($slot->isNotEmpty())
                        <div class="mt-6 text-left">{{ $slot }}</div>
                    @endif

                    <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <x-button href="{{ url('/') }}">Voltar ao início</x-button>
                        <x-button href="{{ route('scenarios.index') }}" variant="secondary">Ir para o painel</x-button>
                    </div>
                </div>
            </div>
        </main>

        <footer class="border-t border-stone-200 bg-white py-4">
            <div class="tsl-container text-center text-xs text-ink-500">
                Tactical Scenario Lab · Ferramenta educacional · <a href="https://github.com/matheusflorindo32/tactical-scenario-lab" class="underline underline-offset-2 hover:text-navy-900">Reportar problema</a>
            </div>
        </footer>
    </div>
</body>
</html>
