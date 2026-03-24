<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Peldarg Consulting Limited - Convocation Extractor</title>
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
                    <p class="m-0 text-sm text-white/90">Welcome, {{ $userName ?? 'User' }}</p>
                </div>

                <div class="flex items-center gap-2 flex-wrap justify-end">
                    <a href="{{ route('dashboard') }}" class="text-sm px-3 py-2 rounded-lg bg-white/10">Dashboard</a>
                    <a href="{{ route('how.to.use') }}" class="text-sm px-3 py-2 rounded-lg hover:bg-white/10">How To Use</a>
                    @if ((bool) session('is_admin'))
                        <a href="{{ route('admin.console') }}" class="text-sm px-3 py-2 rounded-lg hover:bg-white/10">Admin</a>
                    @endif
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

    <main class="max-w-5xl mx-auto px-4 py-6">
        <section class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="rounded-xl p-5 border border-white/10 bg-slate-950 text-white shadow-sm">
                <h2 class="text-sm font-semibold text-white/80">Credit Balance</h2>
                <div class="mt-2 flex items-end gap-2">
                    <div id="creditBalanceValue" class="text-4xl font-bold leading-none">{{ (int) ($creditBalance ?? 0) }}</div>
                    <div class="pb-1 text-sm text-white/70">credits</div>
                </div>
                <div class="mt-2 text-sm text-white/70">
                    Cap: <span id="creditCapValue">{{ (int) ($creditCap ?? 0) > 0 ? (int) $creditCap : 'No cap' }}</span>
                </div>
                <div class="mt-3 text-xs text-white/60">
                    Unit price: $<span id="unitPriceUsd">{{ $unitPriceUsd ?? '' }}</span> / credit
                </div>
                <div class="mt-4">
                    <a href="{{ route('topup') }}" class="inline-flex items-center justify-center text-sm px-4 py-2 bg-amber-400 text-slate-950 rounded-lg hover:bg-amber-300 transition">
                        Top up
                    </a>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-gray-700">Booklets uploaded</h2>
                <div class="mt-2">
                    <div class="text-3xl font-bold leading-none">{{ (int) ($uploadsToday ?? 0) }}</div>
                    <div class="text-sm text-gray-600 mt-1">Today</div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="text-2xl font-bold leading-none">{{ (int) ($uploadsThisMonth ?? 0) }}</div>
                    <div class="text-sm text-gray-600 mt-1">This month</div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-gray-700">PDFs successfully extracted</h2>
                <div class="mt-2">
                    <div class="text-3xl font-bold leading-none">{{ (int) ($successfulExtractsTotal ?? 0) }}</div>
                    <div class="text-sm text-gray-600 mt-1">Total</div>
                </div>
            </div>
        </section>

        <section class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm mb-6">
            <div class="mb-4 flex items-center justify-between gap-3 flex-wrap">
                <h2 class="text-xl font-semibold">Upload Convocation PDF</h2>
                <a href="{{ route('how.to.use') }}" class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    How To Use
                </a>
            </div>
            <form id="uploadForm" class="space-y-3" method="POST" action="javascript:void(0);" onsubmit="return false;" data-credit-balance="{{ (int) ($creditBalance ?? 0) }}">
                @csrf
                <div class="flex flex-col gap-2">
                    <label for="file" class="font-medium">PDF File</label>
                    <input id="file" name="file" type="file" accept="application/pdf" required class="rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-amber-400" />
                    <small class="text-gray-500 text-xs">Max upload size: {{ (int) ($maxUploadMb ?? 0) }}MB (PDF only)</small>
                </div>
                <div class="flex flex-col gap-2">
                    <label for="session" class="font-medium">Session (optional)</label>
                    <input id="session" name="session" type="text" placeholder="e.g. 2021/2022" class="rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-amber-400" />
                    <small class="text-gray-500 text-xs">If not provided, will be auto-detected from PDF</small>
                </div>
                <div class="flex flex-col gap-2">
                    <label for="api_tier" class="font-medium">API Key Tier</label>
                    <select id="api_tier" name="api_tier" class="rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-amber-400" required>
                        @foreach(($availableApiTiers ?? ['paid_1']) as $tier)
                            <option value="{{ $tier }}" {{ ($defaultApiTier ?? 'paid_1') === $tier ? 'selected' : '' }}>
                                {{ strtoupper(str_replace('_', ' ', $tier)) }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-gray-500 text-xs">Available tiers are assigned by admin.</small>
                </div>
                <div class="flex gap-3">
                    <div class="flex-1 flex flex-col gap-2">
                        <label for="page_start" class="font-medium">Start Page (optional)</label>
                        <input id="page_start" name="page_start" type="number" min="1" placeholder="e.g. 1" class="rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-amber-400" />
                    </div>
                    <div class="flex-1 flex flex-col gap-2">
                        <label for="page_end" class="font-medium">End Page (optional)</label>
                        <input id="page_end" name="page_end" type="number" min="1" placeholder="e.g. 10" class="rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-amber-400" />
                    </div>
                </div>
                <div id="pageValidationError" class="text-red-600 text-sm hidden">End page must be greater than or equal to start page</div>
                <div id="creditGateMsg" class="text-sm text-gray-700 hidden"></div>
                
                <!-- Progress bar -->
                <div id="uploadProgress" class="hidden">
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div id="progressBar" class="bg-amber-400 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <p id="progressText" class="text-sm text-gray-600 mt-2 text-center">Uploading...</p>
                </div>
                
                <button id="uploadBtn" type="submit" disabled class="w-full py-3 bg-amber-400 text-slate-950 font-semibold rounded-lg hover:bg-amber-300 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    Upload and Extract
                </button>
            </form>
            <div id="uploadMsg" class="mt-3 text-sm"></div>
        </section>

        <section class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm mb-6">
            <h2 class="text-xl font-semibold mb-3">Audit &amp; History (PDF uploads)</h2>
            <div class="overflow-auto">
                <table class="w-full text-sm border-collapse" id="docsTable">
                    <thead>
                        <tr class="bg-gray-50 text-gray-900">
                            <th class="text-left p-2 border-b">ID</th>
                            <th class="text-left p-2 border-b">Filename</th>
                            <th class="text-left p-2 border-b">Session</th>
                            <th class="text-left p-2 border-b">Status</th>
                            <th class="text-left p-2 border-b">Pages</th>
                            <th class="text-left p-2 border-b">Results</th>
                            <th class="text-left p-2 border-b">Credits</th>
                            <th class="text-left p-2 border-b">Credit status</th>
                            <th class="text-left p-2 border-b">CSV</th>
                            <th class="text-left p-2 border-b">XLSX</th>
                            <th class="text-left p-2 border-b">Created</th>
                            <th class="text-left p-2 border-b">Failure reason</th>
                            <th class="text-left p-2 border-b">API tier</th>
                            <th class="text-left p-2 border-b">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </section>
    </main>

    <footer class="text-center text-slate-700 py-6">
        <div class="max-w-5xl mx-auto px-4">
            <small>© <span id="year"></span> Peldarg Consulting Limited</small>
        </div>
    </footer>

    <script>
        document.getElementById('year').textContent = new Date().getFullYear();
    </script>
</body>
</html>
