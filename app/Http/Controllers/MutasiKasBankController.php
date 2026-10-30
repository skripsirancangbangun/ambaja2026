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
            ->with('akun')
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

        /*
        |--------------------------------------------------------------------------
        | SALDO REKENING SAAT INI
        |--------------------------------------------------------------------------
        |
        | Gunakan rumus yang sama dengan halaman Rekening Kas/Bank:
        |
        | Saldo awal + total debit mutasi - total kredit mutasi.
        |
        | Jangan mengambil saldo dari mutasi terakhir karena saldo_awal dapat
        | disinkronkan/dikoreksi setelah mutasi lama tercatat. Kolom `saldo`
        | pada setiap mutasi tetap dipertahankan sebagai saldo historis mutasi.
        | Ringkasan dihitung secara agregat agar tidak menjalankan query per rekening.
        |
        */

        $ringkasanMutasi = MutasiKasBank::query()
            ->selectRaw('
                rekening_kas_bank_id,
                COALESCE(SUM(debit), 0) AS total_debit,
                COALESCE(SUM(kredit), 0) AS total_kredit
            ')
            ->groupBy('rekening_kas_bank_id')
            ->get()
            ->keyBy('rekening_kas_bank_id');

        $saldoRekenings = $rekeningKasBanks->map(
            function ($rekening) use ($ringkasanMutasi) {
                $ringkasan = $ringkasanMutasi->get($rekening->id);

                $saldoAwal = (float) $rekening->saldo_awal;
                $totalDebit = (float) ($ringkasan?->total_debit ?? 0);
                $totalKredit = (float) ($ringkasan?->total_kredit ?? 0);

                $rekening->saldo_saat_ini = round(
                    $saldoAwal + $totalDebit - $totalKredit,
                    2
                );

                $rekening->total_debit_mutasi = $totalDebit;
                $rekening->total_kredit_mutasi = $totalKredit;

                // Bandingkan saldo subledger Kas/Bank dengan saldo COA dari jurnal posted.
                $saldoCoa = $rekening->akun?->saldo_saat_ini;
                $rekening->saldo_coa_saat_ini = $saldoCoa === null
                    ? null
                    : round((float) $saldoCoa, 2);
                $rekening->selisih_saldo_coa = $rekening->saldo_coa_saat_ini === null
                    ? null
                    : round((float) $rekening->saldo_saat_ini - $rekening->saldo_coa_saat_ini, 2);
                $rekening->saldo_sinkron_dengan_coa = $rekening->selisih_saldo_coa !== null
                    && abs($rekening->selisih_saldo_coa) <= 0.01;

                return $rekening;
            }
        );

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