<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\KategoriBarang;
use App\Models\Satuan;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;


class BarangController extends Controller
{
    /**
     * Menampilkan master barang dengan pencarian, filter, dan pagination.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100',
            'kategori_barang_id' => 'nullable|integer|exists:kategori_barangs,id',
            'satuan_id' => 'nullable|integer|exists:satuans,id',
            'status' => 'nullable|in:aktif,nonaktif',
            'per_page' => 'nullable|in:25,50,100',
        ]);

        $search = trim($filters['q'] ?? '');

        $query = Barang::query()->with([
            'kategori',
            'satuan',
        ]);

        // Pencarian berdasarkan kode, nama, spesifikasi, atau merek.
        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $keyword = '%' . $search . '%';

                $subQuery->where('kode_barang', 'like', $keyword)
                    ->orWhere('nama_barang', 'like', $keyword)
                    ->orWhere('spesifikasi', 'like', $keyword)
                    ->orWhere('merek', 'like', $keyword);
            });
        }

        // Filter kategori.
        if (!empty($filters['kategori_barang_id'])) {
            $query->where(
                'kategori_barang_id',
                $filters['kategori_barang_id']
            );
        }

        // Filter satuan.
        if (!empty($filters['satuan_id'])) {
            $query->where(
                'satuan_id',
                $filters['satuan_id']
            );
        }

        // Filter status barang.
        if (($filters['status'] ?? '') === 'aktif') {
            $query->where('is_active', true);
        } elseif (($filters['status'] ?? '') === 'nonaktif') {
            $query->where('is_active', false);
        }

        // Pagination dibatasi agar query tetap efisien.
        $perPage = (int) ($filters['per_page'] ?? 25);

        $barangs = $query
            ->orderBy('kode_barang', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        // Kategori untuk dropdown filter.
        $kategoris = KategoriBarang::query()
            ->orderBy('nama_kategori')
            ->get();

        /*
         * PERBAIKAN:
         * Dropdown filter hanya menampilkan satuan aktif.
         * Satuan nonaktif tetap disimpan di database.
         */
        $satuans = Satuan::query()
            ->where('is_active', true)
            ->orderBy('nama_satuan')
            ->get();

