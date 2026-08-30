<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use Illuminate\Http\Request;

class GudangController extends Controller
{
    public function index()
    {
        $gudangs = Gudang::latest()->paginate(10);

        return view('gudang.index', compact('gudangs'));
    }

    public function create()
    {
        return view('gudang.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_gudang' => 'required|string|max:50|unique:gudangs,kode_gudang',
            'nama_gudang' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'keterangan' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        Gudang::create($validated);

        return redirect()
            ->route('gudang.index')
            ->with('success', 'Gudang berhasil ditambahkan.');
    }

    public function edit(Gudang $gudang)
    {
        return view('gudang.edit', compact('gudang'));
    }

    public function update(Request $request, Gudang $gudang)
    {
        $validated = $request->validate([
            'kode_gudang' => 'required|string|max:50|unique:gudangs,kode_gudang,' . $gudang->id,
            'nama_gudang' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'keterangan' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $gudang->update($validated);

        return redirect()
            ->route('gudang.index')
            ->with('success', 'Gudang berhasil diperbarui.');
    }

    public function destroy(Gudang $gudang)
    {
        $gudang->delete();

        return redirect()
            ->route('gudang.index')
            ->with('success', 'Gudang berhasil dihapus.');
    }
}