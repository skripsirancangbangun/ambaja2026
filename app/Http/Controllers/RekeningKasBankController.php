<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\MutasiKasBank;
use App\Models\RekeningKasBank;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RekeningKasBankController extends Controller
{
    /**
     * Daftar Kas & Rekening Bank.
     */
    public function index(Request $request)
    {
        $query = RekeningKasBank::query()
            ->with('akun');

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim(
                $request->search
            );

            $query->where(
                function ($q) use ($search) {
                    $q->where(
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
                        )
                        ->orWhere(
                            'nomor_rekening',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhere(
                            'atas_nama',
                            'like',
                            '%' . $search . '%'
                        )
                        ->orWhereHas(
                            'akun',
                            function ($akunQuery) use ($search) {
                                $akunQuery
                                    ->where(
                                        'kode_akun',
                                        'like',
                                        '%' . $search . '%'
                                    )
                                    ->orWhere(
                                        'nama_akun',
                                        'like',
                                        '%' . $search . '%'
                                    );
                            }
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER JENIS
        |--------------------------------------------------------------------------
        */

        if ($request->filled('jenis')) {
            $query->where(
                'jenis',
                $request->jenis
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER STATUS
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {
            $query->where(
                'is_active',
                $request->status === 'aktif'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | GET DATA
        |--------------------------------------------------------------------------
        */

        $rekeningKasBanks = $query
            ->orderByRaw("
                CASE
                    WHEN jenis = 'KAS' THEN 1
                    WHEN jenis = 'BANK' THEN 2
                    ELSE 3
                END
            ")
            ->orderBy(
                'nama_rekening'
            )
            ->paginate(20)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | HITUNG SALDO SAAT INI
        |--------------------------------------------------------------------------
        |
        | Saldo Saat Ini:
        |
        | Saldo Awal
        | + Total Debit / Uang Masuk
        | - Total Kredit / Uang Keluar
        |
        | Jangan hanya menggunakan saldo pada mutasi terakhir.
        | Hal ini penting karena saldo awal rekening bisa pernah
        | dikoreksi / disinkronisasi dengan COA.
        |
        */

        $rekeningIds = $rekeningKasBanks
            ->getCollection()
            ->pluck('id');

        $ringkasanMutasi = collect();

        if ($rekeningIds->isNotEmpty()) {
            $ringkasanMutasi =
                MutasiKasBank::query()
                    ->whereIn(
                        'rekening_kas_bank_id',
                        $rekeningIds
                    )
                    ->selectRaw('
                        rekening_kas_bank_id,
                        COALESCE(SUM(debit), 0) AS total_debit,
                        COALESCE(SUM(kredit), 0) AS total_kredit
                    ')
                    ->groupBy(
                        'rekening_kas_bank_id'
                    )
                    ->get()
                    ->keyBy(
                        'rekening_kas_bank_id'
                    );
        }

        $rekeningKasBanks
            ->getCollection()
            ->transform(
                function ($rekening) use ($ringkasanMutasi) {
                    $ringkasan =
                        $ringkasanMutasi->get(
                            $rekening->id
                        );

                    $saldoAwal =
                        (float) $rekening->saldo_awal;

                    $totalDebit =
                        (float) (
                            $ringkasan
                                ?->total_debit
                            ?? 0
                        );

                    $totalKredit =
                        (float) (
                            $ringkasan
                                ?->total_kredit
                            ?? 0
                        );

                    $saldoSaatIni =
                        round(
                            $saldoAwal
                            + $totalDebit
                            - $totalKredit,
                            2
                        );

                    $rekening->setAttribute(
                        'total_debit_mutasi',
                        $totalDebit
                    );

                    $rekening->setAttribute(
                        'total_kredit_mutasi',
                        $totalKredit
                    );

                    $rekening->setAttribute(
                        'saldo_saat_ini',
                        $saldoSaatIni
                    );

                    /*
                     * Cek sinkronisasi saldo awal rekening
                     * dengan saldo awal akun COA.
                     */
                    $saldoAwalCoa =
                        $rekening->akun
                            ? (float)
                                $rekening
                                    ->akun
                                    ->saldo_awal
                            : null;

                    $rekening->setAttribute(
                        'saldo_awal_coa',
                        $saldoAwalCoa
                    );

                    $rekening->setAttribute(
                        'saldo_awal_tidak_sinkron',
                        $saldoAwalCoa !== null
                        &&
                        abs(
                            $saldoAwal
                            - $saldoAwalCoa
                        ) > 0.01
                    );

                    return $rekening;
                }
            );

        return view(
            'rekening-kas-bank.index',
            compact(
                'rekeningKasBanks'
            )
        );
    }

    /**
     * Form tambah.
     */
    public function create()
    {
        $akunKasBanks =
            $this->getAkunKasBankTersedia();

        return view(
            'rekening-kas-bank.create',
            compact(
                'akunKasBanks'
            )
        );
    }

    /**
     * Simpan.
     */
    public function store(Request $request)
    {
        $validated =
            $this->validateData(
                $request
            );

        /*
        |--------------------------------------------------------------------------
        | SALDO AWAL DARI COA
        |--------------------------------------------------------------------------
        */

        $akun =
            Akun::query()
                ->findOrFail(
                    $validated['akun_id']
                );

        $validated['saldo_awal'] =
            (float) $akun->saldo_awal;

        /*
        |--------------------------------------------------------------------------
        | JENIS KAS
        |--------------------------------------------------------------------------
        */

        if (
            $validated['jenis']
            === 'KAS'
        ) {
            $validated['nama_bank'] =
                null;

            $validated['nomor_rekening'] =
                null;

            $validated['atas_nama'] =
                null;
        }

        RekeningKasBank::create(
            $validated
        );

        return redirect()
            ->route(
                'rekening-kas-bank.index'
            )
            ->with(
                'success',
                'Kas/Rekening Bank berhasil ditambahkan dan telah terhubung dengan akun COA.'
            );
    }

    /**
     * Form edit.
     */
    public function edit(
        RekeningKasBank $rekeningKasBank
    ) {
        $rekeningKasBank
            ->loadMissing(
                'akun'
            );

        $hasTransactions =
            MutasiKasBank::query()
                ->where(
                    'rekening_kas_bank_id',
                    $rekeningKasBank->id
                )
                ->exists();

        $akunKasBanks =
            $this->getAkunKasBankTersedia(
                $rekeningKasBank
            );

        return view(
            'rekening-kas-bank.edit',
            compact(
                'rekeningKasBank',
                'akunKasBanks',
                'hasTransactions'
            )
        );
    }

    /**
     * Update.
     */
    public function update(
        Request $request,
        RekeningKasBank $rekeningKasBank
    ) {
        $validated =
            $this->validateData(
                $request,
                $rekeningKasBank
            );

        $hasTransactions =
            MutasiKasBank::query()
                ->where(
                    'rekening_kas_bank_id',
                    $rekeningKasBank->id
                )
                ->exists();

        /*
        |--------------------------------------------------------------------------
        | MAPPING COA DIKUNCI SETELAH ADA TRANSAKSI
        |--------------------------------------------------------------------------
        */

        if (
            $hasTransactions
            &&
            (int) $validated['akun_id']
            !==
            (int) $rekeningKasBank->akun_id
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'akun_id' =>
                        'Akun COA Kas/Rekening Bank tidak dapat diganti karena rekening sudah memiliki transaksi.',
                ]);
        }

        $akun =
            Akun::query()
                ->findOrFail(
                    $validated['akun_id']
                );

        /*
        |--------------------------------------------------------------------------
        | SINKRONKAN SALDO AWAL DENGAN COA
        |--------------------------------------------------------------------------
        */

        $validated['saldo_awal'] =
            (float) $akun->saldo_awal;

        /*
        |--------------------------------------------------------------------------
        | JENIS KAS
        |--------------------------------------------------------------------------
        */

        if (
            $validated['jenis']
            === 'KAS'
        ) {
            $validated['nama_bank'] =
                null;

            $validated['nomor_rekening'] =
                null;

            $validated['atas_nama'] =
                null;
        }

        $rekeningKasBank->update(
            $validated
        );

        return redirect()
            ->route(
                'rekening-kas-bank.index'
            )
            ->with(
                'success',
                'Kas/Rekening Bank berhasil diperbarui dan saldo awal disinkronkan dengan COA.'
            );
    }

    /**
     * Hapus.
     */
    public function destroy(
        RekeningKasBank $rekeningKasBank
    ) {
        /*
        |--------------------------------------------------------------------------
        | REKENING YANG SUDAH DIPAKAI TIDAK BOLEH DIHAPUS
        |--------------------------------------------------------------------------
        */

        if (
            $rekeningKasBank
                ->mutasiKasBanks()
                ->exists()
        ) {
            return redirect()
                ->route(
                    'rekening-kas-bank.index'
                )
                ->with(
                    'error',
                    'Kas/Rekening Bank tidak dapat dihapus karena sudah memiliki transaksi. Ubah status menjadi Nonaktif.'
                );
        }

        try {
            $rekeningKasBank->delete();

        } catch (QueryException $e) {

            return redirect()
                ->route(
                    'rekening-kas-bank.index'
                )
                ->with(
                    'error',
                    'Kas/Rekening Bank tidak dapat dihapus karena sudah digunakan pada data lain. Ubah status menjadi Nonaktif.'
                );
        }

        return redirect()
            ->route(
                'rekening-kas-bank.index'
            )
            ->with(
                'success',
                'Kas/Rekening Bank berhasil dihapus.'
            );
    }

    /**
     * Validasi create/update.
     */
    private function validateData(
        Request $request,
        ?RekeningKasBank $rekeningKasBank = null
    ): array {
        /*
        |--------------------------------------------------------------------------
        | COA TIDAK BOLEH DIPAKAI DUA REKENING
        |--------------------------------------------------------------------------
        */

        $akunDipakaiRekeningLain =
            RekeningKasBank::query()
                ->when(
                    $rekeningKasBank,
                    fn ($query) =>
                        $query->where(
                            'id',
                            '!=',
                            $rekeningKasBank->id
                        )
                )
                ->whereNotNull(
                    'akun_id'
                )
                ->pluck(
                    'akun_id'
                )
                ->all();

        return $request->validate([
            'akun_id' => [
                'required',

                Rule::exists(
                    'akuns',
                    'id'
                )->where(
                    function ($query) use (
                        $akunDipakaiRekeningLain
                    ) {
                        $query
                            ->where(
                                'is_active',
                                true
                            )
                            ->where(
                                'kategori_akun',
                                'Aset'
                            )
                            ->where(
                                'sub_kategori',
                                'Aset Lancar'
                            )
                            ->where(
                                'saldo_normal',
                                'DEBIT'
                            );

                        if (
                            !empty(
                                $akunDipakaiRekeningLain
                            )
                        ) {
                            $query
                                ->whereNotIn(
                                    'id',
                                    $akunDipakaiRekeningLain
                                );
                        }
                    }
                ),
            ],

            'kode_rekening' => [
                'required',
                'string',
                'max:30',

                Rule::unique(
                    'rekening_kas_banks',
                    'kode_rekening'
                )->ignore(
                    $rekeningKasBank?->id
                ),
            ],

            'nama_rekening' => [
                'required',
                'string',
                'max:150',
            ],

            'jenis' => [
                'required',

                Rule::in([
                    'KAS',
                    'BANK',
                ]),
            ],

            'nama_bank' => [
                'nullable',
                'required_if:jenis,BANK',
                'string',
                'max:100',
            ],

            'nomor_rekening' => [
                'nullable',
                'required_if:jenis,BANK',
                'string',
                'max:100',
            ],

            'atas_nama' => [
                'nullable',
                'required_if:jenis,BANK',
                'string',
                'max:150',
            ],

            /*
             * saldo_awal tidak diterima dari request.
             * Selalu diambil dari saldo awal COA.
             */

            'is_active' => [
                'required',
                'boolean',
            ],

            'catatan' => [
                'nullable',
                'string',
            ],
        ]);
    }

    /**
     * Akun yang boleh digunakan sebagai Kas/Bank.
     */
    private function getAkunKasBankTersedia(
        ?RekeningKasBank $rekeningKasBank = null
    ) {
        $akunDipakai =
            RekeningKasBank::query()
                ->when(
                    $rekeningKasBank,
                    fn ($query) =>
                        $query->where(
                            'id',
                            '!=',
                            $rekeningKasBank->id
                        )
                )
                ->whereNotNull(
                    'akun_id'
                )
                ->pluck(
                    'akun_id'
                );

        return Akun::query()
            ->where(
                'is_active',
                true
            )
            ->where(
                'kategori_akun',
                'Aset'
            )
            ->where(
                'sub_kategori',
                'Aset Lancar'
            )
            ->where(
                'saldo_normal',
                'DEBIT'
            )
            ->when(
                $akunDipakai->isNotEmpty(),
                fn ($query) =>
                    $query->whereNotIn(
                        'id',
                        $akunDipakai
                    )
            )
            ->orderBy(
                'kode_akun'
            )
            ->get();
    }
}