<?php

namespace App\Http\Controllers;

use App\Models\Satuan;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class SatuanController extends Controller
{
    public function index()
    {
        $satuans = Satuan::latest()->paginate(10);

        return view('satuan.index', compact('satuans'));
    }

    public function create()
    {
        return view('satuan.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_satuan' => 'required|string|max:50|unique:satuans,kode_satuan',
            'nama_satuan' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        Satuan::create($validated);

        return redirect()
            ->route('satuan.index')
            ->with('success', 'Satuan berhasil ditambahkan.');
    }

    public function edit(Satuan $satuan)
    {
        return view('satuan.edit', compact('satuan'));
    }

    public function update(Request $request, Satuan $satuan)
    {
        $validated = $request->validate([
            'kode_satuan' => 'required|string|max:50|unique:satuans,kode_satuan,' . $satuan->id,
            'nama_satuan' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $satuan->update($validated);

        return redirect()
            ->route('satuan.index')
            ->with('success', 'Satuan berhasil diperbarui.');
    }

    public function destroy(Satuan $satuan)
    {
        try {
            $satuan->delete();
        } catch (QueryException $e) {
            report($e);

            return redirect()
                ->route('satuan.index')
                ->with(
                    'error',
                    'Satuan tidak dapat dihapus karena masih digunakan oleh barang atau data terkait. Nonaktifkan satuan jika tidak ingin digunakan lagi.'
                );
        }

        return redirect()
            ->route('satuan.index')
            ->with('success', 'Satuan berhasil dihapus.');
    }
}
