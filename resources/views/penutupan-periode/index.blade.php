@extends('layouts.app')

@section('title', 'Penutupan Periode (Cut-off Bulanan)')

@section('content')

<div class="mx-auto max-w-5xl space-y-6" x-data="{ showReopenModal: false, selectedId: null, selectedMonth: '' }">

    {{-- HEADER --}}
    <div>
        <h2 class="text-xl font-semibold text-slate-800">
            Penutupan Periode Transaksi (Cut-off Bulanan)
        </h2>
        <p class="mt-1 text-sm text-slate-500">
            Kunci transaksi per bulan agar tidak dapat di-edit atau dihapus setelah laporan dikirim.
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
            <x-icon name="check" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-800">
            <x-icon name="alert-triangle" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> {{ session('warning') }}
        </div>
    @endif

    {{-- FORM CUT-OFF BARU --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <h3 class="text-sm font-bold uppercase tracking-wider text-[#15235F]">Form Tutup Transaksi Bulanan</h3>
        
        <form method="POST" action="{{ route('penutupan-periode.close') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            @csrf
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Bulan</label>
                <select name="bulan" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $m == date('n') ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::createFromDate(2026, $m, 1)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Tahun</label>
                <select name="tahun" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none">
                    @for($y = date('Y'); $y >= 2024; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div class="flex items-end">
                <button
                    type="submit"
                    onclick="return confirm('Apakah Anda yakin ingin menutup (Cut-off) periode ini? Transaksi pada bulan ini akan TERKUNCI.')"
                    class="w-full rounded-xl bg-rose-600 py-2 text-sm font-semibold text-white transition hover:bg-rose-700"
                >
                    <x-icon name="lock" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Kunci & Tutup Periode
                </button>
            </div>
        </form>
    </div>

    {{-- TABEL RIWAYAT PENUTUPAN PERIODE --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h3 class="text-sm font-semibold text-slate-700">Status Penutupan Periode</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-6 py-3 font-medium">Periode</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                        <th class="px-6 py-3 font-medium">Ditutup Oleh</th>
                        <th class="px-6 py-3 font-medium">Waktu Tutup</th>
                        <th class="px-6 py-3 font-medium">Keterangan / Audit Log</th>
                        <th class="px-6 py-3 text-center font-medium">Aksi Backup Plan</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($penutupans as $p)
                        @php
                            $namaBulan = \Carbon\Carbon::createFromDate($p->tahun, $p->bulan, 1)->translatedFormat('F Y');
                        @endphp
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-6 py-4 font-bold text-[#15235F]">
                                {{ $namaBulan }}
                            </td>
                            <td class="px-6 py-4">
                                @if($p->status === 'LOCKED')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">
                                        <x-icon name="lock" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> TERKUNCI (LOCKED)
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                        <x-icon name="lock-open" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> TERBUKA (OPEN)
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $p->closedBy->name ?? 'Admin' }}
                            </td>
                            <td class="px-6 py-4 text-slate-500 text-xs">
                                {{ $p->tanggal_penutupan ? $p->tanggal_penutupan->format('d/m/Y H:i') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-600 max-w-xs">
                                @if($p->status === 'OPEN' && $p->alasan_buka_kembali)
                                    <span class="text-amber-700 font-semibold">Dibuka Kembali:</span> {{ $p->alasan_buka_kembali }}
                                @else
                                    {{ $p->catatan ?: '-' }}
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($p->status === 'LOCKED')
                                    <button
                                        type="button"
                                        @click="showReopenModal = true; selectedId = {{ $p->id }}; selectedMonth = '{{ $namaBulan }}'"
                                        class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 transition hover:bg-amber-100"
                                    >
                                        <x-icon name="lock-open" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Buka Kembali Periode
                                    </button>
                                @else
                                    <span class="text-xs text-slate-400 italic">Periode Aktif</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                                Belum ada periode transaksi yang ditutup.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL BACKUP PLAN: BUKA KEMBALI PERIODE --}}
    <div
        x-show="showReopenModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
    >
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-amber-700"><x-icon name="lock-open" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Backup Plan: Buka Kembali Periode</h3>
                <p class="text-xs text-slate-500" x-text="'Membuka periode terkunci: ' + selectedMonth"></p>
            </div>

            <form :action="'/penutupan-periode/' + selectedId + '/reopen'" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Alasan Pembukaan Kembali (Audit Log Wajib) <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        name="alasan_buka_kembali"
                        rows="3"
                        required
                        placeholder="Jelaskan alasan darurat mengapa periode ini harus dibuka kembali untuk penyesuaian data..."
                        class="mt-1.5 w-full rounded-xl border border-slate-200 p-3 text-sm outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-100"
                    ></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="showReopenModal = false"
                        class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="rounded-xl bg-amber-600 px-5 py-2 text-sm font-semibold text-white hover:bg-amber-700"
                    >
                        Ya, Buka Periode
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
