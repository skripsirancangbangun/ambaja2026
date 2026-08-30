@extends('layouts.app')

@section('title', 'Tambah Barang')

@section('content')

<div class="mx-auto max-w-4xl space-y-6">

    <div>
        <a
            href="{{ route('barang.index') }}"
            class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 transition hover:text-[#15235F]"
        >
            ← Kembali
        </a>

        <h2 class="text-xl font-semibold text-slate-800">
            Tambah Barang
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Tambahkan data barang baru.
        </p>
    </div>


    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">
            <p class="text-sm font-semibold text-red-700">
                Periksa kembali data yang diinput.
            </p>

            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs text-red-600">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <form
        action="{{ route('barang.store') }}"
        method="POST"
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
    >

        @csrf

        <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">

            {{-- KODE --}}
            <div>
                <label
                    for="kode_barang"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Kode Barang
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    id="kode_barang"
                    name="kode_barang"
                    value="{{ old('kode_barang') }}"
                    placeholder="Contoh: A0-00003"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    required
                >
            </div>


            {{-- NAMA --}}
            <div>
                <label
                    for="nama_barang"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Nama Barang
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    id="nama_barang"
                    name="nama_barang"
                    value="{{ old('nama_barang') }}"
                    placeholder="Nama barang"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    required
                >
            </div>


            {{-- KATEGORI --}}
            <div>
                <label
                    for="kategori_barang_id"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Kategori
                    <span class="text-red-500">*</span>
                </label>

                <select
                    id="kategori_barang_id"
                    name="kategori_barang_id"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    required
                >

                    <option value="">
                        Pilih kategori
                    </option>

                    @foreach($kategoris as $kategori)

                        <option
                            value="{{ $kategori->id }}"
                            {{ old('kategori_barang_id') == $kategori->id ? 'selected' : '' }}
                        >
                            {{ $kategori->nama_kategori }}
                        </option>

                    @endforeach

                </select>
            </div>


            {{-- SATUAN --}}
            <div>
                <label
                    for="satuan_id"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Satuan
                    <span class="text-red-500">*</span>
                </label>

                <select
                    id="satuan_id"
                    name="satuan_id"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    required
                >

                    <option value="">
                        Pilih satuan
                    </option>

                    @foreach($satuans as $satuan)

                        <option
                            value="{{ $satuan->id }}"
                            {{ old('satuan_id') == $satuan->id ? 'selected' : '' }}
                        >
                            {{ $satuan->nama_satuan }}
                        </option>

                    @endforeach

                </select>
            </div>


            {{-- SPESIFIKASI --}}
            <div>
                <label
                    for="spesifikasi"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Spesifikasi
                </label>

                <input
                    type="text"
                    id="spesifikasi"
                    name="spesifikasi"
                    value="{{ old('spesifikasi') }}"
                    placeholder="Contoh: 35 × 0,75 AZ100 Premium"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >
            </div>


            {{-- MEREK --}}
            <div>
                <label
                    for="merek"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Merek
                </label>

                <input
                    type="text"
                    id="merek"
                    name="merek"
                    value="{{ old('merek') }}"
                    placeholder="Opsional"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >
            </div>


            {{-- HARGA BELI --}}
            <div>
                <label
                    for="harga_beli_default"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Harga Beli Default
                    <span class="text-red-500">*</span>
                </label>

                <div class="relative">

                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400">
                        Rp
                    </span>

                    <input
                        type="number"
                        id="harga_beli_default"
                        name="harga_beli_default"
                        value="{{ old('harga_beli_default', 0) }}"
                        min="0"
                        step="0.01"
                        class="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-3.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                        required
                    >

                </div>

                <p class="mt-1.5 text-xs text-slate-400">
                    Digunakan sebagai harga referensi saat membuat Purchase Order.
                </p>
            </div>


            {{-- MARGIN --}}
            <div>
                <label
                    for="margin_persen"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Margin
                    <span class="text-red-500">*</span>
                </label>

                <div class="relative">

                    <input
                        type="number"
                        id="margin_persen"
                        name="margin_persen"
                        value="{{ old('margin_persen', 0) }}"
                        min="0"
                        step="0.01"
                        class="w-full rounded-lg border border-slate-300 py-2.5 pl-3.5 pr-10 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                        required
                    >

                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400">
                        %
                    </span>

                </div>

                <p class="mt-1.5 text-xs text-slate-400">
                    Persentase margin dari harga beli.
                </p>
            </div>


            {{-- MINIMUM STOK --}}
            <div>
                <label
                    for="minimum_stok"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Minimum Stok
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="number"
                    id="minimum_stok"
                    name="minimum_stok"
                    value="{{ old('minimum_stok', 0) }}"
                    min="0"
                    step="0.01"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    required
                >

                <p class="mt-1.5 text-xs text-slate-400">
                    Digunakan untuk peringatan stok menipis.
                </p>
            </div>


            {{-- HARGA JUAL --}}
            <div>
                <label
                    for="harga_jual_preview"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Harga Jual
                </label>

                <div class="relative">

                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400">
                        Rp
                    </span>

                    <input
                        type="text"
                        id="harga_jual_preview"
                        value="0"
                        class="w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-3.5 text-sm font-semibold text-[#15235F] outline-none"
                        readonly
                    >

                </div>

                <p class="mt-1.5 text-xs text-slate-400">
                    Dihitung otomatis dari harga beli + margin.
                </p>
            </div>


            {{-- STATUS --}}
            <div class="md:col-span-2">

                <label
                    for="is_active"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Status
                </label>

                <select
                    id="is_active"
                    name="is_active"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >

                    <option
                        value="1"
                        {{ old('is_active', '1') == '1' ? 'selected' : '' }}
                    >
                        Aktif
                    </option>

                    <option
                        value="0"
                        {{ old('is_active') == '0' ? 'selected' : '' }}
                    >
                        Nonaktif
                    </option>

                </select>

            </div>

        </div>


        {{-- ACTION --}}
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">

            <a
                href="{{ route('barang.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
            >
                Batal
            </a>

            <button
                type="submit"
                class="rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
            >
                Simpan Barang
            </button>

        </div>

    </form>

</div>


<script>
    const hargaBeliInput =
        document.getElementById('harga_beli_default');

    const marginInput =
        document.getElementById('margin_persen');

    const hargaJualPreview =
        document.getElementById('harga_jual_preview');


    function formatRupiah(value) {
        return new Intl.NumberFormat('id-ID', {
            maximumFractionDigits: 2
        }).format(value || 0);
    }


    function hitungHargaJual() {

        const hargaBeli =
            parseFloat(hargaBeliInput.value) || 0;

        const margin =
            parseFloat(marginInput.value) || 0;


        const hargaJual =
            hargaBeli + (hargaBeli * margin / 100);


        hargaJualPreview.value =
            formatRupiah(hargaJual);
    }


    hargaBeliInput.addEventListener(
        'input',
        hitungHargaJual
    );

    marginInput.addEventListener(
        'input',
        hitungHargaJual
    );


    hitungHargaJual();
</script>

@endsection