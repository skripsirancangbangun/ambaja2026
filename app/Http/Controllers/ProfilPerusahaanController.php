<?php

namespace App\Http\Controllers;

use App\Models\ProfilPerusahaan;
use Illuminate\Http\Request;

class ProfilPerusahaanController extends Controller
{
    public function index()
    {
        $profil = ProfilPerusahaan::getProfil();
        return view('profil-perusahaan.index', compact('profil'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'nama_perusahaan' => 'required|string|max:255',
            'nama_singkat' => 'required|string|max:100',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'kota' => 'nullable|string|max:100',
            'rekening_bca' => 'nullable|string|max:255',
            'rekening_mandiri' => 'nullable|string|max:255',
            'catatan_dokumen' => 'nullable|string',
        ]);

        $profil = ProfilPerusahaan::firstOrCreate(['id' => 1]);
        $profil->update($validated);

        return back()->with('success', 'Profil Perusahaan CV Andika Mustika berhasil diperbarui.');
    }
}
