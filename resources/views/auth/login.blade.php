<!doctype html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b1a2c">
    <title>Acesso institucional · Tactical Medicine Academy</title>
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&family=instrument-sans:500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-stone-25 antialiased">
    <main class="grid min-h-screen lg:grid-cols-[minmax(0,1.1fr)_minmax(420px,0.9fr)]">
        <section class="hidden bg-navy-950 p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-alert-300">Tactical Medicine Academy</p>
                <h1 class="mt-6 max-w-2xl font-display text-5xl font-semibold leading-tight">Controle institucional para treinamento, pessoas e cenários.</h1>
                <p class="mt-6 max-w-xl text-base leading-7 text-white/70">Acesso individual, rastreável e preparado para permissões por organização.</p>
            </div>
            <p class="text-sm text-white/50">Ambiente protegido · dados pessoais exigem autorização institucional.</p>
        </section>

        <section class="flex items-center justify-center p-6 sm:p-10">
            <div class="w-full max-w-md">
                <div class="rounded-3xl border border-ink-200 bg-white p-7 shadow-sm sm:p-9">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emergency-700">Acesso seguro</p>
                    <h2 class="mt-3 font-display text-3xl font-semibold tracking-tight text-navy-950">Entrar no sistema</h2>
                    <p class="mt-3 text-sm leading-6 text-ink-500">Use a conta institucional fornecida pelo administrador.</p>

                    @if (session('success'))
                        <div class="mt-5 rounded-xl border border-clinical-200 bg-clinical-50 px-4 py-3 text-sm text-clinical-800">{{ session('success') }}</div>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}" class="mt-7 space-y-5">
                        @csrf
                        <label class="block">
                            <span class="text-sm font-semibold text-navy-950">E-mail</span>
                            <input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                            @error('email') <span class="mt-2 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-semibold text-navy-950">Senha</span>
                            <input type="password" name="password" autocomplete="current-password" required class="mt-2 w-full rounded-xl border border-ink-300 px-4 py-3 text-sm outline-none focus:border-navy-700 focus:ring-4 focus:ring-navy-100">
                            @error('password') <span class="mt-2 block text-sm text-emergency-700">{{ $message }}</span> @enderror
                        </label>

                        <label class="flex items-center gap-3 text-sm text-ink-600">
                            <input type="checkbox" name="remember" value="1" class="rounded border-ink-300 text-navy-900 focus:ring-navy-500">
                            Manter sessão neste dispositivo
                        </label>

                        <button type="submit" class="w-full rounded-xl bg-navy-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-navy-800 focus:outline-none focus:ring-4 focus:ring-navy-200">Entrar com segurança</button>
                    </form>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
