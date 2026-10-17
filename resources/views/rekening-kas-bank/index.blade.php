@extends('layouts.app')

@section('title', 'Kas & Rekening Bank')

@section('content')

<div class="min-h-screen bg-slate-50">

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">


        {{-- HEADER --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h1 class="text-2xl font-bold text-slate-900">
                    Kas & Rekening Bank
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Kelola Kas Kecil dan rekening bank perusahaan.
                </p>

            </div>


            <a
                href="{{ route('rekening-kas-bank.create') }}"
                class="inline-flex items-center justify-center rounded-lg bg-[#15235F] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90"
            >
                + Tambah Rekening
            </a>

        </div>


        {{-- SUCCESS --}}
        @if(session('success'))

            <div
                class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
            >
                {{ session('success') }}
            </div>

        @endif


        {{-- ERROR --}}
        @if(session('error'))

            <div
                class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
            >
                {{ session('error') }}
            </div>

        @endif


        {{-- FILTER --}}
        <div
            class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
        >

            <form
                method="GET"
                action="{{ route('rekening-kas-bank.index') }}"
            >

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">


                    {{-- SEARCH --}}
                    <div>

                        <label
                            class="mb-1 block text-sm font-medium text-slate-700"
                        >
                            Pencarian
                        </label>

                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Kode, rekening, bank..."
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-[#32AAA6]/20"
                        >

                    </div>


                    {{-- JENIS --}}
                    <div>

                        <label
                            class="mb-1 block text-sm font-medium text-slate-700"
                        >
                            Jenis
                        </label>

                        <select
                            name="jenis"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-[#32AAA6]/20"
                        >

                            <option value="">
                                Semua Jenis
                            </option>

                            <option
                                value="KAS"
                                @selected(
                                    request('jenis') === 'KAS'
                                )
                            >
                                Kas
                            </option>

                            <option
                                value="BANK"
                                @selected(
                                    request('jenis') === 'BANK'
                                )
                            >
                                Bank
                            </option>

                        </select>

                    </div>


                    {{-- STATUS --}}
                    <div>

                        <label
                            class="mb-1 block text-sm font-medium text-slate-700"
                        >
                            Status
                        </label>

                        <select
                            name="status"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-[#32AAA6]/20"
                        >

                            <option value="">
                                Semua Status
                            </option>

                            <option
                                value="aktif"
                                @selected(
                                    request('status') === 'aktif'
                                )
                            >
                                Aktif
                            </option>

                            <option
                                value="nonaktif"
                                @selected(
                                    request('status') === 'nonaktif'
                                )
                            >
                                Nonaktif
                            </option>

                        </select>

                    </div>

                </div>


                <div class="mt-4 flex flex-wrap gap-2">

                    <button
                        type="submit"
                        class="rounded-lg bg-[#15235F] px-5 py-2 text-sm font-semibold text-white"
                    >
                        Terapkan Filter
                    </button>


                    <a
                        href="{{ route('rekening-kas-bank.index') }}"
                        class="rounded-lg border border-slate-300 bg-white px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                    >
                        Reset
                    </a>

                </div>

            </form>

        </div>


        {{-- INFO SALDO --}}
        <div
            class="mb-4 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3"
        >

            <p class="text-sm font-semibold text-blue-800">
                Saldo Saat Ini
            </p>

            <p class="mt-1 text-xs leading-relaxed text-blue-700">
                Saldo berjalan dihitung dari Saldo Awal + seluruh Uang Masuk − seluruh Uang Keluar.
                Saldo ini digunakan sebagai jumlah dana yang tersedia untuk transaksi.
            </p>

        </div>


        {{-- TABLE --}}
        <div
            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
        >

            <div class="overflow-x-auto">

                <table
                    class="min-w-full divide-y divide-slate-200"
                >

                    <thead class="bg-slate-100">

                        <tr>

                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600"
                            >
                                No
                            </th>

                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600"
                            >
                                Kode
                            </th>

                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600"
                            >
                                Nama Rekening
                            </th>

                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600"
                            >
                                Jenis
                            </th>

                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600"
                            >
                                Bank / No Rekening
                            </th>

                            <th
                                class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600"
                            >
                                Saldo Awal
                            </th>

                            <th
                                class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600"
                            >
                                Saldo Saat Ini
                            </th>

                            <th
                                class="px-4 py-3 text-center text-xs font-semibold uppercase text-slate-600"
                            >
                                Status
                            </th>

                            <th
                                class="px-4 py-3 text-center text-xs font-semibold uppercase text-slate-600"
                            >
                                Aksi
                            </th>

                        </tr>

                    </thead>


                    <tbody
                        class="divide-y divide-slate-100 bg-white"
                    >

                        @forelse($rekeningKasBanks as $rekening)

                            <tr class="hover:bg-slate-50">


                                {{-- NO --}}
                                <td
                                    class="px-4 py-3 text-sm text-slate-600"
                                >

                                    {{
                                        $rekeningKasBanks->firstItem()
                                        + $loop->index
                                    }}

                                </td>


                                {{-- KODE --}}
                                <td
                                    class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-slate-900"
                                >
                                    {{ $rekening->kode_rekening }}
                                </td>


                                {{-- NAMA REKENING --}}
                                <td
                                    class="min-w-[210px] px-4 py-3"
                                >

                                    <div
                                        class="text-sm font-semibold text-slate-900"
                                    >
                                        {{ $rekening->nama_rekening }}
                                    </div>


                                    @if(
                                        $rekening->jenis === 'BANK'
                                        &&
                                        $rekening->atas_nama
                                    )

                                        <div
                                            class="mt-0.5 text-xs text-slate-500"
                                        >
                                            a.n. {{ $rekening->atas_nama }}
                                        </div>

                                    @endif


                                    @if($rekening->akun)

                                        <div
                                            class="mt-1 text-xs text-slate-400"
                                        >
                                            COA:
                                            {{ $rekening->akun->kode_akun }}
                                            -
                                            {{ $rekening->akun->nama_akun }}
                                        </div>

                                    @else

                                        <div
                                            class="mt-1 text-xs font-semibold text-red-500"
                                        >
                                            Belum mapping COA
                                        </div>

                                    @endif

                                </td>


                                {{-- JENIS --}}
                                <td class="px-4 py-3">

                                    @if($rekening->jenis === 'KAS')

                                        <span
                                            class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700"
                                        >
                                            KAS
                                        </span>

                                    @else

                                        <span
                                            class="inline-flex rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700"
                                        >
                                            BANK
                                        </span>

                                    @endif

                                </td>


                                {{-- BANK / NOMOR --}}
                                <td
                                    class="px-4 py-3 text-sm text-slate-700"
                                >

                                    @if($rekening->jenis === 'BANK')

                                        <div class="font-medium">
                                            {{ $rekening->nama_bank ?: '-' }}
                                        </div>

                                        <div
                                            class="mt-0.5 text-xs text-slate-500"
                                        >
                                            {{ $rekening->nomor_rekening ?: '-' }}
                                        </div>

                                    @else

                                        -

                                    @endif

                                </td>


                                {{-- SALDO AWAL --}}
                                <td
                                    class="whitespace-nowrap px-4 py-3 text-right"
                                >

                                    <div
                                        class="text-sm font-semibold text-slate-700"
                                    >

                                        Rp {{
                                            number_format(
                                                (float)
                                                $rekening->saldo_awal,
                                                0,
                                                ',',
                                                '.'
                                            )
                                        }}

                                    </div>


                                    @if(
                                        $rekening
                                            ->saldo_awal_tidak_sinkron
                                    )

                                        <div
                                            class="mt-1 text-xs font-semibold text-red-600"
                                        >
                                            Berbeda dengan COA
                                        </div>

                                        <div
                                            class="text-xs text-red-500"
                                        >

                                            COA:
                                            Rp {{
                                                number_format(
                                                    (float)
                                                    $rekening
                                                        ->saldo_awal_coa,
                                                    0,
                                                    ',',
                                                    '.'
                                                )
                                            }}

                                        </div>

                                    @endif

                                </td>


                                {{-- SALDO SAAT INI --}}
                                <td
                                    class="whitespace-nowrap px-4 py-3 text-right"
                                >

                                    <div
                                        class="text-base font-bold {{
                                            (float)
                                            $rekening->saldo_saat_ini < 0
                                                ? 'text-red-600'
                                                : 'text-[#15235F]'
                                        }}"
                                    >

                                        Rp {{
                                            number_format(
                                                (float)
                                                $rekening->saldo_saat_ini,
                                                0,
                                                ',',
                                                '.'
                                            )
                                        }}

                                    </div>


                                    <div
                                        class="mt-1 text-xs text-emerald-600"
                                    >

                                        + Rp {{
                                            number_format(
                                                (float)
                                                $rekening
                                                    ->total_debit_mutasi,
                                                0,
                                                ',',
                                                '.'
                                            )
                                        }}

                                    </div>


                                    <div
                                        class="text-xs text-red-500"
                                    >

                                        - Rp {{
                                            number_format(
                                                (float)
                                                $rekening
                                                    ->total_kredit_mutasi,
                                                0,
                                                ',',
                                                '.'
                                            )
                                        }}

                                    </div>

                                </td>


                                {{-- STATUS --}}
                                <td
                                    class="px-4 py-3 text-center"
                                >

                                    @if($rekening->is_active)

                                        <span
                                            class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                                        >
                                            Aktif
                                        </span>

                                    @else

                                        <span
                                            class="inline-flex rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600"
                                        >
                                            Nonaktif
                                        </span>

                                    @endif

                                </td>


                                {{-- AKSI --}}
                                <td
                                    class="whitespace-nowrap px-4 py-3 text-center"
                                >

                                    <a
                                        href="{{ route(
                                            'rekening-kas-bank.edit',
                                            $rekening
                                        ) }}"
                                        class="mr-2 text-sm font-semibold text-[#15235F] hover:underline"
                                    >
                                        Edit
                                    </a>


                                    <form
                                        action="{{ route(
                                            'rekening-kas-bank.destroy',
                                            $rekening
                                        ) }}"
                                        method="POST"
                                        class="inline"
                                        onsubmit="return confirm('Hapus rekening ini?')"
                                    >

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="text-sm font-semibold text-red-600 hover:underline"
                                        >
                                            Hapus
                                        </button>

                                    </form>

                                </td>


                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="9"
                                    class="px-4 py-12 text-center text-sm text-slate-500"
                                >
                                    Belum ada data Kas & Rekening Bank.
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            @if($rekeningKasBanks->hasPages())

                <div
                    class="border-t border-slate-200 px-5 py-4"
                >
                    {{ $rekeningKasBanks->links() }}
                </div>

            @endif

        </div>


    </div>

</div>

@endsection