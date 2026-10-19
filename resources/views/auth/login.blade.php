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
                    <svg data-eye-icon xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="h-5 w-5">
                        <path d="M2.25 12s3.55-6.75 9.75-6.75S21.75 12 21.75 12 18.2 18.75 12 18.75 2.25 12 2.25 12Z" />
                        <circle cx="12" cy="12" r="2.75" />
                    </svg>
                    <svg data-eye-off-icon xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="hidden h-5 w-5">
                        <path d="M3 3l18 18" />
                        <path d="M10.58 10.58a2 2 0 0 0 2.84 2.84" />
                        <path d="M9.88 5.24A10.6 10.6 0 0 1 12 5.03c6.2 0 9.75 6.97 9.75 6.97a16.7 16.7 0 0 1-3.13 3.88" />
                        <path d="M6.61 6.61C3.77 8.3 2.25 12 2.25 12s3.55 6.97 9.75 6.97c1.17 0 2.23-.24 3.18-.64" />
                    </svg>
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
