<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Peldarg Extraction Platform') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>
                :root { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
                body { margin: 0; min-height: 100vh; }
                a { color: inherit; text-decoration: none; }
            </style>
        @endif
    </head>
    <body class="min-h-screen bg-slate-950 text-white">
        <header class="border-b border-white/10">
            <div class="mx-auto max-w-6xl px-6 py-4 flex items-center justify-between">
                <a href="{{ url('/') }}" class="flex items-center gap-3">
                    <img src="{{ asset('images/peldarg-logo.png') }}" alt="Peldarg Consulting" class="h-10 w-auto" />
                </a>

                @if (Route::has('login'))
                    @auth
                        <a
                            href="{{ url('/dashboard') }}"
                            class="inline-flex items-center rounded-md px-4 py-2 text-sm font-semibold border border-white/20 hover:border-white/30"
                        >
                            Dashboard
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="inline-flex items-center rounded-md px-4 py-2 text-sm font-semibold bg-amber-400 text-slate-950 hover:bg-amber-300 transition"
                        >
                            Log in
                        </a>
                    @endauth
                @endif
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-6 py-16">
            <h1 class="text-4xl md:text-5xl font-bold tracking-tight">
                Secure extraction workflows.
            </h1>
            <p class="mt-4 max-w-2xl text-white/80">
                The Extraction Platform provides credit-controlled access to document extraction, with a secured CI integration for automated pipelines.
            </p>

            <div class="mt-10 grid gap-6 md:grid-cols-3">
                <div class="rounded-lg bg-white/5 border border-white/10 p-6">
                    <h2 class="text-lg font-semibold">Credit-controlled usage</h2>
                    <p class="mt-2 text-sm text-white/75">Integer credits, strict caps, and a transparent ledger for auditing.</p>
                </div>
                <div class="rounded-lg bg-white/5 border border-white/10 p-6">
                    <h2 class="text-lg font-semibold">Secured pipeline callbacks</h2>
                    <p class="mt-2 text-sm text-white/75">Token authentication plus HMAC signatures for CI callbacks.</p>
                </div>
            </div>

            @if (Route::has('login'))
                <div class="mt-12">
                    <a
                        href="{{ route('login') }}"
                        class="inline-flex items-center rounded-md px-5 py-3 font-semibold bg-amber-400 text-slate-950 hover:bg-amber-300 transition"
                    >
                        Log in to your account
                    </a>
                </div>
            @endif
        </main>

        <footer class="border-t border-white/10">
            <div class="mx-auto max-w-6xl px-6 py-6 text-sm text-white/70 flex items-center justify-between">
                <span>© {{ date('Y') }} Peldarg Consulting</span>
                <span>Extraction Platform</span>
            </div>
        </footer>
    </body>
</html>
