<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Peldarg Consulting Limited</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-950 min-h-screen flex items-center justify-center font-sans">
    <div class="w-full max-w-md px-4">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-200">
            <!-- Header -->
            <div class="bg-slate-950 text-white px-8 py-6 border-b border-white/10">
                <h1 class="text-2xl font-bold text-center">Institutions Convocation Booklet Extraction Platform</h1>
            </div>

            <!-- Login Form -->
            <div class="px-8 py-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-6 text-center">Sign In</h2>

                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                        <ul class="text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('success'))
                    <div class="bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 rounded-lg mb-4 text-sm">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.submit') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="{{ old('email') }}"
                            required 
                            autofocus
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-300 focus:border-amber-400 outline-none transition"
                            placeholder=""
                        />
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required
                                class="w-full px-4 py-3 pr-12 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-300 focus:border-amber-400 outline-none transition"
                                placeholder="••••••••"
                            />
                            <button
                                type="button"
                                id="togglePasswordBtn"
                                aria-label="Show password"
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-500 hover:text-slate-700"
                            >
                                <svg id="eyeOpenIcon" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg id="eyeClosedIcon" class="h-5 w-5 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20C5 20 1 12 1 12a21.77 21.77 0 0 1 5.06-6.94"></path>
                                    <path d="M9.9 4.24A10.87 10.87 0 0 1 12 4c7 0 11 8 11 8a21.79 21.79 0 0 1-3.18 4.78"></path>
                                    <path d="M1 1l22 22"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button 
                        type="submit"
                        class="w-full bg-amber-400 hover:bg-amber-300 text-slate-950 font-semibold px-4 py-3 rounded-lg transition duration-200 shadow-md hover:shadow-lg"
                    >
                        Sign In
                    </button>
                </form>

                <div class="mt-6 text-center">
                </div>
            </div>
        </div>

        <div class="text-center mt-6">
            <p class="text-sm text-white/70">© {{ date('Y') }} Peldarg Consulting Limited</p>
        </div>
    </div>
        <script>
            const passwordInput = document.getElementById('password');
            const togglePasswordBtn = document.getElementById('togglePasswordBtn');
            const eyeOpenIcon = document.getElementById('eyeOpenIcon');
            const eyeClosedIcon = document.getElementById('eyeClosedIcon');

            if (passwordInput && togglePasswordBtn && eyeOpenIcon && eyeClosedIcon) {
                togglePasswordBtn.addEventListener('click', () => {
                    const isHidden = passwordInput.type === 'password';
                    passwordInput.type = isHidden ? 'text' : 'password';
                    togglePasswordBtn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                    eyeOpenIcon.classList.toggle('hidden', isHidden);
                    eyeClosedIcon.classList.toggle('hidden', !isHidden);
                });
            }
        </script>
</body>
</html>
