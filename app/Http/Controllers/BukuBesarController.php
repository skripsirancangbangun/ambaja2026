<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\JurnalDetail;
use Illuminate\Http\Request;

class BukuBesarController extends Controller
{
    public function index(Request $request)
    {
        $akuns = Akun::where('is_active', true)->orderBy('kode_akun')->get();
        $selectedAkunId = $request->input('akun_id', $akuns->first()->id ?? null);
        $tanggalMulai = $request->input('tanggal_mulai', date('Y-01-01'));
        $tanggalSelesai = $request->input('tanggal_selesai', date('Y-m-d'));

        $selectedAkun = null;
        $details = collect();
        $saldoAwalPeriod = 0;

        if ($selectedAkunId) {
            $selectedAkun = Akun::find($selectedAkunId);

            if ($selectedAkun) {
                // Calculate prior balance before start date
                $priorDebit = JurnalDetail::where('akun_id', $selectedAkun->id)
                    ->whereHas('jurnal', function ($q) use ($tanggalMulai) {
                        $q->whereDate('tanggal', '<', $tanggalMulai);
                    })
                    ->sum('debit');

                $priorKredit = JurnalDetail::where('akun_id', $selectedAkun->id)
                    ->whereHas('jurnal', function ($q) use ($tanggalMulai) {
                        $q->whereDate('tanggal', '<', $tanggalMulai);
                    })
                    ->sum('kredit');

                $isNominalLabaRugi = preg_match('/^[456789]/', $selectedAkun->kode_akun);

                if ($isNominalLabaRugi) {
                    $saldoAwalPeriod = 0;
                } else {
                    if ($selectedAkun->saldo_normal === 'DEBIT') {
                        $saldoAwalPeriod = (float) $selectedAkun->saldo_awal + ($priorDebit - $priorKredit);
                    } else {
                        $saldoAwalPeriod = (float) $selectedAkun->saldo_awal + ($priorKredit - $priorDebit);
                    }
                }

                // Query details within period
                $details = JurnalDetail::with(['jurnal'])
                    ->where('akun_id', $selectedAkun->id)
                    ->whereHas('jurnal', function ($q) use ($tanggalMulai, $tanggalSelesai) {
                        $q->whereDate('tanggal', '>=', $tanggalMulai)
                          ->whereDate('tanggal', '<=', $tanggalSelesai);
                    })
                    ->get()
                    ->sortBy(function ($detail) {
                        $tgl = $detail->jurnal?->tanggal ? $detail->jurnal->tanggal->format('Y-m-d') : '9999-99-99';
                        return $tgl . '-' . str_pad($detail->jurnal_id, 8, '0', STR_PAD_LEFT);
                    });
            }
        }

        return view('buku-besar.index', compact(
            'akuns',
            'selectedAkunId',
            'selectedAkun',
            'tanggalMulai',
            'tanggalSelesai',
            'saldoAwalPeriod',
            'details'
        ));
    }
}
