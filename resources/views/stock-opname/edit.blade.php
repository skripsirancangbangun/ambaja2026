@extends('layouts.app')

@section('title', 'Isi Stock Opname')

@section('content')

<div class="mx-auto max-w-6xl space-y-6">

    <div>

        <a
            href="{{ route('stock-opname.show', $stockOpname) }}"
            class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]"
        >
            ← Kembali
        </a>

        <h2 class="text-xl font-semibold text-slate-800">
            Isi Stock Opname
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            {{ $stockOpname->nomor_opname }}
            ·
            {{ $stockOpname->gudang?->nama_gudang }}
        </p>

    </div>


    @if($errors->any())

        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">

            <ul class="list-disc space-y-1 pl-5 text-xs text-red-600">

                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach

            </ul>

        </div>

    @endif


    <form
        action="{{ route('stock-opname.update', $stockOpname) }}"
        method="POST"
        class="space-y-6"
    >

        @csrf
        @method('PUT')


        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-4">

                <h3 class="text-sm font-semibold text-slate-700">
                    Hasil Perhitungan Fisik
                </h3>

                <p class="mt-1 text-xs text-slate-400">
                    Masukkan stok fisik yang ditemukan pada gudang.
                </p>

            </div>


            <div class="overflow-x-auto">

                <table class="w-full min-w-[1100px] text-sm">

                    <thead class="bg-slate-50">

                        <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                            <th class="px-5 py-3">
                                Barang
                            </th>

                            <th class="px-5 py-3 text-right">
                                Stok Sistem
                            </th>

                            <th class="px-5 py-3">
                                Satuan
                            </th>

                            <th class="px-5 py-3">
                                Stok Fisik
                            </th>

                            <th class="px-5 py-3 text-right">
                                Selisih
                            </th>

                            <th class="px-5 py-3">
                                Keterangan
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100">

                        @foreach($stockOpname->items as $item)

                            <tr class="opname-row">

                                <td class="px-5 py-4">

                                    <input
                                        type="hidden"
                                        name="items[{{ $loop->index }}][id]"
                                        value="{{ $item->id }}"
                                    >

                                    <p class="font-semibold text-slate-700">
                                        {{ $item->barang?->kode_barang }}
                                        -
                                        {{ $item->barang?->nama_barang }}
                                    </p>

                                    @if($item->barang?->spesifikasi)

                                        <p class="mt-1 text-xs text-slate-400">
                                            {{ $item->barang->spesifikasi }}
                                        </p>

                                    @endif

                                </td>


                                <td
                                    class="stok-sistem px-5 py-4 text-right font-semibold text-slate-700"
                                    data-value="{{ (float) $item->stok_sistem }}"
                                >
                                    {{ number_format(
                                        $item->stok_sistem,
                                        2,
                                        ',',
                                        '.'
                                    ) }}
                                </td>


                                <td class="px-5 py-4 text-slate-500">

                                    {{ $item->barang?->satuan?->nama_satuan ?? '-' }}

                                </td>


                                <td class="px-5 py-4">

                                    <input
                                        type="number"
                                        name="items[{{ $loop->index }}][stok_fisik]"
                                        value="{{ old(
                                            'items.' . $loop->index . '.stok_fisik',
                                            $item->stok_fisik
                                        ) }}"
                                        min="0"
                                        step="0.01"
                                        class="stok-fisik w-36 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                                        required
                                    >

                                </td>


                                <td class="selisih px-5 py-4 text-right font-semibold text-slate-500">
                                    0
                                </td>


                                <td class="px-5 py-4">

                                    <input
                                        type="text"
                                        name="items[{{ $loop->index }}][keterangan]"
                                        value="{{ old(
                                            'items.' . $loop->index . '.keterangan',
                                            $item->keterangan
                                        ) }}"
                                        placeholder="Opsional"
                                        class="w-52 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                                    >

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>


        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <label class="mb-2 block text-sm font-medium text-slate-700">
                Catatan
            </label>

            <textarea
                name="catatan"
                rows="4"
                class="w-full resize-none rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
            >{{ old('catatan', $stockOpname->catatan) }}</textarea>

        </div>


        <div class="flex justify-end gap-3">

            <a
                href="{{ route('stock-opname.show', $stockOpname) }}"
                class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100"
            >
                Batal
            </a>

            <button
                type="submit"
                onclick="return confirm('Selesaikan Stock Opname? Setelah selesai data fisik tidak dapat diedit lagi.')"
                class="rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#101b4d]"
            >
                Selesaikan Opname
            </button>

        </div>

    </form>

</div>


<script>
    function formatQty(value) {
        return new Intl.NumberFormat(
            'id-ID',
            {
                maximumFractionDigits: 2,
                minimumFractionDigits: 0
            }
        ).format(value);
    }


    function updateSelisih(row) {

        const sistem =
            parseFloat(
                row.querySelector(
                    '.stok-sistem'
                ).dataset.value
            ) || 0;

        const fisik =
            parseFloat(
                row.querySelector(
                    '.stok-fisik'
                ).value
            ) || 0;

        const selisih =
            fisik - sistem;

        const element =
            row.querySelector(
                '.selisih'
            );

        element.textContent =
            (
                selisih > 0
                    ? '+'
                    : ''
            )
            + formatQty(selisih);

        element.classList.remove(
            'text-slate-500',
            'text-emerald-600',
            'text-red-500'
        );

        if (selisih > 0) {

            element.classList.add(
                'text-emerald-600'
            );

        } else if (selisih < 0) {

            element.classList.add(
                'text-red-500'
            );

        } else {

            element.classList.add(
                'text-slate-500'
            );
        }
    }


    document
        .querySelectorAll('.opname-row')
        .forEach(row => {

            updateSelisih(row);

            row
                .querySelector('.stok-fisik')
                .addEventListener(
                    'input',
                    () => updateSelisih(row)
                );
        });
</script>

@endsection