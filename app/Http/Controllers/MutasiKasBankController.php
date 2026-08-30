<?php

namespace App\Http\Controllers;

use App\Models\MutasiKasBank;
use App\Models\RekeningKasBank;
use Illuminate\Http\Request;

class MutasiKasBankController extends Controller
{
    public function index(Request $request)
    {
        $rekeningKasBanks = RekeningKasBank::query()
            ->orderBy('jenis')
            ->orderBy('nama_rekening')
            ->get();

        $query = MutasiKasBank::query()
            ->with('rekeningKasBank');

        /*
        |--------------------------------------------------------------------------
        | FILTER REKENING
        |--------------------------------------------------------------------------
        */

        if ($request->filled('rekening_kas_bank_id')) {
            $query->where(
                'rekening_kas_bank_id',
                $request->rekening_kas_bank_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER TIPE
        |--------------------------------------------------------------------------
        */

        if ($request->filled('tipe')) {
            $query->where(
                'tipe',
                $request->tipe
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER TANGGAL
        |--------------------------------------------------------------------------
        */

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate(
                'tanggal',
                '>=',
                $request->tanggal_mulai
            );
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate(
                'tanggal',
                '<=',
                $request->tanggal_selesai
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where(
                    'nomor_referensi',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'reference_type',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'keterangan',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhereHas(
                    'rekeningKasBank',
                    function ($rekeningQuery) use ($search) {
                        $rekeningQuery
                            ->where(
                                'kode_rekening',
                                'like',
                                '%' . $search . '%'
                            )
                            ->orWhere(
                                'nama_rekening',
                                'like',
                                '%' . $search . '%'
                            )
                            ->orWhere(
                                'nama_bank',
                                'like',
                                '%' . $search . '%'
                            );
                    }
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        $summaryQuery = clone $query;

        $totalDebit = (float) (clone $summaryQuery)->sum('debit');
        $totalKredit = (float) (clone $summaryQuery)->sum('kredit');

        /*
        |--------------------------------------------------------------------------
        | DATA MUTASI
        |--------------------------------------------------------------------------
        */

        $mutasis = $query
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | SALDO REKENING
        |--------------------------------------------------------------------------
        */

        $saldoRekenings = $rekeningKasBanks->map(function ($rekening) {

            $mutasiTerakhir = MutasiKasBank::query()
                ->where(
                    'rekening_kas_bank_id',
                    $rekening->id
                )
                ->orderByDesc('tanggal')
                ->orderByDesc('id')
                ->first();

            $rekening->saldo_saat_ini = $mutasiTerakhir
                ? (float) $mutasiTerakhir->saldo
                : (float) $rekening->saldo_awal;

            return $rekening;
        });

        $totalSaldo = $saldoRekenings->sum(
            'saldo_saat_ini'
        );

        return view(
            'mutasi-kas-bank.index',
            compact(
                'mutasis',
                'rekeningKasBanks',
                'saldoRekenings',
                'totalDebit',
                'totalKredit',
                'totalSaldo'
            )
        );
    }
}