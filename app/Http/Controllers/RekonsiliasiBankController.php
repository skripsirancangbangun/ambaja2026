<?php

namespace App\Http\Controllers;

use App\Models\MutasiKasBank;
use App\Models\PembayaranSupplier;
use App\Models\RekeningKasBank;
use App\Models\RekonsiliasiBank;
use App\Models\SupplierRekeningAlias;
use App\Models\TransaksiBelumTeridentifikasi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RekonsiliasiBankController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query =
            TransaksiBelumTeridentifikasi::with([
                'rekeningKasBank',
            ])
                ->where(
                    'status',
                    'BELUM_TERIDENTIFIKASI'
                );


        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search =
                trim(
                    $request->search
                );


            $query->where(
                function ($q) use ($search) {

                    $q->where(
                        'nama_pengirim_penerima',
                        'like',
                        "%{$search}%"
                    )
                        ->orWhere(
                            'nomor_referensi_bank',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'deskripsi_bank',
                            'like',
                            "%{$search}%"
                        );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | FILTER REKENING
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'rekening_kas_bank_id'
            )
        ) {

            $query->where(
                'rekening_kas_bank_id',
                $request
                    ->rekening_kas_bank_id
            );
        }


        /*
        |--------------------------------------------------------------------------
        | FILTER JENIS
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'jenis'
            )
        ) {

            $query->where(
                'jenis',
                $request->jenis
            );
        }


        /*
        |--------------------------------------------------------------------------
        | FILTER TANGGAL MULAI
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'tanggal_mulai'
            )
        ) {

            $query->whereDate(
                'tanggal',
                '>=',
                $request
                    ->tanggal_mulai
            );
        }


        /*
        |--------------------------------------------------------------------------
        | FILTER TANGGAL SELESAI
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'tanggal_selesai'
            )
        ) {

            $query->whereDate(
                'tanggal',
                '<=',
                $request
                    ->tanggal_selesai
            );
        }


        /*
        |--------------------------------------------------------------------------
        | DATA TRANSAKSI BANK
        |--------------------------------------------------------------------------
        */

        $transaksis =
            $query
                ->orderByDesc(
                    'tanggal'
                )
                ->orderByDesc(
                    'id'
                )
                ->paginate(20)
                ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | DAFTAR REKENING BANK
        |--------------------------------------------------------------------------
        */

        $rekeningBanks =
            RekeningKasBank::query()
                ->where(
                    'jenis',
                    'BANK'
                )
                ->where(
                    'is_active',
                    true
                )
                ->orderBy(
                    'nama_rekening'
                )
                ->get();


        /*
        |--------------------------------------------------------------------------
        | RINGKASAN
        |--------------------------------------------------------------------------
        */

        $jumlahBelumDirekonsiliasi =
            TransaksiBelumTeridentifikasi::query()
                ->where(
                    'status',
                    'BELUM_TERIDENTIFIKASI'
                )
                ->count();


        $jumlahSudahDirekonsiliasi =
            RekonsiliasiBank::query()
                ->count();


        $totalMasukBelumDirekonsiliasi =
            TransaksiBelumTeridentifikasi::query()
                ->where(
                    'status',
                    'BELUM_TERIDENTIFIKASI'
                )
                ->where(
                    'jenis',
                    'MASUK'
                )
                ->sum(
                    'jumlah'
                );


        $totalKeluarBelumDirekonsiliasi =
            TransaksiBelumTeridentifikasi::query()
                ->where(
                    'status',
                    'BELUM_TERIDENTIFIKASI'
                )
                ->where(
                    'jenis',
                    'KELUAR'
                )
                ->sum(
                    'jumlah'
                );


        return view(
            'rekonsiliasi-bank.index',
            compact(
                'transaksis',
                'rekeningBanks',
                'jumlahBelumDirekonsiliasi',
                'jumlahSudahDirekonsiliasi',
                'totalMasukBelumDirekonsiliasi',
                'totalKeluarBelumDirekonsiliasi'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show(
        TransaksiBelumTeridentifikasi $transaksiBank
    ) {
        /*
        |--------------------------------------------------------------------------
        | LOAD DATA
        |--------------------------------------------------------------------------
        */

        $transaksiBank->load([
            'rekeningKasBank',
            'identifier',
        ]);


        /*
        |--------------------------------------------------------------------------
        | NAMA DARI REKENING KORAN
        |--------------------------------------------------------------------------
        */

        $namaBankTerdeteksi =
            $this->ambilNamaBank(
                $transaksiBank
            );


        /*
        |--------------------------------------------------------------------------
        | JIKA SUDAH DIREKONSILIASI
        |--------------------------------------------------------------------------
        */

        if (
            $transaksiBank->status
            !==
            'BELUM_TERIDENTIFIKASI'
        ) {

            $rekonsiliasi =
                RekonsiliasiBank::with([
                    'mutasiKasBank.rekeningKasBank',
                    'creator',
                ])
                    ->where(
                        'transaksi_bank_id',
                        $transaksiBank->id
                    )
                    ->first();


            if (
                $rekonsiliasi
                &&
                $rekonsiliasi
                    ->mutasiKasBank
            ) {

                $this->tambahkanDetailSumber(
                    $rekonsiliasi
                        ->mutasiKasBank,
                    $transaksiBank
                );
            }


            $kandidatMutasis =
                collect();


            return view(
                'rekonsiliasi-bank.show',
                compact(
                    'transaksiBank',
                    'rekonsiliasi',
                    'kandidatMutasis',
                    'namaBankTerdeteksi'
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | RENTANG TANGGAL ±7 HARI
        |--------------------------------------------------------------------------
        */

        $tanggalBank =
            Carbon::parse(
                $transaksiBank->tanggal
            )
                ->startOfDay();


        $tanggalMulai =
            $tanggalBank
                ->copy()
                ->subDays(7)
                ->startOfDay();


        $tanggalSelesai =
            $tanggalBank
                ->copy()
                ->addDays(7)
                ->endOfDay();


        /*
        |--------------------------------------------------------------------------
        | QUERY KANDIDAT
        |--------------------------------------------------------------------------
        */

        $kandidatQuery =
            MutasiKasBank::query()
                ->with([
                    'rekeningKasBank',
                ])
                ->where(
                    'rekening_kas_bank_id',
                    $transaksiBank
                        ->rekening_kas_bank_id
                )
                ->whereBetween(
                    'tanggal',
                    [
                        $tanggalMulai,
                        $tanggalSelesai,
                    ]
                )
                ->whereDoesntHave(
                    'rekonsiliasiBank'
                );


        /*
        |--------------------------------------------------------------------------
        | NOMINAL + ARAH
        |--------------------------------------------------------------------------
        |
        | Internal:
        |
        | debit  > 0 = uang masuk
        | kredit > 0 = uang keluar
        |
        */

        if (
            $transaksiBank->jenis
            ===
            'MASUK'
        ) {

            $kandidatQuery
                ->where(
                    'debit',
                    '>',
                    0
                )
                ->where(
                    'debit',
                    $transaksiBank->jumlah
                );

        } else {

            $kandidatQuery
                ->where(
                    'kredit',
                    '>',
                    0
                )
                ->where(
                    'kredit',
                    $transaksiBank->jumlah
                );
        }


        /*
        |--------------------------------------------------------------------------
        | AMBIL KANDIDAT
        |--------------------------------------------------------------------------
        */

        $kandidatMutasis =
            $kandidatQuery
                ->orderBy(
                    'tanggal'
                )
                ->get()
                ->map(
                    function (
                        MutasiKasBank $mutasi
                    ) use (
                        $transaksiBank
                    ) {

                        return $this
                            ->tambahkanDetailSumber(
                                $mutasi,
                                $transaksiBank
                            );
                    }
                )
                ->sortByDesc(
                    'match_score'
                )
                ->values();


        $rekonsiliasi =
            null;


        return view(
            'rekonsiliasi-bank.show',
            compact(
                'transaksiBank',
                'rekonsiliasi',
                'kandidatMutasis',
                'namaBankTerdeteksi'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | COCOKKAN
    |--------------------------------------------------------------------------
    */

    public function cocokkan(
        Request $request,
        TransaksiBelumTeridentifikasi $transaksiBank
    ) {
        /*
        |--------------------------------------------------------------------------
        | VALIDASI
        |--------------------------------------------------------------------------
        */

        $validated =
            $request->validate([

                'mutasi_kas_bank_id' => [
                    'required',
                    'exists:mutasi_kas_banks,id',
                ],

                'catatan' => [
                    'nullable',
                    'string',
                ],

                'simpan_alias_supplier' => [
                    'nullable',
                    'boolean',
                ],

                'nama_alias_supplier' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
            ]);


        DB::transaction(
            function () use (
                $validated,
                $transaksiBank
            ) {
                /*
                |--------------------------------------------------------------------------
                | LOCK BANK
                |--------------------------------------------------------------------------
                */

                $bank =
                    TransaksiBelumTeridentifikasi::query()
                        ->whereKey(
                            $transaksiBank->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();


                /*
                |--------------------------------------------------------------------------
                | STATUS BANK
                |--------------------------------------------------------------------------
                */

                if (
                    $bank->status
                    !==
                    'BELUM_TERIDENTIFIKASI'
                ) {

                    throw ValidationException::withMessages([
                        'mutasi_kas_bank_id' =>
                            'Transaksi bank ini sudah diproses.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | LOCK MUTASI
                |--------------------------------------------------------------------------
                */

                $mutasi =
                    MutasiKasBank::query()
                        ->whereKey(
                            $validated[
                                'mutasi_kas_bank_id'
                            ]
                        )
                        ->lockForUpdate()
                        ->firstOrFail();


                /*
                |--------------------------------------------------------------------------
                | REKENING HARUS SAMA
                |--------------------------------------------------------------------------
                */

                if (
                    (int)
                    $mutasi
                        ->rekening_kas_bank_id
                    !==
                    (int)
                    $bank
                        ->rekening_kas_bank_id
                ) {

                    throw ValidationException::withMessages([
                        'mutasi_kas_bank_id' =>
                            'Rekening transaksi tidak sama dengan rekening pada rekening koran.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | CEK TANGGAL ±7 HARI
                |--------------------------------------------------------------------------
                */

                $selisihHari =
                    $this->hitungSelisihHari(
                        $mutasi->tanggal,
                        $bank->tanggal
                    );


                if (
                    $selisihHari > 7
                ) {

                    throw ValidationException::withMessages([
                        'mutasi_kas_bank_id' =>
                            'Tanggal transaksi berada di luar toleransi ±7 hari.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | CEK NOMINAL + ARAH
                |--------------------------------------------------------------------------
                */

                if (
                    !$this->nominalDanArahCocok(
                        $mutasi,
                        $bank
                    )
                ) {

                    throw ValidationException::withMessages([
                        'mutasi_kas_bank_id' =>
                            'Nominal atau arah transaksi tidak sesuai.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | MUTASI SUDAH DIREKONSILIASI?
                |--------------------------------------------------------------------------
                */

                $mutasiSudahDipakai =
                    RekonsiliasiBank::query()
                        ->where(
                            'mutasi_kas_bank_id',
                            $mutasi->id
                        )
                        ->exists();


                if (
                    $mutasiSudahDipakai
                ) {

                    throw ValidationException::withMessages([
                        'mutasi_kas_bank_id' =>
                            'Mutasi Kas/Bank ini sudah direkonsiliasi dengan transaksi bank lain.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | BANK SUDAH DIREKONSILIASI?
                |--------------------------------------------------------------------------
                */

                $bankSudahDipakai =
                    RekonsiliasiBank::query()
                        ->where(
                            'transaksi_bank_id',
                            $bank->id
                        )
                        ->exists();


                if (
                    $bankSudahDipakai
                ) {

                    throw ValidationException::withMessages([
                        'mutasi_kas_bank_id' =>
                            'Transaksi rekening koran ini sudah direkonsiliasi.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | SIMPAN ALIAS SUPPLIER
                |--------------------------------------------------------------------------
                */

                if (
                    !empty(
                        $validated[
                            'simpan_alias_supplier'
                        ]
                    )
                ) {
                    /*
                    |--------------------------------------------------------------------------
                    | HARUS PEMBAYARAN SUPPLIER
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $mutasi->reference_type
                        !==
                        'PEMBAYARAN_SUPPLIER'
                    ) {

                        throw ValidationException::withMessages([
                            'nama_alias_supplier' =>
                                'Alias supplier hanya dapat disimpan dari transaksi pembayaran supplier.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | NAMA ALIAS
                    |--------------------------------------------------------------------------
                    */

                    $namaAlias =
                        trim(
                            $validated[
                                'nama_alias_supplier'
                            ]
                            ?? ''
                        );


                    if (
                        $namaAlias === ''
                    ) {

                        throw ValidationException::withMessages([
                            'nama_alias_supplier' =>
                                'Nama alias supplier wajib diisi jika opsi simpan alias dicentang.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | PEMBAYARAN SUPPLIER
                    |--------------------------------------------------------------------------
                    */

                    $pembayaran =
                        PembayaranSupplier::query()
                            ->whereKey(
                                $mutasi
                                    ->reference_id
                            )
                            ->first();


                    if (
                        !$pembayaran
                    ) {

                        throw ValidationException::withMessages([
                            'nama_alias_supplier' =>
                                'Data pembayaran supplier tidak ditemukan.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | NORMALISASI ALIAS
                    |--------------------------------------------------------------------------
                    */

                    $normalized =
                        $this->normalisasiNama(
                            $namaAlias
                        );


                    if (
                        $normalized === ''
                    ) {

                        throw ValidationException::withMessages([
                            'nama_alias_supplier' =>
                                'Nama alias supplier tidak valid.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | SIMPAN ALIAS
                    |--------------------------------------------------------------------------
                    */

                    $alias =
                        SupplierRekeningAlias::firstOrCreate(
                            [
                                'supplier_id' =>
                                    $pembayaran
                                        ->supplier_id,

                                'rekening_kas_bank_id' =>
                                    $bank
                                        ->rekening_kas_bank_id,

                                'nama_alias_normalized' =>
                                    $normalized,
                            ],

                            [
                                'nama_alias' =>
                                    strtoupper(
                                        $namaAlias
                                    ),

                                'sumber' =>
                                    'REKONSILIASI',

                                'is_active' =>
                                    true,

                                'created_by' =>
                                    auth()->id(),
                            ]
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | AKTIFKAN KEMBALI JIKA NONAKTIF
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !$alias->is_active
                    ) {

                        $alias->update([
                            'is_active' =>
                                true,
                        ]);
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | BUAT REKONSILIASI
                |--------------------------------------------------------------------------
                */

                RekonsiliasiBank::create([

                    'transaksi_bank_id' =>
                        $bank->id,

                    'mutasi_kas_bank_id' =>
                        $mutasi->id,

                    'tanggal_rekonsiliasi' =>
                        now(),

                    'catatan' =>
                        $validated[
                            'catatan'
                        ]
                        ?? null,

                    'created_by' =>
                        auth()->id(),
                ]);


                /*
                |--------------------------------------------------------------------------
                | UPDATE BANK
                |--------------------------------------------------------------------------
                */

                $bank->update([

                    'status' =>
                        'TERIDENTIFIKASI',

                    'matched_reference_type' =>
                        $mutasi
                            ->reference_type
                        ?: 'MUTASI_KAS_BANK',

                    'matched_reference_id' =>
                        $mutasi
                            ->reference_id
                        ?: $mutasi->id,

                    'identified_at' =>
                        now(),

                    'identified_by' =>
                        auth()->id(),
                ]);
            }
        );


        return redirect()
            ->route(
                'rekonsiliasi-bank.show',
                $transaksiBank
            )
            ->with(
                'success',
                'Transaksi berhasil direkonsiliasi.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | BATALKAN REKONSILIASI
    |--------------------------------------------------------------------------
    */

    public function batalkan(
        TransaksiBelumTeridentifikasi $transaksiBank
    ) {
        DB::transaction(
            function () use (
                $transaksiBank
            ) {
                /*
                |--------------------------------------------------------------------------
                | LOCK BANK
                |--------------------------------------------------------------------------
                */

                $bank =
                    TransaksiBelumTeridentifikasi::query()
                        ->whereKey(
                            $transaksiBank->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();


                /*
                |--------------------------------------------------------------------------
                | REKONSILIASI
                |--------------------------------------------------------------------------
                */

                $rekonsiliasi =
                    RekonsiliasiBank::query()
                        ->where(
                            'transaksi_bank_id',
                            $bank->id
                        )
                        ->lockForUpdate()
                        ->first();


                if (
                    !$rekonsiliasi
                ) {

                    throw ValidationException::withMessages([
                        'rekonsiliasi' =>
                            'Data rekonsiliasi tidak ditemukan.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | HAPUS
                |--------------------------------------------------------------------------
                */

                $rekonsiliasi
                    ->delete();


                /*
                |--------------------------------------------------------------------------
                | RESET BANK
                |--------------------------------------------------------------------------
                */

                $bank->update([

                    'status' =>
                        'BELUM_TERIDENTIFIKASI',

                    'matched_reference_type' =>
                        null,

                    'matched_reference_id' =>
                        null,

                    'identified_at' =>
                        null,

                    'identified_by' =>
                        null,
                ]);


                /*
                 * Alias supplier sengaja tidak dihapus.
                 */
            }
        );


        return redirect()
            ->route(
                'rekonsiliasi-bank.show',
                $transaksiBank
            )
            ->with(
                'success',
                'Rekonsiliasi berhasil dibatalkan.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | DETAIL SUMBER
    |--------------------------------------------------------------------------
    */

    private function tambahkanDetailSumber(
        MutasiKasBank $mutasi,
        TransaksiBelumTeridentifikasi $transaksiBank
    ): MutasiKasBank {
        /*
        |--------------------------------------------------------------------------
        | DEFAULT
        |--------------------------------------------------------------------------
        */

        $mutasi->sumber_jenis =
            $mutasi->reference_type
            ?: $mutasi->tipe
            ?: 'MUTASI_KAS_BANK';


        $mutasi->sumber_nama =
            null;


        $mutasi->sumber_nomor =
            $mutasi->nomor_referensi;


        $mutasi->sumber_invoice =
            null;


        $mutasi->sumber_tanggal =
            $mutasi->tanggal;


        $mutasi->sumber_jumlah =
            (float) (
                (float) $mutasi->debit > 0
                    ? $mutasi->debit
                    : $mutasi->kredit
            );


        /*
        |--------------------------------------------------------------------------
        | MATCH DEFAULT
        |--------------------------------------------------------------------------
        */

        $mutasi->nama_cocok =
            false;


        $mutasi->alias_cocok =
            false;


        $mutasi->alias_terdeteksi =
            null;


        /*
        |--------------------------------------------------------------------------
        | PEMBAYARAN SUPPLIER
        |--------------------------------------------------------------------------
        */

        if (
            $mutasi->reference_type
            ===
            'PEMBAYARAN_SUPPLIER'
        ) {

            $pembayaran =
                PembayaranSupplier::with([
                    'supplier',
                    'invoiceSupplier',
                ])
                    ->find(
                        $mutasi
                            ->reference_id
                    );


            if (
                $pembayaran
            ) {

                $mutasi->sumber_jenis =
                    'PEMBAYARAN_SUPPLIER';


                $mutasi->sumber_nama =
                    $pembayaran
                        ->supplier
                        ?->nama_supplier;


                $mutasi->sumber_nomor =
                    $pembayaran
                        ->nomor_pembayaran
                    ?: $mutasi
                        ->nomor_referensi;


                $mutasi->sumber_invoice =
                    $pembayaran
                        ->invoiceSupplier
                        ?->nomor_invoice_internal
                    ?: $pembayaran
                        ->invoiceSupplier
                        ?->nomor_invoice_supplier;


                $mutasi->sumber_tanggal =
                    $pembayaran
                        ->tanggal_pembayaran
                    ?: $mutasi
                        ->tanggal;


                $mutasi->sumber_jumlah =
                    (float)
                    $pembayaran
                        ->jumlah_bayar;


                /*
                |--------------------------------------------------------------------------
                | NAMA SUPPLIER LANGSUNG
                |--------------------------------------------------------------------------
                */

                $namaSupplier =
                    $pembayaran
                        ->supplier
                        ?->nama_supplier;


                if (
                    $namaSupplier
                    &&
                    $this->namaTerdapatDiBank(
                        $namaSupplier,
                        $transaksiBank
                    )
                ) {

                    $mutasi->nama_cocok =
                        true;
                }


                /*
                |--------------------------------------------------------------------------
                | CEK ALIAS
                |--------------------------------------------------------------------------
                */

                if (
                    $pembayaran->supplier_id
                ) {

                    $alias =
                        $this->cariAliasCocok(
                            (int)
                            $pembayaran
                                ->supplier_id,
                            $transaksiBank
                        );


                    if (
                        $alias
                    ) {

                        $mutasi->alias_cocok =
                            true;


                        $mutasi->alias_terdeteksi =
                            $alias
                                ->nama_alias;
                    }
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | SELISIH HARI
        |--------------------------------------------------------------------------
        */

        $mutasi->selisih_hari =
            $this->hitungSelisihHari(
                $mutasi->tanggal,
                $transaksiBank->tanggal
            );


        /*
        |--------------------------------------------------------------------------
        | MATCH NOMINAL
        |--------------------------------------------------------------------------
        */

        $mutasi->match_nominal =
            $this->nominalSama(
                $mutasi,
                $transaksiBank
            );


        /*
        |--------------------------------------------------------------------------
        | MATCH ARAH
        |--------------------------------------------------------------------------
        */

        $mutasi->match_arah =
            $this->arahSama(
                $mutasi,
                $transaksiBank
            );


        /*
        |--------------------------------------------------------------------------
        | MATCH TANGGAL
        |--------------------------------------------------------------------------
        |
        | Kandidat tetap diperbolehkan sampai 7 hari.
        |
        */

        $mutasi->match_tanggal =
            $mutasi->selisih_hari
            <=
            7;


        /*
        |--------------------------------------------------------------------------
        | SCORE
        |--------------------------------------------------------------------------
        */

        $score =
            0;


        /*
        |--------------------------------------------------------------------------
        | NOMINAL EXACT = 50
        |--------------------------------------------------------------------------
        */

        if (
            $mutasi->match_nominal
        ) {

            $score +=
                50;
        }


        /*
        |--------------------------------------------------------------------------
        | ARAH = 25
        |--------------------------------------------------------------------------
        */

        if (
            $mutasi->match_arah
        ) {

            $score +=
                25;
        }


        /*
        |--------------------------------------------------------------------------
        | TANGGAL
        |--------------------------------------------------------------------------
        |
        | 0 hari = +20
        | 1 hari = +15
        | 2 hari = +10
        | 3 hari = +5
        | 4-7 hari = +0
        |
        */

        switch (
            (int)
            $mutasi->selisih_hari
        ) {

            case 0:

                $score +=
                    20;

                break;


            case 1:

                $score +=
                    15;

                break;


            case 2:

                $score +=
                    10;

                break;


            case 3:

                $score +=
                    5;

                break;


            default:

                /*
                 * 4 sampai 7 hari:
                 * tetap kandidat tetapi tidak dapat poin tanggal.
                 */

                break;
        }


        /*
        |--------------------------------------------------------------------------
        | NAMA / ALIAS = 5
        |--------------------------------------------------------------------------
        */

        if (
            $mutasi->nama_cocok
            ||
            $mutasi->alias_cocok
        ) {

            $score +=
                5;
        }


        /*
        |--------------------------------------------------------------------------
        | FINAL SCORE
        |--------------------------------------------------------------------------
        */

        $mutasi->match_score =
            min(
                $score,
                100
            );


        /*
        |--------------------------------------------------------------------------
        | LABEL
        |--------------------------------------------------------------------------
        */

        if (
            $mutasi->match_score
            >=
            95
        ) {

            $mutasi->match_label =
                'SANGAT KUAT';

        } elseif (
            $mutasi->match_score
            >=
            85
        ) {

            $mutasi->match_label =
                'KUAT';

        } elseif (
            $mutasi->match_score
            >=
            75
        ) {

            $mutasi->match_label =
                'CUKUP';

        } else {

            $mutasi->match_label =
                'PERLU CEK';
        }


        return $mutasi;
    }


    /*
    |--------------------------------------------------------------------------
    | NOMINAL + ARAH
    |--------------------------------------------------------------------------
    */

    private function nominalDanArahCocok(
        MutasiKasBank $mutasi,
        TransaksiBelumTeridentifikasi $bank
    ): bool {

        return
            $this->nominalSama(
                $mutasi,
                $bank
            )
            &&
            $this->arahSama(
                $mutasi,
                $bank
            );
    }


    /*
    |--------------------------------------------------------------------------
    | NOMINAL
    |--------------------------------------------------------------------------
    */

    private function nominalSama(
        MutasiKasBank $mutasi,
        TransaksiBelumTeridentifikasi $bank
    ): bool {

        $jumlahBank =
            round(
                (float)
                $bank->jumlah,
                2
            );


        if (
            $bank->jenis
            ===
            'MASUK'
        ) {

            $jumlahMutasi =
                round(
                    (float)
                    $mutasi->debit,
                    2
                );

        } else {

            $jumlahMutasi =
                round(
                    (float)
                    $mutasi->kredit,
                    2
                );
        }


        return
            abs(
                $jumlahMutasi
                -
                $jumlahBank
            )
            <
            0.01;
    }


    /*
    |--------------------------------------------------------------------------
    | ARAH
    |--------------------------------------------------------------------------
    */

    private function arahSama(
        MutasiKasBank $mutasi,
        TransaksiBelumTeridentifikasi $bank
    ): bool {

        if (
            $bank->jenis
            ===
            'MASUK'
        ) {

            return
                (float)
                $mutasi->debit
                >
                0
                &&
                (float)
                $mutasi->kredit
                <=
                0;
        }


        if (
            $bank->jenis
            ===
            'KELUAR'
        ) {

            return
                (float)
                $mutasi->kredit
                >
                0
                &&
                (float)
                $mutasi->debit
                <=
                0;
        }


        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | SELISIH HARI
    |--------------------------------------------------------------------------
    */

    private function hitungSelisihHari(
        $tanggalMutasi,
        $tanggalBank
    ): int {

        $tanggalMutasi =
            Carbon::parse(
                $tanggalMutasi
            )
                ->startOfDay();


        $tanggalBank =
            Carbon::parse(
                $tanggalBank
            )
                ->startOfDay();


        return
            (int)
            round(
                abs(
                    $tanggalMutasi
                        ->diffInDays(
                            $tanggalBank
                        )
                )
            );
    }


    /*
    |--------------------------------------------------------------------------
    | CARI ALIAS SUPPLIER
    |--------------------------------------------------------------------------
    */

    private function cariAliasCocok(
        int $supplierId,
        TransaksiBelumTeridentifikasi $bank
    ): ?SupplierRekeningAlias {

        $textBank =
            $this->textBank(
                $bank
            );


        if (
            $textBank === ''
        ) {

            return null;
        }


        $aliases =
            SupplierRekeningAlias::query()
                ->where(
                    'supplier_id',
                    $supplierId
                )
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    function ($query) use ($bank) {

                        $query
                            ->whereNull(
                                'rekening_kas_bank_id'
                            )
                            ->orWhere(
                                'rekening_kas_bank_id',
                                $bank
                                    ->rekening_kas_bank_id
                            );
                    }
                )
                ->get();


        foreach (
            $aliases as $alias
        ) {

            $normalizedAlias =
                $alias
                    ->nama_alias_normalized
                ?: $this->normalisasiNama(
                    $alias
                        ->nama_alias
                );


            if (
                $normalizedAlias === ''
            ) {

                continue;
            }


            if (
                str_contains(
                    $textBank,
                    $normalizedAlias
                )
            ) {

                return $alias;
            }
        }


        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | CEK NAMA SUPPLIER
    |--------------------------------------------------------------------------
    */

    private function namaTerdapatDiBank(
        string $namaSupplier,
        TransaksiBelumTeridentifikasi $bank
    ): bool {

        $namaSupplier =
            $this->normalisasiNama(
                $namaSupplier
            );


        if (
            $namaSupplier === ''
        ) {

            return false;
        }


        $textBank =
            $this->textBank(
                $bank
            );


        if (
            $textBank === ''
        ) {

            return false;
        }


        return str_contains(
            $textBank,
            $namaSupplier
        );
    }


    /*
    |--------------------------------------------------------------------------
    | TEXT BANK
    |--------------------------------------------------------------------------
    */

    private function textBank(
        TransaksiBelumTeridentifikasi $bank
    ): string {

        $text =
            implode(
                ' ',
                array_filter([
                    $bank
                        ->nama_pengirim_penerima,

                    $bank
                        ->deskripsi_bank,

                    $bank
                        ->nomor_referensi_bank,
                ])
            );


        return
            $this->normalisasiNama(
                $text
            );
    }


    /*
    |--------------------------------------------------------------------------
    | AMBIL NAMA BANK
    |--------------------------------------------------------------------------
    */

    private function ambilNamaBank(
        TransaksiBelumTeridentifikasi $bank
    ): ?string {

        $nama =
            trim(
                (string)
                $bank
                    ->nama_pengirim_penerima
            );


        if (
            $nama === ''
        ) {

            return null;
        }


        return strtoupper(
            $nama
        );
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALISASI NAMA
    |--------------------------------------------------------------------------
    */

    private function normalisasiNama(
        ?string $value
    ): string {

        if (
            !$value
        ) {

            return '';
        }


        $value =
            Str::upper(
                $value
            );


        $value =
            preg_replace(
                '/[^A-Z0-9]+/',
                ' ',
                $value
            );


        $value =
            preg_replace(
                '/\s+/',
                ' ',
                $value
            );


        return trim(
            $value
        );
    }
}