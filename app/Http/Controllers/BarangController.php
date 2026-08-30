<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\KategoriBarang;
use App\Models\Satuan;
use Illuminate\Http\Request;

class BarangController extends Controller
{
    public function index()
    {
        $barangs = Barang::with(['kategori', 'satuan'])
            ->latest()
            ->paginate(10);

        return view('barang.index', compact('barangs'));
    }

    public function create()
    {
        $kategoris = KategoriBarang::where('is_active', true)
            ->orderBy('nama_kategori')
            ->get();

        $satuans = Satuan::where('is_active', true)
            ->orderBy('nama_satuan')
            ->get();

        return view('barang.create', compact(
            'kategoris',
            'satuans'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_barang' => 'required|string|max:100|unique:barangs,kode_barang',
            'nama_barang' => 'required|string|max:255',
            'kategori_barang_id' => 'required|exists:kategori_barangs,id',
            'satuan_id' => 'required|exists:satuans,id',
            'spesifikasi' => 'nullable|string|max:255',
            'merek' => 'nullable|string|max:255',

            'harga_beli_default' => 'required|numeric|min:0',
            'margin_persen' => 'required|numeric|min:0|max:999.99',
            'minimum_stok' => 'required|numeric|min:0',

            'is_active' => 'required|boolean',
        ]);

        /*
        |--------------------------------------------------------------------------
        | HITUNG HARGA JUAL
        |--------------------------------------------------------------------------
        */

        $hargaBeli = (float) $validated['harga_beli_default'];
        $margin = (float) $validated['margin_persen'];

        $validated['harga_jual'] =
            $hargaBeli + ($hargaBeli * $margin / 100);

        Barang::create($validated);

        return redirect()
            ->route('barang.index')
            ->with('success', 'Barang berhasil ditambahkan.');
    }

    public function edit(Barang $barang)
    {
        $kategoris = KategoriBarang::where('is_active', true)
            ->orderBy('nama_kategori')
            ->get();

        $satuans = Satuan::where('is_active', true)
            ->orderBy('nama_satuan')
            ->get();

        return view('barang.edit', compact(
            'barang',
            'kategoris',
            'satuans'
        ));
    }

    public function update(Request $request, Barang $barang)
    {
        $validated = $request->validate([
            'kode_barang' => 'required|string|max:100|unique:barangs,kode_barang,' . $barang->id,
            'nama_barang' => 'required|string|max:255',
            'kategori_barang_id' => 'required|exists:kategori_barangs,id',
            'satuan_id' => 'required|exists:satuans,id',
            'spesifikasi' => 'nullable|string|max:255',
            'merek' => 'nullable|string|max:255',

            'harga_beli_default' => 'required|numeric|min:0',
            'margin_persen' => 'required|numeric|min:0|max:999.99',
            'minimum_stok' => 'required|numeric|min:0',

            'is_active' => 'required|boolean',
        ]);

        /*
        |--------------------------------------------------------------------------
        | HITUNG ULANG HARGA JUAL
        |--------------------------------------------------------------------------
        */

        $hargaBeli = (float) $validated['harga_beli_default'];
        $margin = (float) $validated['margin_persen'];

        $validated['harga_jual'] =
            $hargaBeli + ($hargaBeli * $margin / 100);

        $barang->update($validated);

        return redirect()
            ->route('barang.index')
            ->with('success', 'Barang berhasil diperbarui.');
    }

    public function destroy(Barang $barang)
    {
        $barang->delete();

        return redirect()
            ->route('barang.index')
            ->with('success', 'Barang berhasil dihapus.');
    }
}