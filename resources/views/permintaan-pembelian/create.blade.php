@extends('layouts.app')

@section('title', 'Ajukan Permintaan Pembelian')

@section('content')
@php
    $formatQty = fn ($qty) => number_format((float) $qty, 2, ',', '.');
@endphp

<div class="mx-auto max-w-6xl space-y-5 pb-32">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('permintaan-pembelian.index') }}" class="text-sm font-medium text-teal-700 hover:text-teal-900">
                ← Kembali ke permintaan
            </a>
            <h2 class="mt-3 text-xl font-semibold text-slate-800">Ajukan Permintaan Pembelian</h2>
            <p class="mt-1 text-sm text-slate-500">
                Pilih barang yang stok gabungan seluruh gudangnya sudah mencapai atau berada di bawah minimum.
            </p>
        </div>

        <span class="inline-flex w-fit items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">
            {{ number_format($barangs->count(), 0, ',', '.') }} barang paling kritis ditampilkan
        </span>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <p class="font-semibold">Permintaan belum dapat dikirim.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-xl border border-teal-200 bg-teal-50 p-4 text-sm text-teal-900">
        <strong>Catatan:</strong>
        jumlah saran dihitung untuk mengembalikan stok sampai batas minimum. Gudang dapat menyesuaikan jumlah yang diminta.
        Pengajuan ini tidak mengubah stok dan belum membuat PO.
    </div>

    <form id="permintaan-pembelian-form" method="POST" action="{{ route('permintaan-pembelian.store') }}" class="space-y-5">
        @csrf

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-1 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="font-semibold text-slate-800">Barang yang perlu restock</h3>
                    <p class="mt-1 text-xs text-slate-500">
                        Maksimal 10 barang ditampilkan, dimulai dari stok habis/negatif dan rasio stok terhadap minimum terendah. Barang lain dapat diajukan pada permintaan berikutnya.
                    </p>
                </div>
                <p class="text-xs text-slate-500">Daftar dapat digulir di dalam tabel.</p>
            </div>

            @if($barangs->isEmpty())
                <div class="p-8 text-center">
                    <p class="font-semibold text-slate-700">Tidak ada barang yang dapat diajukan saat ini.</p>
                    <p class="mt-1 text-sm text-slate-500">
                        Stok mungkin sudah di atas minimum atau semua barang kritis sudah memiliki permintaan aktif.
                    </p>
                </div>
            @else
                <div class="max-h-[55vh] overflow-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="sticky top-0 z-10 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500 shadow-sm">
                            <tr>
                                <th class="w-20 whitespace-nowrap px-5 py-3">Pilih</th>
                                <th class="min-w-64 px-5 py-3">Barang</th>
                                <th class="whitespace-nowrap px-5 py-3 text-right">Stok gabungan</th>
                                <th class="whitespace-nowrap px-5 py-3 text-right">Minimum</th>
                                <th class="whitespace-nowrap px-5 py-3 text-right">Jumlah diminta</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($barangs as $barang)
                                @php
                                    $suggestedQty = max(
                                        round((float) $barang->minimum_stok - (float) $barang->total_stok, 2),
                                        0
                                    );
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-3">
                                        <input
                                            type="hidden"
                                            name="items[{{ $barang->id }}][barang_id]"
                                            value="{{ $barang->id }}"
                                        >
                                        <input
                                            type="checkbox"
                                            name="items[{{ $barang->id }}][selected]"
                                            value="1"
                                            @checked(old("items.{$barang->id}.selected"))
                                            aria-label="Pilih {{ $barang->nama_barang }}"
                                            class="h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-600"
                                        >
                                    </td>
                                    <td class="px-5 py-3">
                                        <p class="font-semibold text-slate-800">{{ $barang->nama_barang }}</p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $barang->kode_barang }} · {{ $barang->satuan?->nama_satuan ?? '-' }}
                                        </p>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right font-semibold {{ (float) $barang->total_stok <= 0 ? 'text-rose-700' : 'text-amber-700' }}">
                                        {{ $formatQty($barang->total_stok) }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-slate-600">
                                        {{ $formatQty($barang->minimum_stok) }}
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <input
                                            type="number"
                                            name="items[{{ $barang->id }}][qty_diminta]"
                                            min="0.01"
                                            step="0.01"
                                            max="999999999"
                                            value="{{ old("items.{$barang->id}.qty_diminta", $suggestedQty) }}"
                                            aria-label="Jumlah diminta untuk {{ $barang->nama_barang }}"
                                            class="w-32 rounded-lg border border-slate-300 px-3 py-2 text-right text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-100"
                                        >
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <label for="catatan" class="mb-2 block text-sm font-semibold text-slate-700">
                Catatan Gudang (opsional)
            </label>
            <textarea
                id="catatan"
                name="catatan"
                rows="3"
                maxlength="2000"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-100"
                placeholder="Contoh: barang dibutuhkan untuk pekerjaan proyek ..."
            >{{ old('catatan') }}</textarea>
        </section>
    </form>
</div>

{{-- Action bar sengaja berada di luar area tabel agar tombol selalu terlihat. --}}
<div class="fixed bottom-0 left-64 right-0 z-40 border-t border-slate-200 bg-white/95 px-5 py-3 shadow-[0_-6px_20px_rgba(15,23,42,0.08)] backdrop-blur sm:px-8">
    <div class="mx-auto flex max-w-6xl flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
        <p class="hidden text-xs text-slate-500 sm:block">
            Pengajuan hanya mengirim permintaan ke Pembelian; saldo stok tidak berubah.
        </p>
        <div class="flex items-center justify-end gap-2">
            <a
                href="{{ route('permintaan-pembelian.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-center text-sm font-semibold text-slate-600 hover:bg-slate-50"
            >
                Batal
            </a>
            <button
                type="submit"
                form="permintaan-pembelian-form"
                @disabled($barangs->isEmpty())
                class="rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0F8B8D] disabled:cursor-not-allowed disabled:opacity-50"
            >
                Kirim ke Bagian Pembelian
            </button>
        </div>
    </div>
</div>
@endsection
