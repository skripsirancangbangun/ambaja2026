<?php

namespace App\Http\Controllers;

use App\Models\PengaturanPenomoran;
use Illuminate\Http\Request;

class PengaturanPenomoranController extends Controller
{
    public function index()
    {
        $penomorans = PengaturanPenomoran::all();
        return view('pengaturan-penomoran.index', compact('penomorans'));
    }

    public function update(Request $request)
    {
        $settings = $request->input('settings', []);

        foreach ($settings as $id => $val) {
            PengaturanPenomoran::where('id', $id)->update([
                'prefix' => $val['prefix'],
                'digits' => (int) $val['digits'],
            ]);
        }

        return redirect()->route('pengaturan-penomoran.index')
            ->with('success', 'Format penomoran dokumen & jurnal berhasil diperbarui.');
    }
}
