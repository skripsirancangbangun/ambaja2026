@extends('layouts.app')

@section('title', 'Detail Jurnal ' . $jurnal->nomor_jurnal)

@section('content')

<div class="mx-auto max-w-4xl space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('jurnal-umum.index') }}" class="mb-2 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]">
                ← Kembali ke Daftar Jurnal
            </a>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold text-slate-800">
                    {{ $jurnal->nomor_jurnal }}
                </h2>
                <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                    Posted (Diposting)
                </span>
            </div>
        </div>

        <form action="{{ route('jurnal-umum.destroy', $jurnal) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jurnal ini?')">
            @csrf
            @method('DELETE')
            <button
                type="submit"
                class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-medium text-rose-700 transition hover:bg-rose-100"
            >
                🗑️ Hapus Jurnal
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- CARD INFORMASI --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <p class="text-xs font-medium uppercase text-slate-400">Keterangan Transaksi</p>
                <p class="mt-1 text-base font-semibold text-slate-800">{{ $jurnal->keterangan }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs font-medium uppercase text-slate-400">Tanggal Transaksi</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">{{ $jurnal->tanggal ? $jurnal->tanggal->format('d F Y') : '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase text-slate-400">Dibuat Oleh</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $jurnal->creator->name ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- DETAIL ITEM TABEL --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h3 class="text-sm font-semibold text-slate-700">Posisi Debet & Kredit</h3>
        </div>

        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50">
                <tr class="text-xs uppercase tracking-wide text-slate-400">
                    <th class="px-6 py-3 font-medium">Kode Akun</th>
                    <th class="px-6 py-3 font-medium">Nama Akun</th>
                    <th class="px-6 py-3 font-medium">Keterangan Baris</th>
                    <th class="px-6 py-3 text-right font-medium">Debit (Rp)</th>
                    <th class="px-6 py-3 text-right font-medium">Kredit (Rp)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($jurnal->details as $detail)
                    <tr>
                        <td class="px-6 py-4 font-mono font-semibold text-[#15235F]">
                            {{ $detail->akun->kode_akun ?? '-' }}
                        </td>
                        <td class="px-6 py-4 font-medium text-slate-800">
                            {{ $detail->akun->nama_akun ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-500">
                            {{ $detail->keterangan ?: '-' }}
                        </td>
                        <td class="px-6 py-4 text-right font-semibold text-slate-800">
                            {{ $detail->debit > 0 ? 'Rp ' . number_format($detail->debit, 0, ',', '.') : '-' }}
                        </td>
                        <td class="px-6 py-4 text-right font-semibold text-slate-800">
                            {{ $detail->kredit > 0 ? 'Rp ' . number_format($detail->kredit, 0, ',', '.') : '-' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t border-slate-200 bg-slate-50 font-bold">
                    <td colspan="3" class="px-6 py-4 text-right text-slate-700">Total:</td>
                    <td class="px-6 py-4 text-right text-emerald-600">
                        Rp {{ number_format($jurnal->total_debit, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4 text-right text-emerald-600">
                        Rp {{ number_format($jurnal->total_kredit, 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

</div>

@endsection
