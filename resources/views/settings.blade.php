<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Peldarg Consulting Limited - Settings</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css','resources/js/settings.js'])
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
                    <a href="{{ route('topup') }}" class="text-sm px-3 py-2 rounded-lg hover:bg-white/10">Top up</a>
                    <a href="{{ route('payment.history') }}" class="text-sm px-3 py-2 rounded-lg hover:bg-white/10">Payment history</a>
                    <a href="{{ route('settings') }}" class="text-sm px-3 py-2 rounded-lg bg-white/10">Settings</a>

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
        <section class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm mb-6">
            <h2 class="text-xl font-semibold">Profile</h2>
            <p class="text-sm text-gray-600 mt-1">Update your company name and email.</p>

            <form id="profileForm" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3" method="POST" action="javascript:void(0);" onsubmit="return false;">
                @csrf
                <div>
                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">Company name</label>
                    <input id="company_name" name="company_name" type="text" maxlength="255" value="{{ $companyName ?? '' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-amber-400" />
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input id="email" name="email" type="email" maxlength="255" value="{{ $email ?? '' }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-amber-400" />
                </div>
                <div class="md:col-span-2">
                    <button id="profileSaveBtn" type="submit" class="py-3 px-4 bg-amber-400 text-slate-950 font-semibold rounded-lg hover:bg-amber-300 transition">Save profile</button>
                    <span id="profileMsg" class="ml-3 text-sm"></span>
                </div>
            </form>
        </section>

        <section class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm mb-6">
            <h2 class="text-xl font-semibold">Change password</h2>
            <p class="text-sm text-gray-600 mt-1">Choose a strong password you can remember.</p>

            <form id="passwordForm" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3" method="POST" action="javascript:void(0);" onsubmit="return false;">
                @csrf
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current password</label>
                    <input id="current_password" name="current_password" type="password" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-amber-400" />
                </div>
                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New password</label>
                    <input id="new_password" name="new_password" type="password" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-amber-400" />
                </div>
                <div class="md:col-span-2">
                    <button id="passwordSaveBtn" type="submit" class="py-3 px-4 bg-amber-400 text-slate-950 font-semibold rounded-lg hover:bg-amber-300 transition">Change password</button>
                    <span id="passwordMsg" class="ml-3 text-sm"></span>
                </div>
            </form>
        </section>
    </main>

    <footer class="text-center text-slate-700 py-6">
        <div class="max-w-5xl mx-auto px-4">
            <small>© <span id="year"></span> Peldarg Consulting Limited</small>
        </div>
    </footer>
</body>
</html>
