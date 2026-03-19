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
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-300 focus:border-amber-400 outline-none transition"
                            placeholder="••••••••"
                        />
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
</body>
</html>
