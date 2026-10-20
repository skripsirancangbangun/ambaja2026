@extends('layouts.app')

@section('title', $pageTitle)

@section('content')
@php
    $isEditing = isset($user);
    $isOwnAccount = $isEditing && (int) auth()->id() === (int) $user->id;
    $activeValue = old('is_active', $user->is_active ?? true);
@endphp

<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <a href="{{ route('users.index') }}" class="text-sm font-semibold text-teal-700 hover:text-teal-900">&larr; Kembali ke Manajemen Pengguna</a>
        <h2 class="mt-3 text-2xl font-semibold text-slate-800">{{ $pageTitle }}</h2>
        <p class="mt-1 text-sm text-slate-500">Password disimpan menggunakan hashing Laravel. Jangan gunakan satu akun bersama untuk beberapa pegawai.</p>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $isEditing ? route('users.update', $user) : route('users.store') }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @if($isEditing)
            @method('PUT')
        @endif

        <div>
            <label for="name" class="mb-1.5 block text-sm font-medium text-slate-700">Nama lengkap</label>
            <input id="name" name="name" type="text" required maxlength="255" value="{{ old('name', $user->name ?? '') }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
            @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email login</label>
            <input id="email" name="email" type="email" required maxlength="255" autocomplete="username" value="{{ old('email', $user->email ?? '') }}" class="w-full rounded-xl border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
            @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="role" class="mb-1.5 block text-sm font-medium text-slate-700">Role</label>
            @if($isOwnAccount)
                <input type="hidden" name="role" value="{{ $user->role }}">
                <input id="role" type="text" disabled value="{{ $user->role }}" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm text-slate-500">
                <p class="mt-1 text-xs text-slate-500">Role akun yang sedang digunakan tidak dapat diubah dari akun itu sendiri.</p>
            @else
                <select id="role" name="role" required class="w-full rounded-xl border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                    <option value="">Pilih role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role }}" @selected(old('role', $user->role ?? '') === $role)>{{ $role }}</option>
                    @endforeach
                </select>
            @endif
            @error('role')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">{{ $isEditing ? 'Password baru (opsional)' : 'Password' }}</label>
            <div class="relative">
                <input id="password" name="password" type="password" autocomplete="new-password" {{ $isEditing ? '' : 'required' }} class="w-full rounded-xl border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500 pr-12">
                <button type="button" data-password-toggle="password" aria-label="Tampilkan password" aria-pressed="false" title="Tampilkan password" class="absolute inset-y-0 right-0 inline-flex items-center justify-center rounded-r-xl px-3 text-slate-500 transition hover:text-teal-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-teal-500">
                                    <x-icon name="eye" class="h-5 w-5" data-eye-icon />
                                    <x-icon name="eye-off" class="hidden h-5 w-5" data-eye-off-icon />
                                </button>
            </div>
            <p class="mt-1 text-xs text-slate-500">Minimal mengikuti kebijakan password aplikasi. Saat edit, kosongkan jika tidak ingin mengganti password.</p>
            @error('password')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-slate-700">Konfirmasi password{{ $isEditing ? ' baru' : '' }}</label>
            <div class="relative">
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" {{ $isEditing ? '' : 'required' }} class="w-full rounded-xl border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500 pr-12">
                <button type="button" data-password-toggle="password_confirmation" aria-label="Tampilkan password" aria-pressed="false" title="Tampilkan password" class="absolute inset-y-0 right-0 inline-flex items-center justify-center rounded-r-xl px-3 text-slate-500 transition hover:text-teal-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-teal-500">
                                    <x-icon name="eye" class="h-5 w-5" data-eye-icon />
                                    <x-icon name="eye-off" class="hidden h-5 w-5" data-eye-off-icon />
                                </button>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <input type="hidden" name="is_active" value="0">
            <label for="is_active" class="flex cursor-pointer items-start gap-3">
                <input id="is_active" name="is_active" type="checkbox" value="1" @checked((bool) $activeValue) class="mt-0.5 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                <span>
                    <span class="block text-sm font-semibold text-slate-800">Akun aktif</span>
                    <span class="mt-1 block text-xs text-slate-500">Jika dinonaktifkan, pengguna tidak dapat login. Sesi yang sedang aktif juga akan dihentikan saat mengakses halaman berikutnya.</span>
                </span>
            </label>
            @error('is_active')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
            <a href="{{ route('users.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
            <button type="submit" class="rounded-xl bg-[#15235F] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#1d3078]">Simpan Pengguna</button>
        </div>
    </form>
</div>
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
@endsection
