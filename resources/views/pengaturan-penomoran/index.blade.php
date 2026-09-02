@extends('layouts.app')

@section('title', 'Pengaturan Penomoran Dokumen & Jurnal')

@section('content')

<div class="mx-auto max-w-4xl space-y-6">

    {{-- HEADER --}}
    <div>
        <h2 class="text-xl font-semibold text-slate-800">
            Pengaturan Format Penomoran Dokumen & Jurnal
        </h2>
        <p class="mt-1 text-sm text-slate-500">
            Sesuaikan awalan (prefix) dan jumlah digit penomoran otomatis untuk Invoice, Jurnal, Retur, dan Kwitansi.
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
            ✓ {{ session('success') }}
        </div>
    @endif

    {{-- FORM PENGATURAN --}}
    <form method="POST" action="{{ route('pengaturan-penomoran.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <h3 class="text-sm font-semibold text-slate-700">Format Awalan (Prefix) & Digit</h3>
            </div>

            <div class="divide-y divide-slate-100 p-6 space-y-4">
                @foreach($penomorans as $item)
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between py-2">
                        <div>
                            <label class="block text-sm font-bold text-[#15235F]">
                                {{ $item->nama_dokumen }}
                            </label>
                            <p class="text-xs text-slate-400">
                                Kunci: {{ $item->jenis_dokumen }}
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-500 uppercase">Prefix</label>
                                <input
                                    type="text"
                                    name="settings[{{ $item->id }}][prefix]"
                                    value="{{ old('prefix', $item->prefix) }}"
                                    required
                                    class="w-32 rounded-xl border border-slate-200 px-3 py-2 text-sm font-mono text-slate-800 outline-none focus:border-[#32AAA6]"
                                >
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-slate-500 uppercase">Jumlah Digit</label>
                                <input
                                    type="number"
                                    name="settings[{{ $item->id }}][digits]"
                                    value="{{ old('digits', $item->digits) }}"
                                    min="3"
                                    max="8"
                                    required
                                    class="w-20 rounded-xl border border-slate-200 px-3 py-2 text-sm font-mono text-slate-800 outline-none focus:border-[#32AAA6]"
                                >
                            </div>

                            <div class="pt-4 text-xs text-slate-400 font-mono">
                                (Contoh: {{ $item->prefix }}{{ str_pad('1', $item->digits, '0', STR_PAD_LEFT) }})
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="border-t border-slate-200 bg-slate-50 px-6 py-4 flex justify-end">
                <button
                    type="submit"
                    class="rounded-xl bg-[#15235F] px-6 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
                >
                    💾 Simpan Format Penomoran
                </button>
            </div>
        </div>
    </form>

</div>

@endsection
