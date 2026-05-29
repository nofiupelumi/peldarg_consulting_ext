<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Peldarg Consulting Limited - Top Up</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css','resources/js/convocation.js'])
</head>
<body class="bg-slate-50 text-slate-900 font-sans">
    <header class="bg-slate-950 text-white border-b border-white/10">
        <div class="max-w-5xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <p class="m-0 text-sm text-white/90">Welcome, {{ $userName ?? 'User' }}</p>
                </div>

                <div class="flex items-center gap-2 flex-nowrap overflow-x-auto">
                    <a href="{{ route('dashboard') }}" class="text-sm px-3 py-2 rounded-lg hover:bg-white/10">Dashboard</a>
                    <a href="{{ route('booklet.log') }}" class="text-sm px-3 py-2 rounded-lg hover:bg-white/10">Booklet Log</a>
                    <a href="{{ route('how.to.use') }}" class="text-sm px-3 py-2 rounded-lg hover:bg-white/10">How To Use</a>
                    <a href="{{ route('topup') }}" class="text-sm px-3 py-2 rounded-lg bg-white/10">Top up</a>
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
                <div class="mt-1 text-xs text-white/60">
                    FX: <span id="fxRateNgn">{{ $fxRateNgn ?? '' }}</span> NGN / USD
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                <h2 class="text-xl font-semibold">Paystack</h2>
                <p class="text-sm text-gray-600 mt-1">Pay online and credits will be added automatically after verification.</p>
                <button id="paystackBtn" type="button" class="mt-3 w-full py-3 bg-slate-950 text-white font-semibold rounded-lg hover:bg-slate-800 transition">
                    Pay with Paystack
                </button>
                <p class="mt-2 text-xs text-gray-500">Use the requested credits field below, then click this button to open secure checkout.</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                <h2 class="text-xl font-semibold">Current invoice</h2>
                @if (!empty($currentInvoice))
                    <div class="mt-2 text-sm text-gray-700">
                        <div><span class="font-medium">Invoice:</span> {{ $currentInvoice->invoice_number }}</div>
                        <div><span class="font-medium">Credits:</span> {{ (int) $currentInvoice->requested_credits }}</div>
                        <div><span class="font-medium">Amount (USD):</span> {{ $currentInvoice->requested_amount_usd }}</div>
                        <div><span class="font-medium">Status:</span> {{ $currentInvoice->status }}</div>
                        <div class="text-xs text-gray-500 mt-1">Created: {{ $currentInvoice->created_at }}</div>
                    </div>
                @else
                    <p class="text-sm text-gray-600 mt-1">No pending invoice.</p>
                @endif
            </div>
        </section>

        <section class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm mb-6">
            <div class="flex items-start justify-between gap-3 flex-wrap">
                <div>
                    <h2 class="text-xl font-semibold">Top up credits</h2>
                    <p class="text-sm text-gray-600 mt-1">Payments are accepted in NGN. Notice: 1 USD = <span class="font-medium">{{ $fxRateNgn ?? '' }}</span> NGN (admin-configurable).</p>
                </div>
            </div>

            <div class="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-700">
                <div class="font-medium text-gray-900">Bank transfer details</div>
                <div class="mt-1">Account Name: <span class="font-medium">Peldarg Consulting Limited</span></div>
                <div>Bank: <span class="font-medium">Moniepoint Bank</span></div>
                <div>Account Number: <span class="font-medium">8107837073</span></div>
            </div>

            <form
                id="topUpForm"
                class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-4"
                method="POST"
                action="javascript:void(0);"
                onsubmit="return false;"
                data-unit-price-usd="{{ $unitPriceUsd ?? '' }}"
                data-fx-rate-ngn="{{ $fxRateNgn ?? '' }}"
            >
                @csrf
                <div>
                    <label for="requested_credits" class="block text-sm font-medium text-gray-700 mb-1">Requested credits</label>
                    <input id="requested_credits" name="requested_credits" type="number" min="1" step="1" required class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-amber-400" placeholder="e.g. 500" />
                </div>
                <div class="md:col-span-2">
                    <label for="payment_reference" class="block text-sm font-medium text-gray-700 mb-1">Payment reference (optional)</label>
                    <input id="payment_reference" name="payment_reference" type="text" maxlength="255" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-amber-400" placeholder="Bank ref / narration" />
                </div>
                <div class="md:col-span-2">
                    <label for="proof" class="block text-sm font-medium text-gray-700 mb-1">Receipt / proof (optional)</label>
                    <input id="proof" name="proof" type="file" accept=".jpg,.jpeg,.png,.pdf" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-amber-400" />
                    <small class="text-gray-500 text-xs">Accepted: JPG/PNG/PDF up to 10MB</small>
                </div>
                <div class="flex items-end">
                    <button id="topUpBtn" type="submit" class="w-full py-3 bg-amber-400 text-slate-950 font-semibold rounded-lg hover:bg-amber-300 transition">
                        Request top-up
                    </button>
                </div>
                <div class="md:col-span-3 text-sm text-gray-700">
                    Estimated amount: <span id="topUpAmountUsd" class="font-medium">$0.00</span>
                    <span class="text-gray-500">(~ <span id="topUpAmountNgn" class="font-medium">₦0</span>)</span>
                </div>
            </form>
            <div id="topUpMsg" class="mt-2 text-sm"></div>
        </section>

        <section class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm mb-6">
            <h2 class="text-xl font-semibold mb-3">Your invoices</h2>
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
    </main>

    <footer class="text-center text-slate-700 py-6">
        <div class="max-w-5xl mx-auto px-4">
            <small>© <span id="year"></span> Peldarg Consulting Limited</small>
        </div>
    </footer>
</body>
</html>
