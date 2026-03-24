<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Peldarg Consulting Limited - Booklet Log</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/booklet-log.js'])
</head>
<body class="bg-slate-50 text-slate-900" style="font-family: 'Manrope', ui-sans-serif, system-ui, sans-serif;">
    <header class="bg-slate-950 text-white border-b border-white/10">
        <div class="max-w-6xl mx-auto px-4 py-4">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-3 min-w-0">
                    <img src="{{ asset('images/peldarg-logo.png') }}" alt="Peldarg Consulting" class="h-10 w-auto" />
                    <div>
                        <h1 class="m-0 text-lg font-semibold">Peldarg Consulting Limited</h1>
                        <p class="m-0 text-xs text-white/80">Welcome, {{ $userName ?? 'User' }}</p>
                    </div>
                </div>

                <div class="w-full md:w-auto flex items-center gap-2 flex-wrap md:justify-end">
                    <a href="{{ route('dashboard') }}" class="text-sm px-3 py-2 rounded-lg hover:bg-white/10">Dashboard</a>
                    <a href="{{ route('booklet.log') }}" class="text-sm px-3 py-2 rounded-lg bg-white/10">Booklet Log</a>
                    <a href="{{ route('how.to.use') }}" class="text-sm px-3 py-2 rounded-lg hover:bg-white/10">How To Use</a>
                    <a href="{{ route('topup') }}" class="text-sm px-3 py-2 rounded-lg hover:bg-white/10">Top up</a>
                    <a href="{{ route('payment.history') }}" class="text-sm px-3 py-2 rounded-lg hover:bg-white/10">Payment history</a>
                    <a href="{{ route('settings') }}" class="text-sm px-3 py-2 rounded-lg hover:bg-white/10">Settings</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm px-4 py-2 bg-amber-400 text-slate-950 rounded-lg hover:bg-amber-300 transition">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">Booklet Log</h2>
                    <p class="text-sm text-slate-600 mt-1">Track booklet uploads, successful PDF extractions, and extracted student rows by month/year.</p>
                </div>
                <form id="bookletLogFilterForm" class="flex items-end gap-3 flex-wrap">
                    <div>
                        <label for="filterYear" class="block text-sm font-medium text-slate-700 mb-1">Year</label>
                        <input id="filterYear" type="number" min="2000" max="2100" placeholder="e.g. 2026" class="rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-amber-400" />
                    </div>
                    <div>
                        <label for="filterMonth" class="block text-sm font-medium text-slate-700 mb-1">Month</label>
                        <select id="filterMonth" class="rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-amber-400">
                            <option value="">All months</option>
                            <option value="1">January</option>
                            <option value="2">February</option>
                            <option value="3">March</option>
                            <option value="4">April</option>
                            <option value="5">May</option>
                            <option value="6">June</option>
                            <option value="7">July</option>
                            <option value="8">August</option>
                            <option value="9">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                    </div>
                    <button type="submit" class="rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold hover:bg-slate-800 transition">Apply filter</button>
                    <button id="clearFilterBtn" type="button" class="rounded-lg border border-slate-300 text-slate-700 px-4 py-2 text-sm font-semibold hover:bg-slate-100 transition">Clear</button>
                </form>
            </div>

            <div id="bookletLogMsg" class="mt-3 text-sm text-slate-600"></div>
        </section>

        <section class="mt-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-slate-500">All-time uploads</div>
                <div id="overallUploads" class="mt-2 text-3xl font-bold">0</div>
            </article>
            <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-slate-500">All-time successful PDFs</div>
                <div id="overallSuccessful" class="mt-2 text-3xl font-bold">0</div>
            </article>
            <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-slate-500">All-time extracted student rows</div>
                <div id="overallRows" class="mt-2 text-3xl font-bold">0</div>
            </article>
            <article class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-amber-700">Filtered uploads</div>
                <div id="filteredUploads" class="mt-2 text-3xl font-bold text-amber-900">0</div>
            </article>
            <article class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-amber-700">Filtered successful PDFs</div>
                <div id="filteredSuccessful" class="mt-2 text-3xl font-bold text-amber-900">0</div>
            </article>
            <article class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                <div class="text-xs uppercase tracking-wide text-amber-700">Filtered extracted student rows</div>
                <div id="filteredRows" class="mt-2 text-3xl font-bold text-amber-900">0</div>
            </article>
        </section>

        <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <h3 class="text-lg font-semibold text-slate-900">Booklet extraction records</h3>
                <div class="text-sm text-slate-600">Each row shows extracted student rows from that PDF.</div>
            </div>
            <div class="mt-3 overflow-auto">
                <table class="w-full text-sm border-collapse" id="bookletLogTable">
                    <thead>
                        <tr class="bg-slate-50 text-slate-900">
                            <th class="text-left p-2 border-b">ID</th>
                            <th class="text-left p-2 border-b">Filename</th>
                            <th class="text-left p-2 border-b">Session</th>
                            <th class="text-left p-2 border-b">Status</th>
                            <th class="text-left p-2 border-b">Pages</th>
                            <th class="text-left p-2 border-b">Results</th>
                            <th class="text-left p-2 border-b">Students rows</th>
                            <th class="text-left p-2 border-b">API tier</th>
                            <th class="text-left p-2 border-b">Created</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
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
