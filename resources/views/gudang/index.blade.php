@extends('layouts.app')

@section('title', 'Gudang')

@section('content')

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h2 class="text-xl font-semibold text-slate-800">
                Gudang
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Kelola lokasi gudang yang digunakan untuk penyimpanan barang.
            </p>
        </div>

        <a
            href="{{ route('gudang.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/>
            </svg>

            Tambah Gudang
        </a>

    </div>


    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif


    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-6 py-4">

            <h3 class="text-sm font-semibold text-slate-700">
                Daftar Gudang
            </h3>

            <p class="mt-1 text-xs text-slate-400">
                Total {{ $gudangs->total() }} gudang.
            </p>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-left text-sm">

                <thead class="bg-slate-50">
                    <tr class="text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-6 py-3 font-medium">No</th>
                        <th class="px-6 py-3 font-medium">Kode</th>
                        <th class="px-6 py-3 font-medium">Nama Gudang</th>
                        <th class="px-6 py-3 font-medium">Alamat</th>
                        <th class="px-6 py-3 font-medium">Keterangan</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                        <th class="px-6 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse($gudangs as $gudang)

                        <tr class="transition hover:bg-slate-50">

                            <td class="px-6 py-4 text-slate-500">
                                {{ $gudangs->firstItem() + $loop->index }}
                            </td>

                            <td class="px-6 py-4">
                                <span class="font-medium text-[#15235F]">
                                    {{ $gudang->kode_gudang }}
                                </span>
                            </td>

                            <td class="px-6 py-4 font-medium text-slate-700">
                                {{ $gudang->nama_gudang }}
                            </td>

                            <td class="max-w-xs px-6 py-4 text-slate-500">
                                {{ $gudang->alamat ?: '-' }}
                            </td>

                            <td class="max-w-xs px-6 py-4 text-slate-500">
                                {{ $gudang->keterangan ?: '-' }}
                            </td>

                            <td class="px-6 py-4">

                                @if($gudang->is_active)
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500">
                                        Nonaktif
                                    </span>
                                @endif

                            </td>

                            <td class="px-6 py-4">

                                <div class="flex items-center justify-end gap-2">

                                    <a
                                        href="{{ route('gudang.edit', $gudang) }}"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-[#32AAA6] hover:bg-teal-50 hover:text-[#15235F]"
                                        title="Edit"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                  d="M15.232 5.232l3.536 3.536M9 17l-4 1 1-4L16.5 3.5a2.121 2.121 0 013 3L9 17z"/>
                                        </svg>
                                    </a>


                                    <form
                                        action="{{ route('gudang.destroy', $gudang) }}"
                                        method="POST"
                                        onsubmit="return confirm('Yakin ingin menghapus gudang ini?')"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-400 transition hover:border-red-200 hover:bg-red-50 hover:text-red-500"
                                            title="Hapus"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                                      d="M6 7h12M9 7V4h6v3m-8 0l1 13h8l1-13M10 11v5m4-5v5"/>
                                            </svg>
                                        </button>
                                    </form>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">

                                <p class="text-sm font-medium text-slate-500">
                                    Belum ada data gudang.
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    Tambahkan gudang pertama untuk mulai mengelola lokasi penyimpanan barang.
                                </p>

                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if($gudangs->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $gudangs->links() }}
            </div>
        @endif

    </div>

</div>

@endsection