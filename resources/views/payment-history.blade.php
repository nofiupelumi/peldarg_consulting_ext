<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Peldarg Consulting Limited - Payment History</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css','resources/js/convocation.js'])
</head>
<body class="bg-slate-50 text-slate-900 font-sans">
    <header class="bg-slate-950 text-white border-b border-white/10">
        <div class="max-w-5xl mx-auto px-4 py-4">
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
                    <a href="{{ route('how.to.use') }}" class="text-sm px-3 py-2 rounded-lg hover:bg-white/10">How To Use</a>
                    <a href="{{ route('topup') }}" class="text-sm px-3 py-2 rounded-lg hover:bg-white/10">Top up</a>
                    <a href="{{ route('payment.history') }}" class="text-sm px-3 py-2 rounded-lg bg-white/10">Payment history</a>
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

    <main class="max-w-5xl mx-auto px-4 py-6">
        <section class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm mb-6">
            <h2 class="text-xl font-semibold mb-3">Top-up invoices</h2>
            <div class="overflow-auto">
                <table class="w-full text-sm border-collapse" id="invoicesTable">
                    <thead>
                        <tr class="bg-gray-50 text-gray-900">
                            <th class="text-left p-2 border-b">Invoice</th>
                            <th class="text-left p-2 border-b">Credits</th>
                            <th class="text-left p-2 border-b">Amount (USD)</th>
                            <th class="text-left p-2 border-b">Status</th>
                            <th class="text-left p-2 border-b">Created</th>
                            <th class="text-left p-2 border-b">Admin note</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div id="invoicesMsg" class="text-sm text-gray-600 mt-2"></div>
        </section>

        <section class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm mb-6">
            <h2 class="text-xl font-semibold mb-3">Credit ledger</h2>
            <div class="overflow-auto">
                <table class="w-full text-sm border-collapse" id="userLedgerTable">
                    <thead>
                        <tr class="bg-gray-50 text-gray-900">
                            <th class="text-left p-2 border-b">Type</th>
                            <th class="text-left p-2 border-b">Credits</th>
                            <th class="text-left p-2 border-b">Before</th>
                            <th class="text-left p-2 border-b">After</th>
                            <th class="text-left p-2 border-b">Doc</th>
                            <th class="text-left p-2 border-b">Invoice</th>
                            <th class="text-left p-2 border-b">Created</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div id="ledgerMsg" class="text-sm text-gray-600 mt-2"></div>
        </section>
    </main>

    <footer class="text-center text-slate-700 py-6">
        <div class="max-w-5xl mx-auto px-4">
            <small>© <span id="year"></span> Peldarg Consulting Limited</small>
        </div>
    </footer>
</body>
</html>
