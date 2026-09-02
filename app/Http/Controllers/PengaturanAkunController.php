<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\PengaturanAkun;
use Illuminate\Http\Request;

class PengaturanAkunController extends Controller
{
    public function index()
    {
        $pengaturanAkuns = PengaturanAkun::with('akun')->get();
        $akuns = Akun::where('is_active', true)->orderBy('kode_akun')->get();

        return view('pengaturan-akun.index', compact('pengaturanAkuns', 'akuns'));
    }

    public function update(Request $request)
    {
        $mappings = $request->input('mappings', []);

        foreach ($mappings as $id => $akunId) {
            PengaturanAkun::where('id', $id)->update(['akun_id' => $akunId ?: null]);
        }

        return redirect()->route('pengaturan-akun.index')
            ->with('success', 'Pemetaan Akun Sistem berhasil diperbarui.');
    }
}
