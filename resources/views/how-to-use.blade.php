<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Peldarg Consulting Limited - How To Use</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-50 text-slate-900" style="font-family: 'Manrope', ui-sans-serif, system-ui, sans-serif;">
    <header class="bg-slate-950 text-white border-b border-white/10">
        <div class="max-w-6xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/peldarg-logo.png') }}" alt="Peldarg Consulting" class="h-10 w-auto" />
                    <div>
                        <h1 class="m-0 text-lg font-semibold">Peldarg Consulting Limited</h1>
                        <p class="m-0 text-xs text-white/80">Welcome, {{ $userName ?? 'User' }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap justify-end">
                    <a href="{{ route('dashboard') }}" class="text-sm px-3 py-2 rounded-lg hover:bg-white/10">Dashboard</a>
                    <a href="{{ route('how.to.use') }}" class="text-sm px-3 py-2 rounded-lg bg-white/10">How To Use</a>
                    <a href="{{ route('topup') }}" class="text-sm px-3 py-2 rounded-lg hover:bg-white/10">Top up</a>
                    <a href="{{ route('payment.history') }}" class="text-sm px-3 py-2 rounded-lg hover:bg-white/10">Payment history</a>
                    <a href="{{ route('settings') }}" class="text-sm px-3 py-2 rounded-lg hover:bg-white/10">Settings</a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm px-4 py-2 bg-amber-400 text-slate-950 rounded-lg hover:bg-amber-300 transition">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-8">
        <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800 text-white p-7 md:p-10 shadow-xl">
            <div class="pointer-events-none absolute inset-0 bg-slate-950/35 z-0"></div>
            <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-amber-300/20 blur-3xl z-0"></div>
            <div class="pointer-events-none absolute -left-20 -bottom-20 h-64 w-64 rounded-full bg-cyan-300/20 blur-3xl z-0"></div>

            <div class="relative z-10">
                <p class="text-xs uppercase tracking-[0.2em] text-amber-300 font-semibold">Convocation Booklet Guide</p>
                <h2 class="mt-3 text-3xl md:text-4xl font-extrabold tracking-tight">How To Use The Extraction Platform</h2>
                <p class="mt-4 text-sm md:text-base text-white/90 max-w-3xl leading-relaxed">
                    This guide helps you prepare files correctly, avoid upload failures, and keep extraction output clean before database import.
                    Follow the page limits and splitting rules below for consistent results.
                </p>

                <div class="mt-7 grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="rounded-xl border border-white/20 bg-white/10 px-4 py-3">
                        <div class="text-xs text-white/80">Daily recommendation</div>
                        <div class="text-2xl font-bold mt-1">5,000 pages</div>
                    </div>
                    <div class="rounded-xl border border-white/20 bg-white/10 px-4 py-3">
                        <div class="text-xs text-white/80">Three-column PDF max</div>
                        <div class="text-2xl font-bold mt-1">150 pages/upload</div>
                    </div>
                    <div class="rounded-xl border border-white/20 bg-white/10 px-4 py-3">
                        <div class="text-xs text-white/80">One-column PDF max</div>
                        <div class="text-2xl font-bold mt-1">200 pages/upload</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-7 grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-8 space-y-5">
                <article class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">
                    <div class="flex items-start gap-4">
                        <span class="h-9 w-9 shrink-0 rounded-full bg-slate-900 text-white text-sm font-bold grid place-items-center">1</span>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Identify the booklet format first</h3>
                            <p class="mt-2 text-sm text-slate-700 leading-relaxed">
                                We currently support two institution booklet styles:
                                <span class="font-semibold">student records in three columns per page</span> and
                                <span class="font-semibold">student records in one column per page</span>.
                                Confirm the format before uploading.
                            </p>
                        </div>
                    </div>
                </article>

                <article class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">
                    <div class="flex items-start gap-4">
                        <span class="h-9 w-9 shrink-0 rounded-full bg-slate-900 text-white text-sm font-bold grid place-items-center">2</span>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Apply the correct upload limits</h3>
                            <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                <div class="rounded-xl border border-amber-300/60 bg-amber-50 p-3">
                                    <p class="font-semibold text-amber-900">Three-column records</p>
                                    <p class="text-amber-800 mt-1">Maximum 150 pages per upload.</p>
                                </div>
                                <div class="rounded-xl border border-cyan-300/60 bg-cyan-50 p-3">
                                    <p class="font-semibold text-cyan-900">One-column records</p>
                                    <p class="text-cyan-800 mt-1">Maximum 200 pages per upload.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">
                    <div class="flex items-start gap-4">
                        <span class="h-9 w-9 shrink-0 rounded-full bg-slate-900 text-white text-sm font-bold grid place-items-center">3</span>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Split oversized files before upload</h3>
                            <p class="mt-2 text-sm text-slate-700 leading-relaxed">
                                If a file exceeds its format limit, split it by faculty where possible.
                                Example: if a three-column file has 180 pages, find a faculty endpoint at or below page 150,
                                upload that part first, then upload the remaining pages as a second file.
                            </p>
                            <p class="mt-3 text-sm text-slate-700 leading-relaxed">
                                If the booklet has only one faculty, split evenly (for example 180 into 90 + 90),
                                upload each part separately, and carefully merge the resulting Excel files afterward.
                            </p>
                        </div>
                    </div>
                </article>

                <article class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">
                    <div class="flex items-start gap-4">
                        <span class="h-9 w-9 shrink-0 rounded-full bg-slate-900 text-white text-sm font-bold grid place-items-center">4</span>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Do not mix column styles in one upload batch</h3>
                            <p class="mt-2 text-sm text-slate-700 leading-relaxed">
                                Keep three-column and one-column PDFs separate even when they come from the same school.
                                This is common in Covenant University convocation booklets.
                            </p>
                        </div>
                    </div>
                </article>
            </div>

            <aside class="lg:col-span-4 space-y-5">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-base font-bold text-slate-900">Operational recommendations</h3>
                    <p class="mt-2 text-sm text-slate-700 leading-relaxed">
                        Use a daily cap of <span class="font-semibold">5,000 pages</span> for stable operation.
                        Paid API tiers can handle higher volume when needed.
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-base font-bold text-slate-900">Quality assurance is mandatory</h3>
                    <p class="mt-2 text-sm text-slate-700 leading-relaxed">
                        Even when extraction output appears correct, always perform due diligence on the final Excel file
                        before uploading records to your database.
                    </p>
                </div>

                <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
                    <h3 class="text-base font-bold text-rose-900">Need support?</h3>
                    <p class="mt-2 text-sm text-rose-800 leading-relaxed">
                        Report unusual behavior, extraction errors, or support requests to
                        <a class="font-semibold underline decoration-2 underline-offset-2" href="mailto:contact@peldargconsulting.com">contact@peldargconsulting.com</a>.
                    </p>
                </div>
            </aside>
        </section>
    </main>

    <footer class="text-center text-slate-700 py-6">
        <div class="max-w-6xl mx-auto px-4">
            <small>© <span id="year"></span> Peldarg Consulting Limited</small>
        </div>
    </footer>

    <script>
        document.getElementById('year').textContent = new Date().getFullYear();
    </script>
</body>
</html>
