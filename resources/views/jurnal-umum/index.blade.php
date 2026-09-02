@extends('layouts.app')

@section('title', 'Jurnal Umum')

@section('content')

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Jurnal Umum & Penyesuaian</h2>
            <p class="mt-1 text-sm text-slate-500">
                Pencatatan jurnal umum manual dan penyesuaian transaksi akuntansi.
            </p>
        </div>

        <a
            href="{{ route('jurnal-umum.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
        >
            <span>+</span>
            Buat Jurnal Baru
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('jurnal-umum.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Pencarian</label>
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="No. Jurnal atau Keterangan..."
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Dari Tanggal</label>
                <input
                    type="date"
                    name="tanggal_mulai"
                    value="{{ request('tanggal_mulai') }}"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Sampai Tanggal</label>
                <div class="mt-1.5 flex gap-2">
                    <input
                        type="date"
                        name="tanggal_selesai"
                        value="{{ request('tanggal_selesai') }}"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                    <button
                        type="submit"
                        class="rounded-xl bg-[#15235F] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#101b4d]"
                    >
                        Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- TABLE --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h3 class="text-sm font-semibold text-slate-700">Daftar Transaksi Jurnal</h3>
            <p class="mt-1 text-xs text-slate-400">Total {{ $jurnals->total() }} entry jurnal.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-6 py-3 font-medium">No. Jurnal</th>
                        <th class="px-6 py-3 font-medium">Tanggal</th>
                        <th class="px-6 py-3 font-medium">Keterangan</th>
                        <th class="px-6 py-3 text-right font-medium">Total Debit</th>
                        <th class="px-6 py-3 text-right font-medium">Total Kredit</th>
                        <th class="px-6 py-3 font-medium">Dibuat Oleh</th>
                        <th class="px-6 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($jurnals as $jurnal)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-6 py-4 font-mono font-semibold text-[#15235F]">
                                <a href="{{ route('jurnal-umum.show', $jurnal) }}" class="hover:underline">
                                    {{ $jurnal->nomor_jurnal }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $jurnal->tanggal ? $jurnal->tanggal->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-800">
                                {{ $jurnal->keterangan }}
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-slate-800">
                                Rp {{ number_format($jurnal->total_debit, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-slate-800">
                                Rp {{ number_format($jurnal->total_kredit, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                {{ $jurnal->creator->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a
                                    href="{{ route('jurnal-umum.show', $jurnal) }}"
                                    class="rounded-lg bg-teal-50 px-3 py-1.5 text-xs font-medium text-[#15235F] transition hover:bg-teal-100"
                                >
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400">
                                Belum ada transaksi jurnal umum tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($jurnals->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $jurnals->links() }}
            </div>
        @endif
    </div>

</div>

@endsection
