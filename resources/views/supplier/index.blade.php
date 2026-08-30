@extends('layouts.app')

@section('title', 'Supplier')

@section('content')

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Supplier</h2>
            <p class="mt-1 text-sm text-slate-500">
                Kelola data supplier untuk proses pembelian.
            </p>
        </div>

        <a
            href="{{ route('supplier.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
        >
            <span>+</span>
            Tambah Supplier
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-6 py-4">
            <h3 class="text-sm font-semibold text-slate-700">Daftar Supplier</h3>
            <p class="mt-1 text-xs text-slate-400">
                Total {{ $suppliers->total() }} supplier.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">

                <thead class="bg-slate-50">
                    <tr class="text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-6 py-3 font-medium">No</th>
                        <th class="px-6 py-3 font-medium">Kode</th>
                        <th class="px-6 py-3 font-medium">Nama Supplier</th>
                        <th class="px-6 py-3 font-medium">Contact Person</th>
                        <th class="px-6 py-3 font-medium">Telepon</th>
                        <th class="px-6 py-3 font-medium">Kota</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                        <th class="px-6 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse($suppliers as $supplier)

                        <tr class="transition hover:bg-slate-50">

                            <td class="px-6 py-4 text-slate-500">
                                {{ $suppliers->firstItem() + $loop->index }}
                            </td>

                            <td class="px-6 py-4 font-medium text-[#15235F]">
                                {{ $supplier->kode_supplier }}
                            </td>

                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-700">
                                    {{ $supplier->nama_supplier }}
                                </div>

                                @if($supplier->email)
                                    <div class="mt-1 text-xs text-slate-400">
                                        {{ $supplier->email }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-slate-500">
                                {{ $supplier->contact_person ?: '-' }}
                            </td>

                            <td class="px-6 py-4 text-slate-500">
                                {{ $supplier->telepon ?: '-' }}
                            </td>

                            <td class="px-6 py-4 text-slate-500">
                                {{ $supplier->kota ?: '-' }}
                            </td>

                            <td class="px-6 py-4">
                                @if($supplier->is_active)
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
                                        href="{{ route('supplier.edit', $supplier) }}"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-[#32AAA6] hover:bg-teal-50 hover:text-[#15235F]"
                                        title="Edit"
                                    >
                                        ✎
                                    </a>

                                    <form
                                        action="{{ route('supplier.destroy', $supplier) }}"
                                        method="POST"
                                        onsubmit="return confirm('Yakin ingin menghapus supplier ini?')"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-400 transition hover:border-red-200 hover:bg-red-50 hover:text-red-500"
                                            title="Hapus"
                                        >
                                            ×
                                        </button>
                                    </form>

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <p class="text-sm font-medium text-slate-500">
                                    Belum ada data supplier.
                                </p>
                                <p class="mt-1 text-xs text-slate-400">
                                    Tambahkan supplier pertama untuk mulai proses pembelian.
                                </p>
                            </td>
                        </tr>

                    @endforelse

                </tbody>
            </table>
        </div>

        @if($suppliers->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $suppliers->links() }}
            </div>
        @endif

    </div>

</div>

@endsection