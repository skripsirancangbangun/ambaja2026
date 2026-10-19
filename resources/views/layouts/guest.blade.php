<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'AMBAJA 2026') }} | Login</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">
            <header class="mb-6 flex justify-center">
                <a href="/" class="inline-flex items-center gap-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-4">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white p-2 shadow-sm ring-1 ring-slate-200">
                        <img src="{{ asset('images/LogoAMBAJA.png') }}" alt="Logo AMBAJA" class="max-h-full max-w-full object-contain">
                    </span>
                    <span>
                        <span class="block text-xl font-bold tracking-tight text-[#15235F]">AMBAJA 2026</span>
                        <span class="mt-0.5 block text-sm text-slate-500">Sistem Informasi Akuntansi</span>
                    </span>
                </a>
            </header>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-lg shadow-slate-200/70 sm:p-8">
                {{ $slot }}
            </section>

            <p class="mt-5 text-center text-xs text-slate-400">&copy; {{ date('Y') }} CV Andika Mustika</p>
        </div>
    </main>
</body>
</html>