        return view('barang.index', compact(
            'barangs',
            'kategoris',
            'satuans'
        ));
    }

    /**
     * Form tambah barang.
     */
    public function create()
    {
        $kategoris = KategoriBarang::query()
            ->where('is_active', true)
            ->orderBy('nama_kategori')
            ->get();

        $satuans = Satuan::query()
            ->where('is_active', true)
            ->orderBy('nama_satuan')
            ->get();

        return view(
            'barang.create',
            compact('kategoris', 'satuans')
        );
    }

    /**
     * Simpan barang baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_barang' =>
                'required|string|max:100|unique:barangs,kode_barang',

            'nama_barang' =>
                'required|string|max:255',

            'kategori_barang_id' =>
                'required|exists:kategori_barangs,id',

            'satuan_id' =>
                'required|exists:satuans,id',

            'spesifikasi' =>
                'nullable|string|max:255',

            'merek' =>
                'nullable|string|max:255',

            // Harga referensi pembelian, bukan HPP moving average.
            'harga_beli_default' =>
                'required|numeric|min:0',

            'margin_persen' =>
                'required|numeric|min:0|max:999.99',

            'harga_jual' =>
                'required|numeric|min:0',

            'minimum_stok' =>
                'required|numeric|min:0',

            'is_active' =>
                'required|boolean',
        ]);

        // Cegah penggunaan kategori nonaktif melalui request manual.
        $kategoriAktif = KategoriBarang::query()
            ->whereKey($validated['kategori_barang_id'])
            ->where('is_active', true)
            ->exists();

        if (!$kategoriAktif) {
            return back()
                ->withInput()
                ->withErrors([
                    'kategori_barang_id' =>
                        'Kategori tidak aktif. Pilih kategori yang masih aktif.',
                ]);
        }

        // Cegah penggunaan satuan nonaktif melalui request manual.
        $satuanAktif = Satuan::query()
            ->whereKey($validated['satuan_id'])
            ->where('is_active', true)
            ->exists();

        if (!$satuanAktif) {
            return back()
                ->withInput()
                ->withErrors([
                    'satuan_id' =>
                        'Satuan tidak aktif. Pilih satuan yang masih aktif.',
                ]);
        }

        Barang::create($validated);

        return redirect()
            ->route('barang.index')
            ->with(
                'success',
                'Barang berhasil ditambahkan. Target markup dan harga jual disimpan secara terpisah.'
            );
    }

    /**
     * Form edit barang.
     */
    public function edit(Barang $barang)
    {
        $kategoris = KategoriBarang::query()
            ->where(function ($query) use ($barang) {
                $query->where('is_active', true)
                    ->orWhere('id', $barang->kategori_barang_id);
            })
            ->orderBy('nama_kategori')
            ->get();

        /*
         * Tampilkan satuan aktif serta satuan yang sedang dipakai
         * barang ini jika satuan tersebut sudah dinonaktifkan.
         *
         * Ini mencegah form edit barang lama kehilangan pilihan
         * satuannya. Satuan nonaktif tidak ditawarkan untuk barang baru.
         */
        $satuans = Satuan::query()
            ->where(function ($query) use ($barang) {
                $query->where('is_active', true)
                    ->orWhere('id', $barang->satuan_id);
            })
            ->orderBy('nama_satuan')
            ->get();

        /*
         * HPP aktual moving average dihitung dari seluruh gudang.
         * Perhitungan ini hanya untuk referensi saran harga jual.
         * HPP transaksi tetap mengikuti gudang transaksi masing-masing.
         */
        $barang->loadMissing('stokGudangs');

        $stokQtyTotal = (float) $barang->stokGudangs->sum(
            fn ($stok) => (float) $stok->qty
        );

        $stokNilaiTotal = (float) $barang->stokGudangs->sum(
            fn ($stok) => (float) $stok->nilai_persediaan
        );

        // Jika belum memiliki stok, gunakan harga beli default.
        $hppAktual = $stokQtyTotal > 0
            ? round($stokNilaiTotal / $stokQtyTotal, 2)
            : (float) $barang->harga_beli_default;

        // Harga jual saran tidak otomatis disimpan.
        $hargaJualSaran = round(
            $hppAktual
            + ($hppAktual * (float) $barang->margin_persen / 100),
            2
        );

        return view(
            'barang.edit',
            compact(
                'barang',
                'kategoris',
                'satuans',
                'stokQtyTotal',
                'hppAktual',
                'hargaJualSaran'
            )
        );
    }

    /**
     * Perbarui barang.
     */
    public function update(Request $request, Barang $barang)
    {
        $validated = $request->validate([
            'kode_barang' =>
                'required|string|max:100|unique:barangs,kode_barang,' . $barang->id,

            'nama_barang' =>
                'required|string|max:255',

            'kategori_barang_id' =>
                'required|exists:kategori_barangs,id',

            'satuan_id' =>
                'required|exists:satuans,id',

            'spesifikasi' =>
                'nullable|string|max:255',

            'merek' =>
                'nullable|string|max:255',

            'harga_beli_default' =>
                'required|numeric|min:0',

            'margin_persen' =>
                'required|numeric|min:0|max:999.99',

            'harga_jual' =>
                'required|numeric|min:0',

            'minimum_stok' =>
                'required|numeric|min:0',

            'is_active' =>
                'required|boolean',
        ]);

        // Kategori nonaktif boleh dipertahankan untuk barang lama,
        // tetapi tidak boleh dipilih sebagai kategori pengganti.
        if (
            (int) $validated['kategori_barang_id'] !== (int) $barang->kategori_barang_id
            && !KategoriBarang::query()
                ->whereKey($validated['kategori_barang_id'])
                ->where('is_active', true)
                ->exists()
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'kategori_barang_id' =>
                        'Kategori tujuan tidak aktif. Pilih kategori yang masih aktif.',
                ]);
        }

        // Satuan nonaktif boleh dipertahankan untuk barang lama,
        // tetapi tidak boleh dipilih sebagai satuan pengganti.
        if (
            (int) $validated['satuan_id'] !== (int) $barang->satuan_id
            && !Satuan::query()
                ->whereKey($validated['satuan_id'])
                ->where('is_active', true)
                ->exists()
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'satuan_id' =>
                        'Satuan tujuan tidak aktif. Pilih satuan yang masih aktif.',
                ]);
        }

        $barang->update($validated);

        return redirect()
            ->route('barang.index')
            ->with(
                'success',
                'Barang berhasil diperbarui. Harga jual tidak berubah otomatis karena perubahan HPP atau pembelian.'
            );
    }

    /**
     * Hapus barang jika belum digunakan transaksi.
     */
    public function destroy(Barang $barang)
    {
        try {
            $barang->delete();
        } catch (QueryException $e) {
            return redirect()
                ->route('barang.index')
                ->with(
                    'error',
                    'Barang tidak dapat dihapus karena sudah digunakan pada transaksi. Nonaktifkan barang jika tidak ingin digunakan lagi.'
                );
        }

        return redirect()
            ->route('barang.index')
            ->with('success', 'Barang berhasil dihapus.');
    }
}
