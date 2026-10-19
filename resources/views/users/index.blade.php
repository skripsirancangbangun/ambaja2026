@extends('layouts.app')

@section('title', 'Manajemen Pengguna')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-slate-800">Manajemen Pengguna</h2>
            <p class="mt-1 text-sm text-slate-500">Kelola akun, role, dan status akses aplikasi AMBAJA.</p>
        </div>
        <a href="{{ route('users.create') }}" class="inline-flex items-center justify-center rounded-xl bg-[#15235F] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1d3078]">
            + Tambah Pengguna
        </a>
    </div>

    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('users.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-4">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email" class="rounded-xl border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500 md:col-span-2">
            <select name="role" class="rounded-xl border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                <option value="">Semua role</option>
                @foreach($roles as $role)
                    <option value="{{ $role }}" @selected(request('role') === $role)>{{ $role }}</option>
                @endforeach
            </select>
            <select name="status" class="rounded-xl border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                <option value="">Semua status</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
            </select>
            <div class="flex gap-2 md:col-span-4">
                <button type="submit" class="rounded-xl bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700">Filter</button>
                <a href="{{ route('users.index') }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset</a>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Pengguna</th>
                        <th class="px-5 py-3 font-medium">Role</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium">Dibuat</th>
                        <th class="px-5 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        <tr>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-800">{{ $user->name }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $user->email }}</div>
                                @if((int) auth()->id() === (int) $user->id)
                                    <span class="mt-1 inline-block text-xs font-medium text-teal-700">Akun Anda</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ $user->role }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                @if($user->is_active)
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Aktif</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Nonaktif</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-500">{{ $user->created_at?->format('d/m/Y') ?? '-' }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('users.edit', $user) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Edit</a>
                                    @if($user->is_active && (int) auth()->id() !== (int) $user->id)
                                        <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Nonaktifkan akun ini? Riwayat transaksi tidak akan dihapus.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">Nonaktifkan</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada pengguna yang sesuai dengan filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-5 py-4">{{ $users->links() }}</div>
    </section>
</div>
@endsection
