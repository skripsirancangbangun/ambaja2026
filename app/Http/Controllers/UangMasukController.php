<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\RekeningKasBank;
use App\Models\UangMasuk;
use App\Services\KasBankService;
use App\Services\SystemAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

class UangMasukController extends Controller
{
    public function index(Request $request)
    {
        $query = UangMasuk::query()
            ->with([
                'rekeningKasBank',
                'akunLawan',
                'creator',
            ]);

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where(
                    'nomor_transaksi',
                    'like',
                    '%' . $search . '%'
                )
                    ->orWhere(
                        'sumber',
                        'like',
                        '%' . $search . '%'
                    )
                    ->orWhere(
                        'referensi',
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
                                );
                        }
                    )
                    ->orWhereHas(
                        'akunLawan',
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
            });
        }

        if ($request->filled('rekening_kas_bank_id')) {
            $query->where(
                'rekening_kas_bank_id',
                $request->rekening_kas_bank_id
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }

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

        $uangMasuks = $query
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $rekeningKasBanks =
            $this->getRekeningKasBanks();

        return view(
            'uang-masuk.index',
            compact(
                'uangMasuks',
                'rekeningKasBanks'
            )
        );
    }

    public function create()
    {
        $rekeningKasBanks =
            $this->getRekeningKasBanks();

        $akunLawans =
            $this->getAkunLawans();

        return view(
            'uang-masuk.create',
            compact(
                'rekeningKasBanks',
                'akunLawans'
            )
        );
    }

    public function store(Request $request)
    {
        $validated =
            $request->validate(
                $this->validationRules()
            );

        $uangMasuk = DB::transaction(
            function () use ($validated) {
                return UangMasuk::create([
                    'nomor_transaksi' =>
                        $this->generateNomorTransaksi(),

                    'tanggal' =>
                        $validated['tanggal'],

                    'rekening_kas_bank_id' =>
                        $validated[
                            'rekening_kas_bank_id'
                        ],

                    'akun_lawan_id' =>
                        $validated[
                            'akun_lawan_id'
                        ],

                    'jumlah' =>
                        $validated['jumlah'],

                    'sumber' =>
                        $validated['sumber'],

                    'referensi' =>
                        $validated['referensi']
                        ?? null,

                    'keterangan' =>
                        $validated['keterangan']
                        ?? null,

                    'status' =>
                        'DRAFT',

                    'created_by' =>
                        auth()->id(),
                ]);
            }
        );

        return redirect()
            ->route(
                'uang-masuk.show',
                $uangMasuk
            )
            ->with(
                'success',
                'Uang Masuk berhasil dibuat sebagai draft.'
            );
    }

    public function show(
        UangMasuk $uangMasuk
    ) {
        $uangMasuk->load([
            'rekeningKasBank.akun',
            'akunLawan',
            'creator',
            'mutasiKasBank',
            'jurnal.details.akun',
        ]);

        return view(
            'uang-masuk.show',
            compact('uangMasuk')
        );
    }

    public function edit(
        UangMasuk $uangMasuk
    ) {
        if (
            $uangMasuk->status
            !== 'DRAFT'
        ) {
            return redirect()
                ->route(
                    'uang-masuk.show',
                    $uangMasuk
                )
                ->with(
                    'error',
                    'Transaksi yang sudah tercatat tidak dapat diedit.'
                );
        }

        $rekeningKasBanks =
            RekeningKasBank::query()
                ->where(
                    function ($query) use (
                        $uangMasuk
                    ) {
                        $query
                            ->where(
                                'is_active',
                                true
                            )
                            ->orWhere(
                                'id',
                                $uangMasuk
                                    ->rekening_kas_bank_id
                            );
                    }
                )
                ->whereNotNull('akun_id')
                ->with('akun')
                ->orderBy('jenis')
                ->orderBy('nama_rekening')
                ->get();

        $akunLawans =
            $this->getAkunLawans(
                $uangMasuk->akun_lawan_id
            );

        return view(
            'uang-masuk.edit',
            compact(
                'uangMasuk',
                'rekeningKasBanks',
                'akunLawans'
            )
        );
    }

    public function update(
        Request $request,
        UangMasuk $uangMasuk
    ) {
        if ($uangMasuk->status !== 'DRAFT') {
            return redirect()
                ->route('uang-masuk.show', $uangMasuk)
                ->with(
                    'error',
                    'Transaksi yang sudah tercatat tidak dapat diubah.'
                );
        }

        $validated = $request->validate($this->validationRules());

        // Re-check status while holding a row lock. This closes the race
        // between a concurrent update/delete and the posting operation.
        $updated = DB::transaction(function () use ($validated, $uangMasuk) {
            $locked = UangMasuk::query()
                ->lockForUpdate()
                ->findOrFail($uangMasuk->id);

            if ($locked->status !== 'DRAFT') {
                return false;
            }

            $locked->update([
                'tanggal' => $validated['tanggal'],
                'rekening_kas_bank_id' => $validated['rekening_kas_bank_id'],
                'akun_lawan_id' => $validated['akun_lawan_id'],
                'jumlah' => $validated['jumlah'],
                'sumber' => $validated['sumber'],
                'referensi' => $validated['referensi'] ?? null,
                'keterangan' => $validated['keterangan'] ?? null,
            ]);

            return true;
        });

        if (!$updated) {
            return redirect()
                ->route('uang-masuk.show', $uangMasuk)
                ->with(
                    'error',
                    'Transaksi sudah tercatat oleh proses lain dan tidak dapat diubah.'
                );
        }

        return redirect()
            ->route('uang-masuk.show', $uangMasuk)
            ->with('success', 'Draft Uang Masuk berhasil diperbarui.');
    }

    public function destroy(
        UangMasuk $uangMasuk
    ) {
        if ($uangMasuk->status !== 'DRAFT') {
            return redirect()
                ->route('uang-masuk.show', $uangMasuk)
                ->with(
                    'error',
                    'Transaksi yang sudah tercatat tidak dapat dihapus.'
                );
        }

        $deleted = DB::transaction(function () use ($uangMasuk) {
            $locked = UangMasuk::query()
                ->lockForUpdate()
                ->findOrFail($uangMasuk->id);

            if ($locked->status !== 'DRAFT') {
                return false;
            }

            $locked->delete();
            return true;
        });

        if (!$deleted) {
            return redirect()
                ->route('uang-masuk.show', $uangMasuk)
                ->with(
                    'error',
                    'Transaksi sudah tercatat oleh proses lain dan tidak dapat dihapus.'
                );
        }

        return redirect()
            ->route('uang-masuk.index')
            ->with('success', 'Draft Uang Masuk berhasil dihapus.');
    }



    public function catat(
        UangMasuk $uangMasuk,
        KasBankService $kasBankService,
        SystemAccountingService $accountingService
    ) {
        try {
            DB::transaction(
                function () use (
                    $uangMasuk,
                    $kasBankService,
                    $accountingService
                ) {
                    $uangMasuk =
                        UangMasuk::query()
                            ->with([
                                'rekeningKasBank.akun',
                                'akunLawan',
                            ])
                            ->lockForUpdate()
                            ->findOrFail(
                                $uangMasuk->id
                            );

                    if (
                        $uangMasuk->status
                        !== 'DRAFT'
                    ) {
                        throw new RuntimeException(
                            'Transaksi Uang Masuk ini sudah tercatat.'
                        );
                    }

                    if (
                        !$uangMasuk
                            ->rekeningKasBank
                    ) {
                        throw new RuntimeException(
                            'Kas/Rekening Bank tidak ditemukan.'
                        );
                    }

                    if (
                        !$uangMasuk
                            ->rekeningKasBank
                            ->akun_id
                    ) {
                        throw new RuntimeException(
                            'Kas/Rekening Bank belum terhubung dengan akun COA.'
                        );
                    }

                    if (
                        !$uangMasuk
                            ->akun_lawan_id
                    ) {
                        throw new RuntimeException(
                            'Akun lawan Uang Masuk belum dipilih.'
                        );
                    }

                    if (
                        (int) $uangMasuk
                            ->rekeningKasBank
                            ->akun_id
                        ===
                        (int) $uangMasuk
                            ->akun_lawan_id
                    ) {
                        throw new RuntimeException(
                            'Akun Kas/Bank dan akun lawan Uang Masuk tidak boleh sama.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | 1. MUTASI KAS/BANK
                    |--------------------------------------------------------------------------
                    */

                    $kasBankService->uangMasuk(
                        rekeningKasBankId:
                            (int) $uangMasuk
                                ->rekening_kas_bank_id,

                        jumlah:
                            (float) $uangMasuk
                                ->jumlah,

                        referenceType:
                            'UANG_MASUK',

                        referenceId:
                            (int) $uangMasuk
                                ->id,

                        nomorReferensi:
                            $uangMasuk
                                ->nomor_transaksi,

                        keterangan:
                            $this
                                ->buildKeteranganMutasi(
                                    $uangMasuk
                                ),

                        tanggalTransaksi:
                            $uangMasuk
                                ->tanggal
                                ->format('Y-m-d'),

                        tipe:
                            'UANG_MASUK'
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | 2. JURNAL ACCOUNTING
                    |--------------------------------------------------------------------------
                    |
                    | Dr Kas/Bank
                    |    Cr Akun Lawan
                    |
                    */

                    $accountingService
                        ->autoJournalUangMasuk(
                            $uangMasuk
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | 3. STATUS
                    |--------------------------------------------------------------------------
                    */

                    $uangMasuk->update([
                        'status' =>
                            'TERCATAT',
                    ]);
                }
            );

            return redirect()
                ->route(
                    'uang-masuk.show',
                    $uangMasuk
                )
                ->with(
                    'success',
                    'Uang Masuk berhasil dicatat. Saldo Kas/Bank dan jurnal accounting telah diperbarui.'
                );

        } catch (\Throwable $e) {
            return redirect()
                ->route(
                    'uang-masuk.show',
                    $uangMasuk
                )
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    private function validationRules():
        array
    {
        $akunKasBankIds =
            RekeningKasBank::query()
                ->whereNotNull('akun_id')
                ->pluck('akun_id')
                ->all();

        return [
            'tanggal' => [
                'required',
                'date',
            ],

            'rekening_kas_bank_id' => [
                'required',

                Rule::exists(
                    'rekening_kas_banks',
                    'id'
                )->where(
                    fn ($query) =>
                        $query
                            ->where(
                                'is_active',
                                true
                            )
                            ->whereNotNull(
                                'akun_id'
                            )
                ),
            ],

            'akun_lawan_id' => [
                'required',

                Rule::exists(
                    'akuns',
                    'id'
                )->where(
                    function ($query) use (
                        $akunKasBankIds
                    ) {
                        $query->where(
                            'is_active',
                            true
                        );

                        /*
                         * Akun yang sudah dipakai sebagai Kas/Bank
                         * tidak boleh menjadi akun lawan.
                         *
                         * Transfer antar rekening harus menggunakan
                         * menu Transfer Antar Rekening.
                         */
                        if (
                            !empty(
                                $akunKasBankIds
                            )
                        ) {
                            $query->whereNotIn(
                                'id',
                                $akunKasBankIds
                            );
                        }
                    }
                ),
            ],

            'jumlah' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'sumber' => [
                'required',
                'string',
                'max:150',
            ],

            'referensi' => [
                'nullable',
                'string',
                'max:150',
            ],

            'keterangan' => [
                'nullable',
                'string',
            ],
        ];
    }

    private function getRekeningKasBanks()
    {
        return RekeningKasBank::query()
            ->where(
                'is_active',
                true
            )
            ->whereNotNull('akun_id')
            ->with('akun')
            ->orderBy('jenis')
            ->orderBy('nama_rekening')
            ->get();
    }

    private function getAkunLawans(
        ?int $currentAkunId = null
    ) {
        $akunKasBankIds =
            RekeningKasBank::query()
                ->whereNotNull('akun_id')
                ->pluck('akun_id');

        return Akun::query()
            ->where(
                'is_active',
                true
            )
            ->where(
                function ($query) use (
                    $akunKasBankIds,
                    $currentAkunId
                ) {
                    if (
                        $akunKasBankIds
                            ->isNotEmpty()
                    ) {
                        $query->whereNotIn(
                            'id',
                            $akunKasBankIds
                        );
                    }

                    if ($currentAkunId) {
                        $query->orWhere(
                            'id',
                            $currentAkunId
                        );
                    }
                }
            )
            ->orderBy('kode_akun')
            ->get();
    }

    private function generateNomorTransaksi():
        string
    {
        $tahun =
            now()->format('Y');

        $bulan =
            now()->format('m');

        $prefix =
            'UM/'
            . $tahun
            . '/'
            . $bulan
            . '/';

        $terakhir =
            UangMasuk::query()
                ->where(
                    'nomor_transaksi',
                    'like',
                    $prefix . '%'
                )
                ->orderByDesc(
                    'nomor_transaksi'
                )
                ->lockForUpdate()
                ->first();

        $urutan = 1;

        if ($terakhir) {
            $nomorTerakhir =
                (int) substr(
                    $terakhir
                        ->nomor_transaksi,
                    -4
                );

            $urutan =
                $nomorTerakhir + 1;
        }

        return $prefix
            . str_pad(
                $urutan,
                4,
                '0',
                STR_PAD_LEFT
            );
    }

    private function buildKeteranganMutasi(
        UangMasuk $uangMasuk
    ): string {
        $uangMasuk->loadMissing(
            'akunLawan'
        );

        $parts = [
            'Uang Masuk '
            . $uangMasuk
                ->nomor_transaksi,

            'Sumber: '
            . $uangMasuk
                ->sumber,
        ];

        if ($uangMasuk->akunLawan) {
            $parts[] =
                'Akun lawan: '
                . $uangMasuk
                    ->akunLawan
                    ->kode_akun
                . ' '
                . $uangMasuk
                    ->akunLawan
                    ->nama_akun;
        }

        if (
            $uangMasuk
                ->referensi
        ) {
            $parts[] =
                'Referensi: '
                . $uangMasuk
                    ->referensi;
        }

        if (
            $uangMasuk
                ->keterangan
        ) {
            $parts[] =
                $uangMasuk
                    ->keterangan;
        }

        return implode(
            ' | ',
            $parts
        );
    }
}
