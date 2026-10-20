
@extends('layouts.app')

@section('title', 'Preview Import Master Barang')

@section('content')

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a
                href="{{ url('/barang/import') }}"
                class="mb-2 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]"
            >
                <x-icon name="arrow-left" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Kembali ke Import Barang
            </a>

            <h1 class="text-2xl font-semibold text-slate-800">
                Preview Import Master Barang
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Periksa data terlebih dahulu sebelum dimasukkan ke database AMBAJA.
            </p>
        </div>
    </div>

    {{-- ERROR SESSION --}}
    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4">
            <div class="flex gap-3">
                <div class="text-red-500 text-lg"><x-icon name="alert-triangle" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /></div>
                <div>
                    <p class="font-semibold text-red-700">
                        Import tidak dapat diproses
                    </p>
                    <p class="mt-1 text-sm text-red-600">
                        {{ session('error') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- SUMMARY --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">

        {{-- TOTAL --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                Total Data
            </p>
            <p class="mt-2 text-2xl font-semibold text-slate-800">
                {{ number_format($summary['total'] ?? 0, 0, ',', '.') }}
            </p>
            <p class="mt-1 text-xs text-slate-400">
                Data dari Excel
            </p>
        </div>

        {{-- NEW --}}
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-blue-500">
                Barang Baru
            </p>
            <p class="mt-2 text-2xl font-semibold text-blue-700">
                {{ number_format($summary['new'] ?? 0, 0, ',', '.') }}
            </p>
            <p class="mt-1 text-xs text-blue-500">
                Akan ditambahkan
            </p>
        </div>

        {{-- UPDATE --}}
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-amber-600">
                Akan Diupdate
            </p>
            <p class="mt-2 text-2xl font-semibold text-amber-700">
                {{ number_format($summary['update'] ?? 0, 0, ',', '.') }}
            </p>
            <p class="mt-1 text-xs text-amber-600">
                Kode sudah ada
            </p>
        </div>

        {{-- PRICE CHANGES --}}
        <div class="rounded-xl border border-orange-200 bg-orange-50 p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-orange-600">
                Harga Berubah
            </p>
            <p class="mt-2 text-2xl font-semibold text-orange-700">
                {{ number_format($summary['price_change'] ?? 0, 0, ',', '.') }}
            </p>
            <p class="mt-1 text-xs text-orange-600">
                Barang lama yang harga file-nya berbeda
            </p>
        </div>

        {{-- CATEGORY --}}
        <div class="rounded-xl border border-purple-200 bg-purple-50 p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-purple-500">
                Kategori
            </p>
            <p class="mt-2 text-2xl font-semibold text-purple-700">
                {{ number_format($summary['category_count'] ?? 0, 0, ',', '.') }}
            </p>
            <p class="mt-1 text-xs text-purple-500">
                Jenis barang
            </p>
        </div>

        {{-- ERROR --}}
        <div class="
            rounded-xl border
            {{ ($summary['error'] ?? 0) > 0
                ? 'border-red-200 bg-red-50'
                : 'border-emerald-200 bg-emerald-50'
            }}
            p-5 shadow-sm
        ">
            <p class="
                text-xs font-medium uppercase tracking-wide
                {{ ($summary['error'] ?? 0) > 0
                    ? 'text-red-500'
                    : 'text-emerald-500'
                }}
            ">
                Error
            </p>

            <p class="
                mt-2 text-2xl font-semibold
                {{ ($summary['error'] ?? 0) > 0
                    ? 'text-red-700'
                    : 'text-emerald-700'
                }}
            ">
                {{ number_format($summary['error'] ?? 0, 0, ',', '.') }}
            </p>

            <p class="
                mt-1 text-xs
                {{ ($summary['error'] ?? 0) > 0
                    ? 'text-red-500'
                    : 'text-emerald-500'
                }}
            ">
                {{ ($summary['error'] ?? 0) > 0 ? 'Perlu diperbaiki' : 'Data valid' }}
            </p>
        </div>

    </div>

    {{-- WARNING --}}
    @if(!empty($warnings))
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
            <div class="flex gap-3">
                <div class="text-amber-500 text-lg"><x-icon name="alert-triangle" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /></div>

                <div class="flex-1">
                    <h3 class="font-semibold text-amber-800">
                        Peringatan
                    </h3>

                    <div class="mt-2 space-y-1 text-sm text-amber-700">
                        @foreach($warnings as $warning)
                            @if(is_array($warning))
                                <p>{{ implode(' ', array_map('strval', $warning)) }}</p>
                            @else
                                <p>{{ $warning }}</p>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- PRICE UPDATE SAFETY --}}
    @if(($summary['price_change'] ?? 0) > 0)
        <div class="rounded-xl border border-orange-200 bg-orange-50 p-5">
            <div class="flex gap-3">
                <div class="text-orange-600 text-lg"><x-icon name="alert-triangle" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /></div>
                <div>
                    <h3 class="font-semibold text-orange-800">
                        Ada perubahan harga pada barang yang sudah terdaftar
                    </h3>
                    <p class="mt-2 text-sm leading-6 text-orange-700">
                        Ditemukan {{ number_format($summary['price_change'], 0, ',', '.') }} barang lama dengan harga pada file yang berbeda dari database.
                        Secara default, harga beli default dan harga jual barang lama <strong>tidak akan diubah</strong>.
                        Periksa perbandingan harga pada tabel. Jika memang ingin memperbarui harga, centang opsi khusus sebelum menjalankan import.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- ERRORS --}}
    @if(!empty($errors))
        <div class="overflow-hidden rounded-xl border border-red-200 bg-white shadow-sm">

            <div class="border-b border-red-200 bg-red-50 px-6 py-4">
                <h2 class="font-semibold text-red-700">
                    Data Tidak Valid
                </h2>

                <p class="mt-1 text-sm text-red-600">
                    Import belum dapat dilakukan karena terdapat data yang perlu diperbaiki.
                </p>
            </div>

            <div class="max-h-[400px] overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-white">
                        <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                            <th class="px-5 py-3">Baris Excel</th>
                            <th class="px-5 py-3">Kode</th>
                            <th class="px-5 py-3">Nama Barang</th>
                            <th class="px-5 py-3">Kesalahan</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @foreach($errors as $error)
                            <tr>
                                <td class="px-5 py-3 font-medium text-slate-700">
                                    {{ $error['line'] ?? '-' }}
                                </td>

                                <td class="px-5 py-3 font-medium text-[#15235F]">
                                    {{ $error['kode'] ?? '-' }}
                                </td>

                                <td class="px-5 py-3 text-slate-600">
                                    {{ $error['nama'] ?? '-' }}
                                </td>

                                <td class="px-5 py-3">
                                    <ul class="space-y-1 text-sm text-red-600">
                                        @foreach(($error['messages'] ?? []) as $message)
                                            <li>• {{ $message }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- PREVIEW TABLE --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-6 py-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                <div>
                    <h2 class="font-semibold text-slate-800">
                        Preview Data Barang
                    </h2>

                    <p class="mt-1 text-xs text-slate-400">
                        Menampilkan maksimal 100 baris pertama dari file Excel.
                    </p>
                </div>

                <div class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">
                    Total:
                    <span class="font-semibold text-slate-700">
                        {{ number_format($summary['total'] ?? 0, 0, ',', '.') }}
                    </span>
                    data
                </div>

            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1500px] text-sm">

                <thead class="bg-slate-50">
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-5 py-3">No.</th>
                        <th class="px-5 py-3">Kode Item</th>
                        <th class="px-5 py-3">Nama Item</th>
                        <th class="px-5 py-3">Jenis</th>
                        <th class="px-5 py-3 text-right">Stok</th>
                        <th class="px-5 py-3">Satuan</th>
                        <th class="px-5 py-3 text-right">Harga Beli Saat Ini</th>
                        <th class="px-5 py-3 text-right">Harga Beli dari File</th>
                        <th class="px-5 py-3 text-right">Harga Jual Saat Ini</th>
                        <th class="px-5 py-3 text-right">Harga Jual dari File</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($previewRows as $row)
                        <tr class="hover:bg-slate-50">

                            <td class="px-5 py-3 text-slate-500">
                                {{ $row['line'] ?? $loop->iteration }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-3 font-medium text-[#15235F]">
                                {{ $row['kode_barang'] ?? '-' }}
                            </td>

                            <td class="px-5 py-3 text-slate-700">
                                {{ $row['nama_barang'] ?? '-' }}
                            </td>

                            <td class="px-5 py-3 text-slate-500">
                                {{ $row['jenis'] ?? '-' }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-3 text-right text-slate-700">
                                {{ number_format((float) ($row['stok'] ?? 0), 2, ',', '.') }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-3 text-slate-500">
                                {{ $row['satuan'] ?? '-' }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-3 text-right {{ !empty($row['harga_beli_berubah']) ? 'bg-orange-50 font-semibold text-orange-800' : 'text-slate-500' }}">
                                @if(!empty($row['is_existing']))
                                    Rp {{ number_format((float) ($row['harga_beli_lama'] ?? 0), 0, ',', '.') }}
                                @else
                                    <span class="text-slate-400">— Barang baru</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-5 py-3 text-right font-medium {{ !empty($row['harga_beli_berubah']) ? 'bg-orange-50 text-orange-800' : 'text-slate-700' }}">
                                Rp {{ number_format((float) ($row['harga_pokok'] ?? 0), 0, ',', '.') }}
                                @if(!empty($row['harga_beli_berubah']))
                                    <span class="ml-1 text-xs">(berubah)</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-5 py-3 text-right {{ !empty($row['harga_jual_berubah']) ? 'bg-orange-50 font-semibold text-orange-800' : 'text-slate-500' }}">
                                @if(!empty($row['is_existing']))
                                    Rp {{ number_format((float) ($row['harga_jual_lama'] ?? 0), 0, ',', '.') }}
                                @else
                                    <span class="text-slate-400">— Barang baru</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-5 py-3 text-right font-medium {{ !empty($row['harga_jual_berubah']) ? 'bg-orange-50 text-orange-800' : 'text-slate-700' }}">
                                Rp {{ number_format((float) ($row['harga_jual'] ?? 0), 0, ',', '.') }}
                                @if(!empty($row['harga_jual_berubah']))
                                    <span class="ml-1 text-xs">(berubah)</span>
                                @endif
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-sm text-slate-400">
                                Tidak ada data untuk ditampilkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

    {{-- IMPORTANT INFORMATION --}}
    <div class="rounded-xl border border-blue-200 bg-blue-50 p-5">
        <div class="flex gap-3">
            <div class="text-blue-600 text-lg">ℹ</div>

            <div>
                <h3 class="font-semibold text-blue-800">
                    Mekanisme Import
                </h3>

                <div class="mt-2 space-y-1 text-sm leading-6 text-blue-700">
                    <p>• Kode barang yang sudah ada akan diperbarui pada kolom non-harga yang tersedia di file.</p>
                    <p>• Kode barang yang belum ada akan dibuat sebagai barang baru.</p>
                    <p>• Barang lama yang tidak terdapat pada Excel <strong>tidak akan dihapus</strong>.</p>
                    <p>• Kolom <strong>STOK</strong> pada Excel hanya ditampilkan sebagai informasi dan <strong>tidak mengubah stok gudang</strong>.</p>
                    <p>• Kategori dan satuan yang belum tersedia akan dibuat otomatis, tetapi master nonaktif tidak akan digunakan.</p>
                    <p>• Untuk saldo stok awal, gunakan menu <strong>Import Stok Awal</strong>.</p>
                    <p>• Jika terdapat error, proses import tidak dapat dilanjutkan.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ACTION --}}
    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

        {{-- BATAL --}}
        <a
            href="{{ url('/barang/import') }}"
            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
        >
            <x-icon name="arrow-left" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Upload Ulang
        </a>

        {{-- IMPORT --}}
        @if(($summary['error'] ?? 0) == 0)

            <form
                action="{{ route('barang.import.process') }}"
                method="POST"
                class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-end"
                onsubmit="return confirm(
                    'Yakin ingin memasukkan data master barang ini ke database?\n\n' +
                    'Data non-harga barang lama akan diperbarui. Harga barang lama hanya berubah jika opsi persetujuan harga dicentang.\n' +
                    'Barang dengan kode baru akan ditambahkan.\n\n' +
                    'Kolom STOK tidak akan mengubah saldo stok gudang. Gunakan Import Stok Awal untuk saldo persediaan.'
                );"
            >
                @csrf

                @if(($summary['price_change'] ?? 0) > 0)
                    <label class="mb-3 flex items-start gap-3 rounded-xl border border-orange-200 bg-orange-50 p-4 text-left sm:mb-0 sm:mr-3 sm:max-w-xl">
                        <input
                            type="checkbox"
                            name="update_existing_prices"
                            value="1"
                            class="mt-1 rounded border-orange-300 text-[#15235F] focus:ring-[#15235F]"
                        >
                        <span>
                            <span class="block text-sm font-semibold text-orange-900">
                                Saya menyetujui pembaruan harga barang lama
                            </span>
                            <span class="mt-1 block text-xs leading-5 text-orange-800">
                                Jika dicentang, harga beli default dan harga jual pada database akan diganti dengan nilai dari file untuk barang yang sudah ada. Jika tidak dicentang, harga barang lama dipertahankan.
                            </span>
                        </span>
                    </label>
                @endif

                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#101b4d] sm:w-auto"
                >
                    <x-icon name="check" class="h-4 w-4" />

                    Import {{ number_format($summary['total'] ?? 0, 0, ',', '.') }} Barang
                </button>
            </form>

        @else

            <button
                type="button"
                disabled
                class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-lg bg-slate-300 px-5 py-2.5 text-sm font-medium text-slate-500"
            >
                Import Tidak Tersedia
            </button>

        @endif

    </div>

</div>

@endsection