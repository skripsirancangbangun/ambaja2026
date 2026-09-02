@extends('layouts.app')

@section('title', 'Pengaturan Akun Sistem')

@section('content')

<div class="mx-auto max-w-4xl space-y-6">

    {{-- HEADER --}}
    <div>
        <h2 class="text-xl font-semibold text-slate-800">
            Pengaturan Pemetaan Akun Sistem
        </h2>
        <p class="mt-1 text-sm text-slate-500">
            Tentukan akun Bagan Akun (COA) default yang akan digunakan secara otomatis oleh sistem saat transaksi terjadi.
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
            ✓ {{ session('success') }}
        </div>
    @endif

    {{-- FORM PENGATURAN --}}
    <form method="POST" action="{{ route('pengaturan-akun.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <h3 class="text-sm font-semibold text-slate-700">Daftar Pemetaan Akun Transaksi Otomatis</h3>
                <p class="text-xs text-slate-400">Pilih akun COA yang sesuai untuk masing-masing fungsi transaksi.</p>
            </div>

            <div class="divide-y divide-slate-100 p-6 space-y-4">
                @foreach($pengaturanAkuns as $item)
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between py-2">
                        <div class="max-w-md">
                            <label class="block text-sm font-semibold text-[#15235F]">
                                {{ $item->nama_pengaturan }}
                            </label>
                            <p class="text-xs text-slate-400">
                                {{ $item->keterangan }}
                            </p>
                            <span class="inline-block mt-1 font-mono text-[11px] text-slate-400 bg-slate-100 px-2 py-0.5 rounded">
                                {{ $item->kunci_pengaturan }}
                            </span>
                        </div>

                        <div class="w-full sm:w-72">
                            <select
                                name="mappings[{{ $item->id }}]"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                            >
                                <option value="">-- Pilih Akun COA --</option>
                                @foreach($akuns as $a)
                                    <option value="{{ $a->id }}" {{ $item->akun_id == $a->id ? 'selected' : '' }}>
                                        {{ $a->kode_akun }} - {{ $a->nama_akun }} ({{ $a->sub_kategori ?: $a->kategori_akun }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="border-t border-slate-200 bg-slate-50 px-6 py-4 flex justify-end">
                <button
                    type="submit"
                    class="rounded-xl bg-[#15235F] px-6 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
                >
                    💾 Simpan Pengaturan Akun
                </button>
            </div>
        </div>
    </form>

</div>

@endsection
