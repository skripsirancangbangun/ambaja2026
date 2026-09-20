@extends('layouts.app')

@section('title', 'Detail Piutang & Penagihan - ' . $invoice->nomor_invoice)

@section('content')

<div class="mx-auto max-w-5xl space-y-6" x-data="{ showPaymentModal: false, showTindakLanjutModal: false }">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('piutang.index') }}" class="mb-2 inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-[#15235F]">
                ← Kembali ke Daftar Piutang
            </a>
            <h2 class="text-xl font-semibold text-slate-800">
                Detail Piutang Invoice #{{ $invoice->nomor_invoice }}
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                Customer: <span class="font-bold text-[#15235F]">{{ $invoice->customer?->nama_customer }}</span> | Telepon: {{ $invoice->customer?->telepon ?: '-' }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if($invoice->sisa_piutang > 0)
                <button
                    type="button"
                    @click="showPaymentModal = true"
                    class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 shadow-sm"
                >
                    💵 Terima Pembayaran
                </button>
            @endif

            <button
                type="button"
                @click="showTindakLanjutModal = true"
                class="rounded-xl bg-[#15235F] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#101b4d] shadow-sm"
            >
                📝 Catat Tindak Lanjut / Terbitkan SP
            </button>

            <a
                href="{{ route('piutang.surat-tagihan.pdf', $invoice) }}"
                target="_blank"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                📄 Cetak Surat Tagihan
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
            ✓ {{ session('success') }}
        </div>
    @endif

    {{-- INFORMASI CARD --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Tagihan</span>
            <p class="mt-1 text-xl font-bold text-slate-800">Rp {{ number_format($invoice->total, 0, ',', '.') }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Sudah Dibayar</span>
            <p class="mt-1 text-xl font-bold text-emerald-600">Rp {{ number_format($invoice->sudah_dibayar, 0, ',', '.') }}</p>
        </div>

        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5">
            <span class="text-xs font-semibold uppercase tracking-wider text-rose-500">Sisa Piutang</span>
            <p class="mt-1 text-xl font-bold text-rose-700">Rp {{ number_format($invoice->sisa_piutang, 0, ',', '.') }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Jatuh Tempo</span>
            <p class="mt-1 text-sm font-bold text-slate-800">
                {{ $invoice->tanggal_jatuh_tempo ? $invoice->tanggal_jatuh_tempo->format('d/m/Y') : '-' }}
            </p>
            @php $isOverdue = $invoice->tanggal_jatuh_tempo && $invoice->tanggal_jatuh_tempo->isPast() && $invoice->sisa_piutang > 0; @endphp
            @if($isOverdue)
                <span class="inline-block mt-1 text-xs font-bold text-rose-600">⚠️ Overdue {{ abs(now()->diffInDays($invoice->tanggal_jatuh_tempo, false)) }} Hari</span>
            @else
                <span class="inline-block mt-1 text-xs font-semibold text-emerald-600">Status: {{ $invoice->status_pembayaran }}</span>
            @endif
        </div>
    </div>

    {{-- HISTORI PEMBAYARAN --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4 flex justify-between items-center">
            <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Histori Pembayaran / Cicilan Diterima</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-6 py-3 font-medium">No. Kwitansi</th>
                        <th class="px-6 py-3 font-medium">Tanggal</th>
                        <th class="px-6 py-3 font-medium">Rekening Kas/Bank</th>
                        <th class="px-6 py-3 text-right font-medium">Nominal Dibayar (Rp)</th>
                        <th class="px-6 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($invoice->penerimaanDetails as $detail)
                        @php $penerimaan = $detail->penerimaanPiutang; @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 font-mono font-bold text-[#15235F]">
                                {{ $penerimaan?->nomor_penerimaan }}
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $penerimaan?->tanggal ? $penerimaan->tanggal->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $penerimaan?->rekeningKasBank?->nama_rekening }}
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-emerald-600">
                                Rp {{ number_format($detail->nominal_dibayar, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($penerimaan)
                                    <a
                                        href="{{ route('piutang.kwitansi.pdf', $penerimaan) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-3 py-1 text-xs font-semibold text-red-600 hover:bg-red-100"
                                    >
                                        🖨️ Kwitansi PDF
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-6 text-center text-slate-400">
                                Belum ada riwayat pembayaran untuk invoice ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- HISTORI RETUR PENJUALAN --}}
    @if($invoice->returPenjualans && $invoice->returPenjualans->isNotEmpty())
        <div class="overflow-hidden rounded-2xl border border-rose-200 bg-white shadow-sm">
            <div class="border-b border-rose-200 bg-rose-50/50 px-6 py-4 flex justify-between items-center">
                <div>
                    <h3 class="text-sm font-bold text-rose-800 uppercase tracking-wider">Histori Retur Penjualan</h3>
                    <p class="text-xs text-rose-600">Pengembalian barang yang memotong sisa piutang / pengembalian dana.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-rose-50/30 text-xs uppercase tracking-wider text-rose-700">
                        <tr>
                            <th class="px-6 py-3 font-medium">No. Retur</th>
                            <th class="px-6 py-3 font-medium">Tanggal Retur</th>
                            <th class="px-6 py-3 text-right font-medium">Total Retur (Rp)</th>
                            <th class="px-6 py-3 text-right font-medium">Potong Piutang (Rp)</th>
                            <th class="px-6 py-3 text-right font-medium">Refund Kas (Rp)</th>
                            <th class="px-6 py-3 font-medium">Alasan</th>
                            <th class="px-6 py-3 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach($invoice->returPenjualans as $retur)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4 font-mono font-bold text-[#15235F]">
                                    <a href="{{ route('retur-penjualan.show', $retur) }}" class="hover:underline">
                                        {{ $retur->nomor_retur }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $retur->tanggal_retur ? $retur->tanggal_retur->format('d/m/Y') : '-' }}
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-rose-600 font-mono text-xs">
                                    Rp {{ number_format($retur->total, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-slate-700 font-mono text-xs">
                                    Rp {{ number_format($retur->potong_piutang, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-emerald-600 font-mono text-xs">
                                    Rp {{ number_format($retur->pengembalian_kas, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600 max-w-xs">
                                    {{ $retur->alasan ?? $retur->alasan_retur ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <a
                                        href="{{ route('retur-penjualan.pdf', $retur) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-100"
                                    >
                                        🖨️ Nota Retur PDF
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- HISTORI TINDAK LANJUT PENAGIHAN & SURAT PERINGATAN --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4 flex justify-between items-center">
            <div>
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Histori Tindak Lanjut Penagihan & Surat Peringatan</h3>
                <p class="text-xs text-slate-400">Rekam medis penagihan, janji bayar, dan pengiriman SP.</p>
            </div>

            <button
                type="button"
                @click="showTindakLanjutModal = true"
                class="rounded-lg bg-[#15235F] px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-[#101b4d]"
            >
                + Tambah Tindak Lanjut
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-6 py-3 font-medium">Tanggal</th>
                        <th class="px-6 py-3 font-medium">Jenis Tindak Lanjut</th>
                        <th class="px-6 py-3 font-medium">Janji Bayar</th>
                        <th class="px-6 py-3 font-medium">Catatan / Detail</th>
                        <th class="px-6 py-3 font-medium">Petugas</th>
                        <th class="px-6 py-3 text-right font-medium">Cetak Dokumen</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($invoice->tindakLanjuts as $tl)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 text-slate-600">
                                {{ $tl->tanggal_tindak_lanjut ? $tl->tanggal_tindak_lanjut->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 font-semibold">
                                @php
                                    $tlBadges = [
                                        'TELEPON_CUSTOMER' => 'bg-blue-50 text-blue-700',
                                        'JANJI_BAYAR' => 'bg-amber-50 text-amber-700',
                                        'SURAT_TAGIHAN' => 'bg-purple-50 text-purple-700',
                                        'SURAT_PERINGATAN_1' => 'bg-orange-50 text-orange-700',
                                        'SURAT_PERINGATAN_2' => 'bg-rose-50 text-rose-700',
                                        'SURAT_PERINGATAN_3' => 'bg-red-100 text-red-800 font-bold',
                                    ];
                                @endphp
                                <span class="inline-flex rounded-lg px-2.5 py-1 text-xs {{ $tlBadges[$tl->jenis_tindak_lanjut] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ str_replace('_', ' ', $tl->jenis_tindak_lanjut) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-700 font-medium">
                                {{ $tl->tanggal_janji_bayar ? $tl->tanggal_janji_bayar->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-600 max-w-xs">
                                {{ $tl->catatan }}
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                {{ $tl->creator?->name ?? 'Admin' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($tl->jenis_tindak_lanjut === 'SURAT_TAGIHAN')
                                    <a href="{{ route('piutang.surat-tagihan.pdf', $invoice) }}" target="_blank" class="text-xs font-semibold text-purple-700 hover:underline">
                                        🖨️ Cetak Surat Tagihan
                                    </a>
                                @elseif(in_array($tl->jenis_tindak_lanjut, ['SURAT_PERINGATAN_1', 'SURAT_PERINGATAN_2', 'SURAT_PERINGATAN_3']))
                                    @php $spLevel = substr($tl->jenis_tindak_lanjut, -1); @endphp
                                    <a href="{{ route('piutang.surat-peringatan.pdf', ['invoice' => $invoice->id, 'level' => $spLevel]) }}" target="_blank" class="text-xs font-bold text-rose-700 hover:underline">
                                        🖨️ Cetak SP-{{ $spLevel }}
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-6 text-center text-slate-400">
                                Belum ada riwayat tindak lanjut penagihan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL 1: FORM TERIMA PEMBAYARAN PIUTANG --}}
    <div
        x-show="showPaymentModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
    >
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl space-y-4">
            <div class="border-b border-slate-100 pb-3 flex justify-between items-center">
                <h3 class="text-base font-bold text-emerald-800">💵 Terima Pembayaran Piutang</h3>
                <button type="button" @click="showPaymentModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form action="{{ route('piutang.penerimaan.store') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="customer_id" value="{{ $invoice->customer_id }}">
                <input type="hidden" name="alokasi[0][invoice_id]" value="{{ $invoice->id }}">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-600">No. Kwitansi</label>
                        <input
                            type="text"
                            name="nomor_penerimaan"
                            value="KWT-{{ date('Ym') }}-{{ str_pad($invoice->id, 4, '0', STR_PAD_LEFT) }}"
                            required
                            class="mt-1 w-full rounded-xl border border-slate-200 p-2.5 text-sm font-mono"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-600">Tanggal Pembayaran</label>
                        <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" required class="mt-1 w-full rounded-xl border border-slate-200 p-2.5 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600">Rekening Kas/Bank Penerima</label>
                    <select name="rekening_kas_bank_id" required class="mt-1 w-full rounded-xl border border-slate-200 p-2.5 text-sm">
                        @foreach($rekeningKasBanks as $rk)
                            <option value="{{ $rk->id }}">{{ $rk->nama_rekening }} ({{ $rk->nomor_rekening }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600">
                        Nominal Dibayar (Sisa: Rp {{ number_format($invoice->sisa_piutang, 0, ',', '.') }})
                    </label>
                    <input
                        type="number"
                        name="alokasi[0][bayar]"
                        value="{{ $invoice->sisa_piutang }}"
                        max="{{ $invoice->sisa_piutang }}"
                        min="1"
                        step="100"
                        required
                        class="mt-1 w-full rounded-xl border border-slate-300 p-2.5 text-sm font-bold text-emerald-700"
                    >
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600">Catatan</label>
                    <input type="text" name="catatan" placeholder="Catatan pembayaran..." class="mt-1 w-full rounded-xl border border-slate-200 p-2.5 text-sm">
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showPaymentModal = false" class="rounded-xl border border-slate-200 px-4 py-2 text-sm">Batal</button>
                    <button type="submit" class="rounded-xl bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Simpan Pembayaran</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 2: FORM TINDAK LANJUT / SURAT PERINGATAN --}}
    <div
        x-show="showTindakLanjutModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
    >
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl space-y-4">
            <div class="border-b border-slate-100 pb-3 flex justify-between items-center">
                <h3 class="text-base font-bold text-[#15235F]">📝 Catat Tindak Lanjut Penagihan / SP</h3>
                <button type="button" @click="showTindakLanjutModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form action="{{ route('piutang.tindak-lanjut.store', $invoice) }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-600">Tanggal Tindak Lanjut</label>
                        <input type="date" name="tanggal_tindak_lanjut" value="{{ date('Y-m-d') }}" required class="mt-1 w-full rounded-xl border border-slate-200 p-2.5 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-600">Jenis Tindak Lanjut</label>
                        <select name="jenis_tindak_lanjut" required class="mt-1 w-full rounded-xl border border-slate-200 p-2.5 text-sm font-semibold">
                            <option value="CATATAN_PENAGIHAN">Catatan Penagihan Routine</option>
                            <option value="TELEPON_CUSTOMER">Telepon / WhatsApp Customer</option>
                            <option value="JANJI_BAYAR">Janji Bayar dari Customer</option>
                            <option value="SURAT_TAGIHAN">Penerbitan Surat Tagihan Resmi</option>
                            <option value="SURAT_PERINGATAN_1">Surat Peringatan 1 (SP-1)</option>
                            <option value="SURAT_PERINGATAN_2">Surat Peringatan 2 (SP-2)</option>
                            <option value="SURAT_PERINGATAN_3">Surat Peringatan 3 (SP-3)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600">Tanggal Janji Bayar (Opsional)</label>
                    <input type="date" name="tanggal_janji_bayar" class="mt-1 w-full rounded-xl border border-slate-200 p-2.5 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-600">Catatan Ringkas / Hasil Komunikasi</label>
                    <textarea name="catatan" rows="3" required placeholder="Tuliskan hasil pembicaraan atau alasan penagihan..." class="mt-1 w-full rounded-xl border border-slate-200 p-2.5 text-sm"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showTindakLanjutModal = false" class="rounded-xl border border-slate-200 px-4 py-2 text-sm">Batal</button>
                    <button type="submit" class="rounded-xl bg-[#15235F] px-5 py-2 text-sm font-semibold text-white hover:bg-[#101b4d]">Simpan Tindak Lanjut</button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
