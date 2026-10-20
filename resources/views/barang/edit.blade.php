@extends('layouts.app')

@section('title', 'Edit Barang')

@section('content')

<div class="mx-auto max-w-4xl space-y-6">

    <div>

        <a
            href="{{ route('barang.index') }}"
            class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 transition hover:text-[#15235F]"
        >
            <x-icon name="arrow-left" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Kembali
        </a>

        <h2 class="text-xl font-semibold text-slate-800">
            Edit Barang
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Perbarui informasi barang.
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
        action="{{ route('barang.update', $barang) }}"
        method="POST"
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
    >

        @csrf
        @method('PUT')


        <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">


            {{-- KODE BARANG --}}
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
                    value="{{ old('kode_barang', $barang->kode_barang) }}"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    required
                >

            </div>


            {{-- NAMA BARANG --}}
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
                    value="{{ old('nama_barang', $barang->nama_barang) }}"
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

                    @foreach($kategoris as $kategori)

                        <option
                            value="{{ $kategori->id }}"
                            {{
                                old(
                                    'kategori_barang_id',
                                    $barang->kategori_barang_id
                                ) == $kategori->id
                                    ? 'selected'
                                    : ''
                            }}
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

                    @foreach($satuans as $satuan)

                        <option
                            value="{{ $satuan->id }}"
                            {{
                                old(
                                    'satuan_id',
                                    $barang->satuan_id
                                ) == $satuan->id
                                    ? 'selected'
                                    : ''
                            }}
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
                    value="{{ old('spesifikasi', $barang->spesifikasi) }}"
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
                    value="{{ old('merek', $barang->merek) }}"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >

            </div>


            {{-- HARGA BELI DEFAULT --}}
            <div>

                <label
                    for="harga_beli_default"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Harga Beli Default
                    <span class="text-red-500">*</span>
                </label>

                <div class="relative">

                    <span
                        class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"
                    >
                        Rp
                    </span>

                    <input
                        type="number"
                        id="harga_beli_default"
                        name="harga_beli_default"
                        value="{{ old(
                            'harga_beli_default',
                            $barang->harga_beli_default
                        ) }}"
                        min="0"
                        step="0.01"
                        class="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-3.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                        required
                    >

                </div>

                <p class="mt-1.5 text-xs text-slate-400">
                    Harga referensi pembelian/default saat membuat Purchase Order.
                    Tidak sama dengan HPP aktual.
                </p>

            </div>


            {{-- HPP AKTUAL --}}
            <div>

                <label
                    for="hpp_aktual"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    HPP Aktual
                </label>

                <div class="relative">

                    <span
                        class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"
                    >
                        Rp
                    </span>

                    <input
                        type="text"
                        id="hpp_aktual"
                        value="{{ number_format(
                            $hppAktual,
                            2,
                            ',',
                            '.'
                        ) }}"
                        class="w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-3.5 text-sm font-semibold text-slate-700 outline-none"
                        readonly
                    >

                </div>

                @if($stokQtyTotal > 0)

                    <p class="mt-1.5 text-xs text-slate-400">
                        Moving average gabungan stok saat ini.
                        Total stok:
                        {{ number_format(
                            $stokQtyTotal,
                            2,
                            ',',
                            '.'
                        ) }}.
                    </p>

                @else

                    <p class="mt-1.5 text-xs text-slate-400">
                        Belum ada stok. Untuk sementara menggunakan Harga Beli Default sebagai referensi.
                    </p>

                @endif

            </div>


            {{-- TARGET MARKUP --}}
            <div>

                <label
                    for="margin_persen"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Target Markup
                    <span class="text-red-500">*</span>
                </label>

                <div class="relative">

                    <input
                        type="number"
                        id="margin_persen"
                        name="margin_persen"
                        value="{{ old(
                            'margin_persen',
                            $barang->margin_persen
                        ) }}"
                        min="0"
                        max="999.99"
                        step="0.01"
                        class="w-full rounded-lg border border-slate-300 py-2.5 pl-3.5 pr-10 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                        required
                    >

                    <span
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"
                    >
                        %
                    </span>

                </div>

                <p class="mt-1.5 text-xs font-medium text-emerald-600">
                    Nilai ini adalah setting target dan tidak berubah otomatis ketika harga pembelian/HPP berubah.
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
                    value="{{ old(
                        'minimum_stok',
                        $barang->minimum_stok
                    ) }}"
                    min="0"
                    step="0.01"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    required
                >

                <p class="mt-1.5 text-xs text-slate-400">
                    Digunakan untuk peringatan stok menipis.
                </p>

            </div>


            {{-- HARGA JUAL SARAN --}}
            <div>

                <label
                    for="harga_jual_saran"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Harga Jual Saran
                </label>

                <div class="relative">

                    <span
                        class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"
                    >
                        Rp
                    </span>

                    <input
                        type="text"
                        id="harga_jual_saran"
                        value="{{ number_format(
                            $hargaJualSaran,
                            2,
                            ',',
                            '.'
                        ) }}"
                        class="w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-3.5 text-sm font-semibold text-[#15235F] outline-none"
                        readonly
                    >

                </div>

                <p class="mt-1.5 text-xs text-slate-400">
                    HPP aktual + Target Markup.
                    Perubahan angka ini tidak otomatis mengubah Harga Jual Aktif.
                </p>

            </div>


            {{-- HARGA JUAL AKTIF --}}
            <div>

                <div class="flex items-end justify-between gap-3">

                    <label
                        for="harga_jual"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Harga Jual Aktif
                        <span class="text-red-500">*</span>
                    </label>

                    <button
                        type="button"
                        id="gunakan_harga_saran"
                        class="mb-2 text-xs font-semibold text-[#15235F] transition hover:text-[#32AAA6]"
                    >
                        Gunakan Harga Saran
                    </button>

                </div>

                <div class="relative">

                    <span
                        class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"
                    >
                        Rp
                    </span>

                    <input
                        type="number"
                        id="harga_jual"
                        name="harga_jual"
                        value="{{ old(
                            'harga_jual',
                            $barang->harga_jual
                        ) }}"
                        min="0"
                        step="0.01"
                        class="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-3.5 text-sm font-semibold text-[#15235F] outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                        required
                    >

                </div>

                <p class="mt-1.5 text-xs text-slate-400">
                    Harga ini tidak berubah sampai user mengubahnya atau menekan Gunakan Harga Saran.
                </p>

            </div>


            {{-- INFORMASI MARKUP AKTUAL --}}
            <div class="md:col-span-2">

                <div
                    class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3"
                >

                    <div
                        class="flex flex-wrap items-center justify-between gap-3"
                    >

                        <div>

                            <p class="text-sm font-medium text-slate-700">
                                Markup Aktual Harga Jual
                            </p>

                            <p class="mt-0.5 text-xs text-slate-400">
                                Hanya informasi berdasarkan Harga Jual Aktif dibanding HPP aktual.
                                Angka ini boleh berubah saat HPP berubah dan bukan Target Markup.
                            </p>

                        </div>

                        <div
                            id="markup_aktual"
                            class="text-lg font-semibold text-[#15235F]"
                        >
                            -
                        </div>

                    </div>

                </div>

                {{-- PERINGATAN HPP / MARGIN --}}
                <div
                    id="peringatan_harga"
                    class="hidden rounded-xl border px-4 py-3"
                >
                    <p
                        id="peringatan_harga_judul"
                        class="text-sm font-semibold"
                    ></p>

                    <p
                        id="peringatan_harga_text"
                        class="mt-1 text-xs"
                    ></p>
                </div>

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
                        {{
                            old(
                                'is_active',
                                $barang->is_active
                                    ? '1'
                                    : '0'
                            ) == '1'
                                ? 'selected'
                                : ''
                        }}
                    >
                        Aktif
                    </option>

                    <option
                        value="0"
                        {{
                            old(
                                'is_active',
                                $barang->is_active
                                    ? '1'
                                    : '0'
                            ) == '0'
                                ? 'selected'
                                : ''
                        }}
                    >
                        Nonaktif
                    </option>

                </select>

            </div>


        </div>


        {{-- ACTION --}}
        <div
            class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4"
        >

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
                Simpan Perubahan
            </button>

        </div>


    </form>

