@extends('layouts.app')

@section('title', 'Detail Invoice ' . $invoicePenjualan->nomor_invoice)

@section('content')

<div class="mx-auto max-w-5xl space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('invoice-penjualan.index') }}" class="mb-2 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]">
                ← Kembali ke Daftar Invoice
            </a>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold text-slate-800">
                    {{ $invoicePenjualan->nomor_invoice }}
                </h2>
                @if($invoicePenjualan->status_pembayaran == 'Lunas')
                    <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                        Lunas
                    </span>
                @elseif($invoicePenjualan->status_pembayaran == 'Sebagian')
                    <span class="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                        Sebagian
                    </span>
                @else
                    <span class="inline-flex rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">
                        Belum Lunas
                    </span>
                @endif
                @if($invoicePenjualan->is_email_sent)
                    <span class="inline-flex items-center gap-1 rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700 border border-teal-200" title="Terkirim ke {{ $invoicePenjualan->email_sent_to }}">
                        ✓ Email Terkirim ({{ $invoicePenjualan->email_sent_at ? $invoicePenjualan->email_sent_at->format('d/m/Y H:i') : '' }})
                    </span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2" x-data="{ showEmailModal: false }">
            <button
                type="button"
                @click="showEmailModal = true"
                class="rounded-xl border border-teal-200 bg-teal-50 px-4 py-2 text-sm font-medium text-teal-800 transition hover:bg-teal-100"
            >
                📧 Kirim Email Bukti Invoice
            </button>
            <a
                href="{{ route('invoice-penjualan.pdf', $invoicePenjualan) }}"
                target="_blank"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            >
                🖨️ Cetak PDF
            </a>
            @if($invoicePenjualan->status_pembayaran != 'Lunas')
                <a
                    href="{{ route('pembayaran-penjualan.create', ['customer_id' => $invoicePenjualan->customer_id]) }}"
                    class="rounded-xl bg-[#15235F] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#101b4d]"
                >
                    💰 Catat Pembayaran
                </a>
            @endif
            <a
                href="{{ route('retur-penjualan.create', ['invoice_id' => $invoicePenjualan->id]) }}"
                class="rounded-xl bg-rose-50 px-4 py-2 text-sm font-medium text-rose-700 transition hover:bg-rose-100"
            >
                ↩️ Buat Retur
            </a>

            {{-- MODAL KIRIM EMAIL --}}
            <div x-show="showEmailModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm">
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl" @click.away="showEmailModal = false">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-base font-semibold text-slate-800">Kirim Bukti Invoice via Email</h3>
                        <button type="button" @click="showEmailModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>
                    <form action="{{ route('invoice-penjualan.kirim-email', $invoicePenjualan) }}" method="POST" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Email Pengirim (CV Andika)</label>
                            <input type="email" value="ezicraft88@gmail.com" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2 text-sm text-slate-600">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Email Pelanggan / Penerima <span class="text-rose-500">*</span></label>
                            <input
                                type="email"
                                name="email_tujuan"
                                value="{{ old('email_tujuan', $invoicePenjualan->customer->email ?? 'ezicraft88@gmail.com') }}"
                                required
                                class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-800 focus:border-[#32AAA6] focus:outline-none"
                                placeholder="nama@pelanggan.com"
                            >
                            <p class="mt-1 text-[11px] text-slate-400">File bukti PDF Invoice #{{ $invoicePenjualan->nomor_invoice }} akan disertakan secara otomatis.</p>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="showEmailModal = false" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50">
                                Batal
                            </button>
                            <button type="submit" class="rounded-xl bg-[#15235F] px-5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-[#101b4d]">
                                📤 Kirim Email Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- CARD INFORMASI --}}
    <div class="grid grid-cols-1 gap-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:grid-cols-2">
        <div class="space-y-3">
            <div>
                <p class="text-xs font-medium uppercase text-slate-400">Customer</p>
                <p class="mt-1 text-base font-semibold text-slate-800">{{ $invoicePenjualan->customer->nama_customer ?? '-' }}</p>
                <p class="text-xs text-slate-500">{{ $invoicePenjualan->customer->alamat ?? '' }}</p>
                <p class="text-xs text-slate-500">Telp: {{ $invoicePenjualan->customer->telepon ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase text-slate-400">Gudang Pengeluaran</p>
                <p class="mt-1 text-sm font-medium text-slate-700">{{ $invoicePenjualan->gudang->nama_gudang ?? '-' }}</p>
            </div>
            @if($invoicePenjualan->penjualan)
                <div>
                    <p class="text-xs font-medium uppercase text-slate-400">Sales Order Terkait</p>
                    <a href="{{ route('penjualan.show', $invoicePenjualan->penjualan) }}" class="mt-1 inline-block text-sm font-semibold text-[#15235F] hover:underline">
                        {{ $invoicePenjualan->penjualan->nomor_penjualan }}
                    </a>
                </div>
            @endif
        </div>

        <div class="space-y-3">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs font-medium uppercase text-slate-400">Tanggal Invoice</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">{{ $invoicePenjualan->tanggal_invoice ? $invoicePenjualan->tanggal_invoice->format('d F Y') : '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase text-slate-400">Jatuh Tempo</p>
                    <p class="mt-1 text-sm font-semibold {{ ($invoicePenjualan->sisa_piutang > 0 && $invoicePenjualan->tanggal_jatuh_tempo && $invoicePenjualan->tanggal_jatuh_tempo->isPast()) ? 'text-rose-600' : 'text-slate-700' }}">
                        {{ $invoicePenjualan->tanggal_jatuh_tempo ? $invoicePenjualan->tanggal_jatuh_tempo->format('d F Y') : '-' }}
                    </p>
                </div>
            </div>
            <div>
                <p class="text-xs font-medium uppercase text-slate-400">Tipe Pembayaran</p>
                <p class="mt-1 text-sm font-medium text-slate-700">
                    {{ $invoicePenjualan->tipe_pembayaran }}
                    @if($invoicePenjualan->rekeningKasBank)
                        (Masuk ke {{ $invoicePenjualan->rekeningKasBank->nama_rekening }})
                    @endif
                </p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase text-slate-400">Dibuat Oleh</p>
                <p class="mt-1 text-sm text-slate-600">{{ $invoicePenjualan->creator->name ?? '-' }} ({{ $invoicePenjualan->created_at->format('d/m/Y H:i') }})</p>
            </div>
            @if($invoicePenjualan->catatan)
                <div>
                    <p class="text-xs font-medium uppercase text-slate-400">Catatan</p>
                    <p class="mt-1 text-xs text-slate-600">{{ $invoicePenjualan->catatan }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- TABEL ITEM --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h3 class="text-sm font-semibold text-slate-700">Rincian Barang Faktur</h3>
        </div>

        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50">
                <tr class="text-xs uppercase tracking-wide text-slate-400">
                    <th class="px-6 py-3 font-medium">No</th>
                    <th class="px-6 py-3 font-medium">Kode & Nama Barang</th>
                    <th class="px-6 py-3 text-right font-medium">Qty</th>
                    <th class="px-6 py-3 text-right font-medium">Harga Satuan</th>
                    <th class="px-6 py-3 text-right font-medium">Diskon</th>
                    <th class="px-6 py-3 text-right font-medium">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($invoicePenjualan->items as $index => $item)
                    <tr>
                        <td class="px-6 py-4 text-slate-500">{{ $index + 1 }}</td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-slate-800">{{ $item->barang->nama_barang ?? '-' }}</div>
                            <div class="text-xs text-slate-400">{{ $item->barang->kode_barang ?? '' }}</div>
                        </td>
                        <td class="px-6 py-4 text-right font-medium text-slate-700">
                            {{ number_format($item->qty, 0, ',', '.') }} {{ $item->barang->satuan->nama_satuan ?? '' }}
                        </td>
                        <td class="px-6 py-4 text-right text-slate-700">
                            Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right text-slate-700">
                            Rp {{ number_format($item->diskon, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right font-semibold text-slate-800">
                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- SUMMARY FOOTER --}}
        <div class="border-t border-slate-200 bg-slate-50/50 p-6">
            <div class="ml-auto max-w-xs space-y-2 text-sm">
                <div class="flex justify-between text-slate-500">
                    <span>Subtotal:</span>
                    <span class="font-medium text-slate-800">Rp {{ number_format($invoicePenjualan->subtotal, 0, ',', '.') }}</span>
                </div>
                @if($invoicePenjualan->diskon > 0)
                    <div class="flex justify-between text-slate-500">
                        <span>Diskon:</span>
                        <span class="font-medium text-slate-800">Rp {{ number_format($invoicePenjualan->diskon, 0, ',', '.') }}</span>
                    </div>
                @endif
                @if($invoicePenjualan->pajak > 0)
                    <div class="flex justify-between text-slate-500">
                        <span>PPN ({{ $invoicePenjualan->pajak_persen }}%):</span>
                        <span class="font-medium text-slate-800">Rp {{ number_format($invoicePenjualan->pajak, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold text-[#15235F]">
                    <span>Total Faktur:</span>
                    <span>Rp {{ number_format($invoicePenjualan->total, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-emerald-600">
                    <span>Sudah Dibayar:</span>
                    <span class="font-semibold">Rp {{ number_format($invoicePenjualan->sudah_dibayar, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-base font-bold {{ $invoicePenjualan->sisa_piutang > 0 ? 'text-rose-600' : 'text-slate-700' }}">
                    <span>Sisa Piutang:</span>
                    <span>Rp {{ number_format($invoicePenjualan->sisa_piutang, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- RIWAYAT PEMBAYARAN --}}
    @if($invoicePenjualan->pembayaranDetails->isNotEmpty())
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <h3 class="text-sm font-semibold text-slate-700">Riwayat Pembayaran Piutang</h3>
            </div>
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-6 py-3 font-medium">No. Bukti Bayar</th>
                        <th class="px-6 py-3 font-medium">Tanggal</th>
                        <th class="px-6 py-3 font-medium">Rekening Masuk</th>
                        <th class="px-6 py-3 text-right font-medium">Nominal Dibayar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($invoicePenjualan->pembayaranDetails as $detail)
                        <tr>
                            <td class="px-6 py-4 font-semibold text-[#15235F]">
                                <a href="{{ route('pembayaran-penjualan.show', $detail->pembayaranPenjualan) }}" class="hover:underline">
                                    {{ $detail->pembayaranPenjualan->nomor_pembayaran ?? '-' }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $detail->pembayaranPenjualan->tanggal_pembayaran ? $detail->pembayaranPenjualan->tanggal_pembayaran->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $detail->pembayaranPenjualan->rekeningKasBank->nama_rekening ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-emerald-600">
                                Rp {{ number_format($detail->nominal_bayar, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>

@endsection
