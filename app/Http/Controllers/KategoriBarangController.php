<?php

namespace App\Http\Controllers;

use App\Models\KategoriBarang;
use Illuminate\Http\Request;

class KategoriBarangController extends Controller
{
    public function index()
    {
        $kategoris = KategoriBarang::latest()->paginate(10);

        return view('kategori-barang.index', compact('kategoris'));
    }

    public function create()
    {
        return view('kategori-barang.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_kategori' => 'required|string|max:50|unique:kategori_barangs,kode_kategori',
            'nama_kategori' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        KategoriBarang::create($validated);

        return redirect()
            ->route('kategori-barang.index')
            ->with('success', 'Kategori barang berhasil ditambahkan.');
    }

    public function edit(KategoriBarang $kategori_barang)
    {
        return view('kategori-barang.edit', [
            'kategori' => $kategori_barang
        ]);
    }

    public function update(Request $request, KategoriBarang $kategori_barang)
    {
        $validated = $request->validate([
            'kode_kategori' => 'required|string|max:50|unique:kategori_barangs,kode_kategori,' . $kategori_barang->id,
            'nama_kategori' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $kategori_barang->update($validated);

        return redirect()
            ->route('kategori-barang.index')
            ->with('success', 'Kategori barang berhasil diperbarui.');
    }

    public function destroy(KategoriBarang $kategori_barang)
    {
        $kategori_barang->delete();

        return redirect()
            ->route('kategori-barang.index')
            ->with('success', 'Kategori barang berhasil dihapus.');
    }
}