</div>


<script>
document.addEventListener(
    'DOMContentLoaded',
    function () {

        const hargaBeliInput =
            document.getElementById(
                'harga_beli_default'
            );

        const markupInput =
            document.getElementById(
                'margin_persen'
            );

        const hargaJualInput =
            document.getElementById(
                'harga_jual'
            );

        const hargaJualSaranInput =
            document.getElementById(
                'harga_jual_saran'
            );

        const gunakanHargaSaranButton =
            document.getElementById(
                'gunakan_harga_saran'
            );

        const markupAktualText =
            document.getElementById(
                'markup_aktual'
            );

        const peringatanHarga =
            document.getElementById(
                'peringatan_harga'
            );

        const peringatanHargaJudul =
            document.getElementById(
                'peringatan_harga_judul'
            );

        const peringatanHargaText =
            document.getElementById(
                'peringatan_harga_text'
            );


        /*
         * HPP saat halaman dibuka.
         *
         * HPP tidak diubah oleh form master barang.
         */
        const hppAktual =
            Number(
                @json((float) $hppAktual)
            );


        const stokQtyTotal =
            Number(
                @json((float) $stokQtyTotal)
            );


        function angka(value) {

            const hasil =
                parseFloat(value);

            return Number.isFinite(hasil)
                ? hasil
                : 0;
        }


        function formatRupiah(value) {

            return new Intl.NumberFormat(
                'id-ID',
                {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2,
                }
            ).format(
                angka(value)
            );
        }


        /*
         * Kalau barang sudah memiliki stok:
         * saran harga menggunakan HPP Moving Average.
         *
         * Kalau belum ada stok:
         * gunakan Harga Beli Default.
         */
        function getDasarHarga() {

            if (
                stokQtyTotal > 0
            ) {
                return hppAktual;
            }

            return angka(
                hargaBeliInput.value
            );
        }


        function hitungHargaSaran() {

            const hargaDasar =
                getDasarHarga();

            const targetMarkup =
                angka(
                    markupInput.value
                );

            const hargaSaran =
                hargaDasar
                +
                (
                    hargaDasar
                    * targetMarkup
                    / 100
                );

            hargaJualSaranInput.value =
                formatRupiah(
                    hargaSaran
                );

            hargaJualSaranInput.dataset.nilai =
                hargaSaran.toFixed(2);

            return hargaSaran;
        }


        function hitungMarkupAktual() {

            const hargaDasar =
                getDasarHarga();

            const hargaJual =
                angka(
                    hargaJualInput.value
                );

            const targetMarkup =
                angka(
                    markupInput.value
                );

            if (
                hargaDasar <= 0
            ) {

                markupAktualText.textContent =
                    '-';

                updatePeringatanHarga(
                    hargaDasar,
                    hargaJual,
                    null,
                    targetMarkup
                );

                return;
            }

            const markupAktual =
                (
                    (
                        hargaJual
                        - hargaDasar
                    )
                    / hargaDasar
                )
                * 100;

            markupAktualText.textContent =
                `${markupAktual.toFixed(2)}%`;

            updatePeringatanHarga(
                hargaDasar,
                hargaJual,
                markupAktual,
                targetMarkup
            );
        }


        function updatePeringatanHarga(
            hargaDasar,
            hargaJual,
            markupAktual,
            targetMarkup
        ) {

            if (
                !peringatanHarga
                || !peringatanHargaJudul
                || !peringatanHargaText
            ) {
                return;
            }

            peringatanHarga.className =
                'hidden rounded-xl border px-4 py-3';

            peringatanHargaJudul.textContent =
                '';

            peringatanHargaText.textContent =
                '';

            if (
                hargaDasar <= 0
                || markupAktual === null
            ) {
                return;
            }

            /*
             * Kondisi 1:
             * Harga jual berada di bawah HPP.
             */
            if (
                hargaJual < hargaDasar
            ) {

                peringatanHarga.className =
                    'rounded-xl border border-red-200 bg-red-50 px-4 py-3';

                peringatanHargaJudul.className =
                    'text-sm font-semibold text-red-700';

                peringatanHargaText.className =
                    'mt-1 text-xs text-red-600';

                peringatanHargaJudul.textContent =
                    'Harga jual berada di bawah HPP.';

                peringatanHargaText.textContent =
                    `Harga jual Rp ${formatRupiah(hargaJual)} lebih rendah dari HPP Rp ${formatRupiah(hargaDasar)}. Barang berpotensi menghasilkan rugi kotor.`;

                return;
            }

            /*
             * Kondisi 2:
             * Harga jual masih di atas HPP,
             * tetapi markup aktual di bawah target.
             */
            if (
                markupAktual < targetMarkup
            ) {

                peringatanHarga.className =
                    'rounded-xl border border-amber-200 bg-amber-50 px-4 py-3';

                peringatanHargaJudul.className =
                    'text-sm font-semibold text-amber-700';

                peringatanHargaText.className =
                    'mt-1 text-xs text-amber-600';

                peringatanHargaJudul.textContent =
                    'Markup aktual berada di bawah target.';

                peringatanHargaText.textContent =
                    `Markup aktual ${markupAktual.toFixed(2)}%, sedangkan target markup ${targetMarkup.toFixed(2)}%. Periksa kembali harga jual jika diperlukan.`;

                return;
            }
        }



        /*
         * Perubahan Harga Beli Default
         * hanya berpengaruh ke saran harga
         * jika barang belum memiliki stok.
         */
        hargaBeliInput.addEventListener(
            'input',
            function () {

                hitungHargaSaran();

                hitungMarkupAktual();
            }
        );


        /*
         * Target markup berubah:
         * HANYA update saran.
         *
         * Harga jual aktif tidak disentuh.
         */
        markupInput.addEventListener(
            'input',
            function () {

                hitungHargaSaran();

                hitungMarkupAktual();
            }
        );


        /*
         * Harga jual user berubah:
         * hitung informasi markup aktual.
         */
        hargaJualInput.addEventListener(
            'input',
            hitungMarkupAktual
        );


        /*
         * Harga jual aktif baru berubah
         * ketika user menekan tombol ini.
         */
        gunakanHargaSaranButton.addEventListener(
            'click',
            function () {

                const hargaSaran =
                    angka(
                        hargaJualSaranInput
                            .dataset
                            .nilai
                    );

                hargaJualInput.value =
                    hargaSaran.toFixed(2);

                hitungMarkupAktual();
            }
        );


        hitungHargaSaran();

        hitungMarkupAktual();
    }
);
</script>

@endsection