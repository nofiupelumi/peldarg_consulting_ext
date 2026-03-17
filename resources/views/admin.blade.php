<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Peldarg Consulting Limited - Admin Console</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css','resources/js/admin.js'])
</head>
<body class="bg-[var(--peldarg-off-white)] text-gray-900 font-sans">
<header class="border-b" style="background: linear-gradient(90deg, var(--peldarg-primary-navy), var(--peldarg-accent-navy-light)); color: var(--peldarg-off-white); border-color: color-mix(in oklab, var(--peldarg-primary-navy) 70%, white);">
    <div class="max-w-6xl mx-auto px-4 py-4">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold" style="background: var(--peldarg-primary-gold); color: var(--peldarg-primary-navy);">PC</div>
                <div>
                    <h1 class="m-0 text-lg font-semibold">Peldarg Consulting Limited</h1>
                    <p class="m-0 text-xs opacity-90">Admin Console</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('dashboard') }}" class="text-sm px-3 py-2 rounded-lg border" style="border-color: color-mix(in oklab, var(--peldarg-off-white) 40%, transparent);">Dashboard</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm px-4 py-2 rounded-lg" style="background: var(--peldarg-primary-gold); color: var(--peldarg-primary-navy);">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

<main class="max-w-6xl mx-auto px-4 py-6">
    <input type="hidden" id="csrf" value="{{ csrf_token() }}" />

    <section class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm mb-6">
        <h2 class="text-xl font-semibold">Settings</h2>
        <p class="text-sm text-gray-600 mt-1">These values affect pricing, upload validation, and admin policy.</p>

        <form id="settingsForm" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4" method="POST" action="javascript:void(0);" onsubmit="return false;">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="unit_price_usd">Unit Price (USD)</label>
                <input id="unit_price_usd" name="unit_price_usd" type="number" step="0.0001" min="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 outline-none focus:border-[var(--peldarg-primary-gold)]" value="{{ $unitPriceUsd }}" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="fx_rate_ngn">FX Rate (NGN per USD)</label>
                <input id="fx_rate_ngn" name="fx_rate_ngn" type="number" step="0.01" min="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 outline-none focus:border-[var(--peldarg-primary-gold)]" value="{{ $fxRateNgn }}" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="max_upload_mb">Max Upload (MB)</label>
                <input id="max_upload_mb" name="max_upload_mb" type="number" step="1" min="1" class="w-full rounded-lg border border-gray-300 px-3 py-2 outline-none focus:border-[var(--peldarg-primary-gold)]" value="{{ $maxUploadMb }}" />
            </div>
            <div class="flex items-end">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input id="admin_2fa_required" name="admin_2fa_required" type="checkbox" class="rounded border-gray-300" {{ $admin2faRequired ? 'checked' : '' }} />
                    Require admin 2FA (policy flag)
                </label>
            </div>
            <div class="md:col-span-2">
                <button id="settingsSaveBtn" type="submit" class="px-4 py-2 rounded-lg font-semibold" style="background: var(--peldarg-primary-navy); color: var(--peldarg-off-white);">
                    Save Settings
                </button>
                <span id="settingsMsg" class="ml-3 text-sm"></span>
            </div>
        </form>
    </section>

    <section class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm mb-6">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <h2 class="text-xl font-semibold">Users</h2>
            <button id="refreshUsers" class="text-sm px-3 py-2 rounded-lg border border-gray-300">Refresh</button>
        </div>

        <form id="createUserForm" class="grid grid-cols-1 md:grid-cols-7 gap-3 mt-4" method="POST" action="javascript:void(0);" onsubmit="return false;">
            <input class="rounded-lg border border-gray-300 px-3 py-2" name="company_name" placeholder="Company name" required />
            <input class="rounded-lg border border-gray-300 px-3 py-2" name="email" type="email" placeholder="Email" required />
            <input class="rounded-lg border border-gray-300 px-3 py-2" name="password" type="password" placeholder="Password (optional)" />
            <input class="rounded-lg border border-gray-300 px-3 py-2" name="credit_cap" type="number" min="0" step="1" placeholder="Credit cap (0=none)" />
            <input class="rounded-lg border border-gray-300 px-3 py-2" name="credit_balance" type="number" min="0" step="1" placeholder="Starting credits" />
            <select class="rounded-lg border border-gray-300 px-3 py-2" name="allowed_api_tiers" multiple size="3" title="Allowed API tiers">
                <option value="paid_1" selected>PAID TIER 1</option>
                <option value="paid_2">PAID TIER 2</option>
                <option value="paid_3">PAID TIER 3</option>
            </select>
            <label class="inline-flex items-center gap-2 text-sm px-2">
                <input type="checkbox" name="is_admin" value="1" />
                Admin
            </label>
            <div class="md:col-span-7 flex items-center gap-3">
                <button class="px-4 py-2 rounded-lg font-semibold" style="background: var(--peldarg-primary-gold); color: var(--peldarg-primary-navy);" type="submit">Create User</button>
                <span id="createUserMsg" class="text-sm"></span>
            </div>
        </form>

        <div class="overflow-auto mt-4">
            <table class="w-full text-sm border-collapse" id="usersTable">
                <thead>
                <tr class="bg-gray-50">
                    <th class="text-left p-2 border-b">ID</th>
                    <th class="text-left p-2 border-b">Company</th>
                    <th class="text-left p-2 border-b">Email</th>
                    <th class="text-left p-2 border-b">Balance</th>
                    <th class="text-left p-2 border-b">Cap</th>
                    <th class="text-left p-2 border-b">Status</th>
                    <th class="text-left p-2 border-b">Allowed API tiers</th>
                    <th class="text-left p-2 border-b">Actions</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </section>

    <section class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm mb-6">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <h2 class="text-xl font-semibold">Credit Invoices</h2>
            <div class="flex items-center gap-2">
                <select id="invoiceStatus" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">All</option>
                    <option value="pending" selected>Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <button id="refreshInvoices" class="text-sm px-3 py-2 rounded-lg border border-gray-300">Refresh</button>
            </div>
        </div>

        <div class="overflow-auto mt-4">
            <table class="w-full text-sm border-collapse" id="invoicesTable">
                <thead>
                <tr class="bg-gray-50">
                    <th class="text-left p-2 border-b">Invoice</th>
                    <th class="text-left p-2 border-b">User</th>
                    <th class="text-left p-2 border-b">Credits</th>
                    <th class="text-left p-2 border-b">Amount (USD)</th>
                    <th class="text-left p-2 border-b">Status</th>
                    <th class="text-left p-2 border-b">Created</th>
                    <th class="text-left p-2 border-b">Actions</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div id="invoiceMsg" class="text-sm text-gray-600 mt-2"></div>
    </section>

    <section class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm mb-6">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <h2 class="text-xl font-semibold">Documents</h2>
            <button id="refreshDocs" class="text-sm px-3 py-2 rounded-lg border border-gray-300">Refresh</button>
        </div>

        <div class="overflow-auto mt-4">
            <table class="w-full text-sm border-collapse" id="adminDocsTable">
                <thead>
                <tr class="bg-gray-50">
                    <th class="text-left p-2 border-b">ID</th>
                    <th class="text-left p-2 border-b">User</th>
                    <th class="text-left p-2 border-b">Filename</th>
                    <th class="text-left p-2 border-b">Status</th>
                    <th class="text-left p-2 border-b">Credit</th>
                    <th class="text-left p-2 border-b">Pages</th>
                    <th class="text-left p-2 border-b">CSV</th>
                    <th class="text-left p-2 border-b">XLSX</th>
                    <th class="text-left p-2 border-b">Created</th>
                    <th class="text-left p-2 border-b">Actions</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </section>

    <section class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm mb-6">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <h2 class="text-xl font-semibold">Credit Ledger</h2>
            <button id="refreshLedger" class="text-sm px-3 py-2 rounded-lg border border-gray-300">Refresh</button>
        </div>

        <div class="overflow-auto mt-4">
            <table class="w-full text-sm border-collapse" id="ledgerTable">
                <thead>
                <tr class="bg-gray-50">
                    <th class="text-left p-2 border-b">ID</th>
                    <th class="text-left p-2 border-b">User</th>
                    <th class="text-left p-2 border-b">Type</th>
                    <th class="text-left p-2 border-b">Credits</th>
                    <th class="text-left p-2 border-b">Before</th>
                    <th class="text-left p-2 border-b">After</th>
                    <th class="text-left p-2 border-b">Doc</th>
                    <th class="text-left p-2 border-b">Created</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </section>

    <section class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm mb-6">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <h2 class="text-xl font-semibold">Audit Logs</h2>
            <button id="refreshAudit" class="text-sm px-3 py-2 rounded-lg border border-gray-300">Refresh</button>
        </div>

        <div class="overflow-auto mt-4">
            <table class="w-full text-sm border-collapse" id="auditTable">
                <thead>
                <tr class="bg-gray-50">
                    <th class="text-left p-2 border-b">ID</th>
                    <th class="text-left p-2 border-b">Event</th>
                    <th class="text-left p-2 border-b">Actor</th>
                    <th class="text-left p-2 border-b">Target</th>
                    <th class="text-left p-2 border-b">Entity</th>
                    <th class="text-left p-2 border-b">Created</th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </section>
</main>

<footer class="text-center py-6" style="color: var(--peldarg-primary-navy);">
    <small>© <span id="year"></span> Peldarg Consulting Limited</small>
</footer>

<script>
    document.getElementById('year').textContent = new Date().getFullYear();
</script>
</body>
</html>
