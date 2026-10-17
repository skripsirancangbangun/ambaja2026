<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\PengaturanAkun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PengaturanAkunController extends Controller
{
    public function index()
    {
        $pengaturanAkuns = PengaturanAkun::with('akun')->get();

        $akuns = Akun::where('is_active', true)
            ->orderBy('kode_akun')
            ->get();

        // Akun lawan saldo awal dibatasi ke akun ekuitas aktif agar tidak salah pilih akun.
        $akunsEkuitas = Akun::where('is_active', true)
            ->where('kategori_akun', 'Ekuitas')
            ->orderBy('kode_akun')
            ->get();

        return view(
            'pengaturan-akun.index',
            compact('pengaturanAkuns', 'akuns', 'akunsEkuitas')
        );
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'mappings' => ['nullable', 'array'],
            'mappings.*' => ['nullable', 'integer', 'exists:akuns,id'],
        ]);

        $mappings = $validated['mappings'] ?? [];

        DB::transaction(function () use ($mappings) {
            foreach ($mappings as $id => $akunId) {
                $pengaturan = PengaturanAkun::findOrFail($id);
                $akunId = $akunId ?: null;

                if (
                    $pengaturan->kunci_pengaturan === 'AKUN_SALDO_AWAL'
                    && $akunId !== null
                ) {
                    $akunEkuitasValid = Akun::query()
                        ->whereKey($akunId)
                        ->where('is_active', true)
                        ->where('kategori_akun', 'Ekuitas')
                        ->exists();

                    if (!$akunEkuitasValid) {
                        throw ValidationException::withMessages([
                            'mappings.' . $id => 'Akun lawan saldo awal harus berupa akun Ekuitas yang aktif.',
                        ]);
                    }
                }

                $pengaturan->update(['akun_id' => $akunId]);
            }
        });

        return redirect()->route('pengaturan-akun.index')
            ->with('success', 'Pemetaan Akun Sistem berhasil diperbarui.');
    }
}
