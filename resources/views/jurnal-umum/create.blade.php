@extends('layouts.app')

@section('title', 'Buat Jurnal Umum')

@section('content')

<div class="mx-auto max-w-5xl space-y-6" x-data="jurnalForm()">

    <div>
        <a href="{{ route('jurnal-umum.index') }}" class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]">
            ← Kembali ke Daftar Jurnal
        </a>
        <h2 class="text-xl font-semibold text-slate-800">Buat Transaksi Jurnal Umum</h2>
        <p class="mt-1 text-sm text-slate-500">
            Catat ayat jurnal penyesuaian atau transaksi keuangan manual. Total Debit & Kredit wajib seimbang.
        </p>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <p class="font-semibold">Terjadi kesalahan pada input data:</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 font-semibold">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('jurnal-umum.store') }}" method="POST" class="space-y-6">
        @csrf

        {{-- HEADER INFO --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-700">Informasi Jurnal</h3>
            <div class="mt-4 grid grid-cols-1 gap-5 md:grid-cols-2">
                {{-- Nomor Jurnal --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Nomor Jurnal</label>
                    <input
                        type="text"
                        name="nomor_jurnal"
                        value="{{ old('nomor_jurnal', $nomorJurnal) }}"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-mono font-semibold text-[#15235F] focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                </div>

                {{-- Tanggal --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Tanggal Jurnal <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="date"
                        name="tanggal"
                        value="{{ old('tanggal', date('Y-m-d')) }}"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                </div>
            </div>

            {{-- Keterangan --}}
            <div class="mt-4">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                    Keterangan Transaksi <span class="text-rose-500">*</span>
                </label>
                <textarea
                    name="keterangan"
                    rows="2"
                    placeholder="Penjelasan ringkas transaksi jurnal..."
                    required
                    class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >{{ old('keterangan') }}</textarea>
            </div>
        </div>

        {{-- ITEMS TABLE --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-slate-700">Rincian Debet & Kredit Akun</h3>
                    <p class="mt-0.5 text-xs text-slate-400">Pilih akun dan tentukan posisi debit atau kredit.</p>
                </div>
                <button
                    type="button"
                    @click="addItem()"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-teal-50 px-4 py-2 text-xs font-semibold text-[#15235F] transition hover:bg-teal-100"
                >
                    + Tambah Baris
                </button>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-xs uppercase tracking-wide text-slate-400">
                            <th class="px-4 py-3 font-medium" style="width: 35%">Pilih Akun</th>
                            <th class="px-4 py-3 font-medium" style="width: 25%">Keterangan Baris</th>
                            <th class="px-4 py-3 text-right font-medium" style="width: 17%">Debit (Rp)</th>
                            <th class="px-4 py-3 text-right font-medium" style="width: 17%">Kredit (Rp)</th>
                            <th class="px-4 py-3 text-center" style="width: 6%"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(item, index) in items" :key="index">
                            <tr>
                                <td class="px-4 py-3">
                                    <select
                                        :name="`items[${index}][akun_id]`"
                                        x-model="item.akun_id"
                                        required
                                        class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                                    >
                                        <option value="">-- Pilih Akun COA --</option>
                                        @foreach($akuns as $akun)
                                            <option value="{{ $akun->id }}">
                                                {{ $akun->kode_akun }} - {{ $akun->nama_akun }} ({{ $akun->kategori_akun }})
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <input
                                        type="text"
                                        :name="`items[${index}][keterangan]`"
                                        x-model="item.keterangan"
                                        placeholder="Keterangan..."
                                        class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                                    >
                                </td>
                                <td class="px-4 py-3">
                                    <input
                                        type="number"
                                        :name="`items[${index}][debit]`"
                                        x-model.number="item.debit"
                                        @input="if(item.debit > 0) item.kredit = 0"
                                        min="0"
                                        step="100"
                                        class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-right text-sm font-semibold text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                                    >
                                </td>
                                <td class="px-4 py-3">
                                    <input
                                        type="number"
                                        :name="`items[${index}][kredit]`"
                                        x-model.number="item.kredit"
                                        @input="if(item.kredit > 0) item.debit = 0"
                                        min="0"
                                        step="100"
                                        class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-right text-sm font-semibold text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                                    >
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button
                                        type="button"
                                        @click="removeItem(index)"
                                        x-show="items.length > 2"
                                        class="text-rose-500 hover:text-rose-700"
                                    >
                                        ✕
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- TOTALS SUMMARY --}}
            <div class="mt-6 flex flex-col items-end border-t border-slate-100 pt-4">
                <div class="w-full max-w-md space-y-2 text-sm">
                    <div class="flex justify-between text-slate-600">
                        <span>Total Debit:</span>
                        <span class="font-bold text-slate-800" x-text="formatRupiah(calculateTotalDebit())"></span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Total Kredit:</span>
                        <span class="font-bold text-slate-800" x-text="formatRupiah(calculateTotalKredit())"></span>
                    </div>
                    <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold">
                        <span>Selisih (Out of Balance):</span>
                        <span :class="isBalanced() ? 'text-emerald-600' : 'text-rose-600'" x-text="formatRupiah(calculateSelisih())"></span>
                    </div>
                </div>

                <div x-show="!isBalanced()" class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-xs font-semibold text-rose-700">
                    ⚠️ Total Debit dan Kredit harus SEIMBANG sebelum transaksi disetujui.
                </div>
            </div>
        </div>

        {{-- ACTIONS --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('jurnal-umum.index') }}" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                Batal
            </a>
            <button
                type="submit"
                :disabled="!isBalanced() || calculateTotalDebit() <= 0"
                class="rounded-xl bg-[#15235F] px-7 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#101b4d] disabled:opacity-50"
            >
                Simpan & Posting Jurnal
            </button>
        </div>
    </form>

</div>

<script>
function jurnalForm() {
    return {
        items: [
            { akun_id: '', debit: 0, kredit: 0, keterangan: '' },
            { akun_id: '', debit: 0, kredit: 0, keterangan: '' }
        ],
        addItem() {
            this.items.push({ akun_id: '', debit: 0, kredit: 0, keterangan: '' });
        },
        removeItem(index) {
            this.items.splice(index, 1);
        },
        calculateTotalDebit() {
            return this.items.reduce((acc, item) => acc + (parseFloat(item.debit) || 0), 0);
        },
        calculateTotalKredit() {
            return this.items.reduce((acc, item) => acc + (parseFloat(item.kredit) || 0), 0);
        },
        calculateSelisih() {
            return Math.abs(this.calculateTotalDebit() - this.calculateTotalKredit());
        },
        isBalanced() {
            return this.calculateSelisih() < 0.01;
        },
        formatRupiah(num) {
            return 'Rp ' + (Math.round(num) || 0).toLocaleString('id-ID');
        }
    };
}
</script>

@endsection
