<?php

namespace App\Http\Controllers;

use App\Models\PenutupanPeriode;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PenutupanPeriodeController extends Controller
{
    public function index()
    {
        $penutupans = PenutupanPeriode::with(['closedBy', 'openedBy'])
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->get();

        return view('penutupan-periode.index', compact('penutupans'));
    }

    /**
     * Lakukan Cut-Off / Penutupan Transaksi Periode Bulanan (Status: LOCKED)
     */
    public function close(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|between:2020,2099',
            'catatan' => 'nullable|string',
        ]);

        $penutupan = PenutupanPeriode::updateOrCreate(
            ['bulan' => $request->bulan, 'tahun' => $request->tahun],
            [
                'status' => 'LOCKED',
                'tanggal_penutupan' => now(),
                'closed_by_user_id' => auth()->id() ?? 1,
                'catatan' => $request->catatan,
            ]
        );

        $namaBulan = Carbon::createFromDate($request->tahun, $request->bulan, 1)->translatedFormat('F Y');

        return redirect()->route('penutupan-periode.index')
            ->with('success', 'Periode transaksi ' . $namaBulan . ' berhasil DITUTUP (Cut-Off). Transaksi pada periode ini terkunci.');
    }

    /**
     * Backup Plan: Buka Kembali Periode (Re-Open Period) (Status: OPEN)
     */
    public function reopen(Request $request, PenutupanPeriode $penutupanPeriode)
    {
        $request->validate([
            'alasan_buka_kembali' => 'required|string|min:10',
        ]);

        $penutupanPeriode->update([
            'status' => 'OPEN',
            'tanggal_pembukaan' => now(),
            'opened_by_user_id' => auth()->id() ?? 1,
            'alasan_buka_kembali' => $request->alasan_buka_kembali,
        ]);

        $namaBulan = Carbon::createFromDate($penutupanPeriode->tahun, $penutupanPeriode->bulan, 1)->translatedFormat('F Y');

        return redirect()->route('penutupan-periode.index')
            ->with('warning', 'Periode transaksi ' . $namaBulan . ' berhasil DIBUKA KEMBALI. Transaksi dapat disesuaikan sementara.');
    }
}
