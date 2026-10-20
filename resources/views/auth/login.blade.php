<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold tracking-tight text-[#15235F]">Login</h1>
        <p class="mt-1 text-sm text-slate-500">Masuk ke sistem AMBAJA 2026</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="nama@perusahaan.com" class="block w-full rounded-xl border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="mb-1.5 flex items-center justify-between gap-3">
                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                @if (Route::has('password.request'))
                    <a class="text-xs font-medium text-teal-700 hover:text-teal-900" href="{{ route('password.request') }}">Lupa password?</a>
                @endif
            </div>
            <div class="relative">
                <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Masukkan password" class="block w-full rounded-xl border-slate-300 px-4 py-3 pr-12 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                <button type="button" data-password-toggle="password" aria-label="Tampilkan password" aria-pressed="false" title="Tampilkan password" class="absolute inset-y-0 right-0 inline-flex items-center justify-center rounded-r-xl px-3 text-slate-500 transition hover:text-teal-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-teal-500">
                    <x-icon name="eye" class="h-5 w-5" data-eye-icon />
                    <x-icon name="eye-off" class="hidden h-5 w-5" data-eye-off-icon />
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center">
            <input id="remember_me" name="remember" type="checkbox" class="rounded border-slate-300 text-teal-600 shadow-sm focus:ring-teal-500">
            <label for="remember_me" class="ms-2 text-sm text-slate-600">Ingat saya</label>
        </div>

        <button type="submit" class="flex w-full items-center justify-center rounded-xl bg-[#15235F] px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1d3078] focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2">
            Masuk
        </button>
    </form>

    <script>
        document.querySelectorAll('[data-password-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.passwordToggle);
                if (!input) return;

                const shouldShow = input.type === 'password';
                input.type = shouldShow ? 'text' : 'password';
                const label = shouldShow ? 'Sembunyikan password' : 'Tampilkan password';
                button.setAttribute('aria-label', label);
                button.setAttribute('title', label);
                button.setAttribute('aria-pressed', String(shouldShow));
                button.querySelector('[data-eye-icon]')?.classList.toggle('hidden', shouldShow);
                button.querySelector('[data-eye-off-icon]')?.classList.toggle('hidden', !shouldShow);
            });
        });
    </script>
</x-guest-layout>
