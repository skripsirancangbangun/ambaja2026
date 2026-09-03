<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\RekeningKasBank;
use App\Models\UangKeluar;
use App\Services\KasBankService;
use App\Services\SystemAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

class UangKeluarController extends Controller
{
    public function index(Request $request)
    {
        $query = UangKeluar::query()
            ->with([
                'rekeningKasBank',
                'creator',
                'details.akun',
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
                    'referensi',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'keterangan',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhereHas(
                    'details',
                    function ($detailQuery) use ($search) {
                        $detailQuery
                            ->where(
                                'tujuan',
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

        $uangKeluars = $query
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $rekeningKasBanks = $this->getRekeningKasBanks();

        return view(
            'uang-keluar.index',
            compact(
                'uangKeluars',
                'rekeningKasBanks'
            )
        );
    }

    public function create()
    {
        $rekeningKasBanks = $this->getRekeningKasBanks();

        $akunBebans = $this->getAkunBebans();

        return view(
            'uang-keluar.create',
            compact(
                'rekeningKasBanks',
                'akunBebans'
            )
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            $this->validationRules(),
            $this->validationMessages()
        );

        $uangKeluar = DB::transaction(function () use ($validated) {
            $total = collect(
                $validated['details']
            )->sum(
                fn ($detail) => (float) $detail['jumlah']
            );

            $uangKeluar = UangKeluar::create([
                'nomor_transaksi' =>
                    $this->generateNomorTransaksi(),

                'tanggal' =>
                    $validated['tanggal'],

                'rekening_kas_bank_id' =>
                    $validated['rekening_kas_bank_id'],

                'jumlah' =>
                    $total,

                /*
                 * Dipertahankan untuk kompatibilitas
                 * dengan struktur Uang Keluar lama.
                 */
                'tujuan' =>
                    count($validated['details']) === 1
                        ? $validated['details'][0]['tujuan']
                        : 'Beberapa pengeluaran',

                'referensi' =>
                    $validated['referensi'] ?? null,

                'keterangan' =>
                    $validated['keterangan'] ?? null,

                'status' =>
                    'DRAFT',

                'created_by' =>
                    auth()->id(),
            ]);

            foreach (
                $validated['details']
                as $detail
            ) {
                $uangKeluar
                    ->details()
                    ->create([
                        'akun_id' =>
                            $detail['akun_id'],

                        'tujuan' =>
                            $detail['tujuan'],

                        'jumlah' =>
                            $detail['jumlah'],
                    ]);
            }

            return $uangKeluar;
        });

        return redirect()
            ->route(
                'uang-keluar.show',
                $uangKeluar
            )
            ->with(
                'success',
                'Uang Keluar berhasil dibuat sebagai draft.'
            );
    }

    public function show(
        UangKeluar $uangKeluar
    ) {
        $uangKeluar->load([
            'rekeningKasBank.akun',
            'creator',
            'mutasiKasBank',
            'details.akun',
        ]);

        return view(
            'uang-keluar.show',
            compact('uangKeluar')
        );
    }

    public function edit(
        UangKeluar $uangKeluar
    ) {
        if (
            $uangKeluar->status !== 'DRAFT'
        ) {
            return redirect()
                ->route(
                    'uang-keluar.show',
                    $uangKeluar
                )
                ->with(
                    'error',
                    'Transaksi yang sudah tercatat tidak dapat diedit.'
                );
        }

        $uangKeluar->load(
            'details.akun'
        );

        $rekeningKasBanks =
            RekeningKasBank::query()
                ->where(
                    function ($query) use ($uangKeluar) {
                        $query
                            ->where(
                                'is_active',
                                true
                            )
                            ->orWhere(
                                'id',
                                $uangKeluar
                                    ->rekening_kas_bank_id
                            );
                    }
                )
                ->orderBy('jenis')
                ->orderBy('nama_rekening')
                ->get();

        $akunBebans =
            $this->getAkunBebans();

        return view(
            'uang-keluar.edit',
            compact(
                'uangKeluar',
                'rekeningKasBanks',
                'akunBebans'
            )
        );
    }

    public function update(
        Request $request,
        UangKeluar $uangKeluar
    ) {
        if (
            $uangKeluar->status !== 'DRAFT'
        ) {
            return redirect()
                ->route(
                    'uang-keluar.show',
                    $uangKeluar
                )
                ->with(
                    'error',
                    'Transaksi yang sudah tercatat tidak dapat diubah.'
                );
        }

        $validated = $request->validate(
            $this->validationRules(),
            $this->validationMessages()
        );

        DB::transaction(
            function () use (
                $validated,
                $uangKeluar
            ) {
                $total = collect(
                    $validated['details']
                )->sum(
                    fn ($detail) =>
                        (float) $detail['jumlah']
                );

                $uangKeluar->update([
                    'tanggal' =>
                        $validated['tanggal'],

                    'rekening_kas_bank_id' =>
                        $validated[
                            'rekening_kas_bank_id'
                        ],

                    'jumlah' =>
                        $total,

                    'tujuan' =>
                        count(
                            $validated['details']
                        ) === 1
                            ? $validated[
                                'details'
                            ][0]['tujuan']
                            : 'Beberapa pengeluaran',

                    'referensi' =>
                        $validated[
                            'referensi'
                        ] ?? null,

                    'keterangan' =>
                        $validated[
                            'keterangan'
                        ] ?? null,
                ]);

                $uangKeluar
                    ->details()
                    ->delete();

                foreach (
                    $validated['details']
                    as $detail
                ) {
                    $uangKeluar
                        ->details()
                        ->create([
                            'akun_id' =>
                                $detail['akun_id'],

                            'tujuan' =>
                                $detail['tujuan'],

                            'jumlah' =>
                                $detail['jumlah'],
                        ]);
                }
            }
        );

        return redirect()
            ->route(
                'uang-keluar.show',
                $uangKeluar
            )
            ->with(
                'success',
                'Draft Uang Keluar berhasil diperbarui.'
            );
    }

    public function destroy(
        UangKeluar $uangKeluar
    ) {
        if (
            $uangKeluar->status !== 'DRAFT'
        ) {
            return redirect()
                ->route(
                    'uang-keluar.show',
                    $uangKeluar
                )
                ->with(
                    'error',
                    'Transaksi yang sudah tercatat tidak dapat dihapus.'
                );
        }

        $uangKeluar->delete();

        return redirect()
            ->route(
                'uang-keluar.index'
            )
            ->with(
                'success',
                'Draft Uang Keluar berhasil dihapus.'
            );
    }

    public function catat(
        UangKeluar $uangKeluar,
        KasBankService $kasBankService,
        SystemAccountingService $accountingService
    ) {
        try {

            /*
             * Semua proses dilakukan dalam satu DB transaction.
             *
             * Kalau jurnal gagal:
             * - Mutasi Kas/Bank ikut rollback
             * - Status tidak menjadi TERCATAT
             *
             * Jadi transaksi tetap konsisten.
             */
            DB::transaction(
                function () use (
                    $uangKeluar,
                    $kasBankService,
                    $accountingService
                ) {
                    $uangKeluar =
                        UangKeluar::query()
                            ->with([
                                'details.akun',
                                'rekeningKasBank.akun',
                            ])
                            ->lockForUpdate()
                            ->findOrFail(
                                $uangKeluar->id
                            );

                    if (
                        $uangKeluar->status
                        !== 'DRAFT'
                    ) {
                        throw new RuntimeException(
                            'Transaksi Uang Keluar ini sudah tercatat.'
                        );
                    }

                    if (
                        $uangKeluar
                            ->details
                            ->isEmpty()
                    ) {
                        throw new RuntimeException(
                            'Transaksi belum memiliki rincian pengeluaran.'
                        );
                    }

                    if (
                        !$uangKeluar
                            ->rekeningKasBank
                    ) {
                        throw new RuntimeException(
                            'Kas/Rekening Bank transaksi tidak ditemukan.'
                        );
                    }

                    /*
                     * Rekening harus dipetakan
                     * ke akun COA.
                     */
                    if (
                        !$uangKeluar
                            ->rekeningKasBank
                            ->akun_id
                    ) {
                        throw new RuntimeException(
                            'Kas/Rekening Bank belum terhubung dengan akun COA.'
                        );
                    }

                    /*
                     * Pastikan semua detail memiliki
                     * akun biaya.
                     */
                    if (
                        $uangKeluar
                            ->details
                            ->contains(
                                fn ($detail) =>
                                    !$detail->akun_id
                            )
                    ) {
                        throw new RuntimeException(
                            'Semua rincian pengeluaran harus memiliki akun biaya.'
                        );
                    }

                    foreach (
                        $uangKeluar->details
                        as $detail
                    ) {
                        if (
                            (float) $detail->jumlah
                            <= 0
                        ) {
                            throw new RuntimeException(
                                'Nominal rincian pengeluaran harus lebih besar dari 0.'
                            );
                        }
                    }

                    /*
                     * Hitung ulang total berdasarkan
                     * detail transaksi.
                     */
                    $totalDetail =
                        (float) $uangKeluar
                            ->details
                            ->sum('jumlah');

                    if ($totalDetail <= 0) {
                        throw new RuntimeException(
                            'Total Uang Keluar harus lebih besar dari 0.'
                        );
                    }

                    /*
                     * Jika total header berbeda,
                     * sinkronkan dengan total detail.
                     */
                    if (
                        abs(
                            $totalDetail
                            -
                            (float)
                            $uangKeluar->jumlah
                        ) > 0.01
                    ) {
                        $uangKeluar->update([
                            'jumlah' =>
                                $totalDetail,
                        ]);
                    }

                    /*
                     * ==================================================
                     * 1. MUTASI KAS/BANK
                     * ==================================================
                     *
                     * Dr = 0
                     * Cr = Total Uang Keluar
                     *
                     * Saldo rekening otomatis berkurang.
                     */
                    $kasBankService->uangKeluar(
                        rekeningKasBankId:
                            (int)
                            $uangKeluar
                                ->rekening_kas_bank_id,

                        jumlah:
                            $totalDetail,

                        referenceType:
                            'UANG_KELUAR',

                        referenceId:
                            (int)
                            $uangKeluar->id,

                        nomorReferensi:
                            $uangKeluar
                                ->nomor_transaksi,

                        keterangan:
                            $this
                                ->buildKeteranganMutasi(
                                    $uangKeluar
                                ),

                        tanggalTransaksi:
                            $uangKeluar
                                ->tanggal
                                ->format('Y-m-d'),

                        tipe:
                            'UANG_KELUAR'
                    );

                    /*
                     * ==================================================
                     * 2. JURNAL OTOMATIS
                     * ==================================================
                     *
                     * Contoh:
                     *
                     * Dr Beban Gaji       1.000.000
                     * Dr Beban Sewa      30.000.000
                     *    Cr Bank BCA     31.000.000
                     */
                    $accountingService
                        ->autoJournalUangKeluar(
                            $uangKeluar
                        );

                    /*
                     * ==================================================
                     * 3. STATUS
                     * ==================================================
                     *
                     * Baru diubah setelah mutasi
                     * dan jurnal berhasil.
                     */
                    $uangKeluar->update([
                        'status' =>
                            'TERCATAT',
                    ]);
                }
            );

            return redirect()
                ->route(
                    'uang-keluar.show',
                    $uangKeluar
                )
                ->with(
                    'success',
                    'Uang Keluar berhasil dicatat. Saldo Kas/Bank dan jurnal accounting telah diperbarui.'
                );

        } catch (\Throwable $e) {

            return redirect()
                ->route(
                    'uang-keluar.show',
                    $uangKeluar
                )
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    private function getRekeningKasBanks()
    {
        return RekeningKasBank::query()
            ->where(
                'is_active',
                true
            )
            ->orderBy('jenis')
            ->orderBy(
                'nama_rekening'
            )
            ->get();
    }

    private function getAkunBebans()
    {
        return Akun::query()
            ->where(
                'kategori_akun',
                'Beban'
            )
            ->where(
                'is_active',
                true
            )

            /*
             * Tidak dimasukkan ke Uang Keluar biasa:
             *
             * 5101 = HPP
             * 6106 = Beban Penyusutan
             */
            ->whereNotIn(
                'kode_akun',
                [
                    '5101',
                    '6106',
                ]
            )
            ->orderBy(
                'kode_akun'
            )
            ->get();
    }

    private function validationRules():
        array
    {
        return [
            'tanggal' => [
                'required',
                'date',
            ],

            'rekening_kas_bank_id' => [
                'required',
                'exists:rekening_kas_banks,id',
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

            'details' => [
                'required',
                'array',
                'min:1',
            ],

            'details.*.akun_id' => [
                'required',

                Rule::exists(
                    'akuns',
                    'id'
                )->where(
                    function ($query) {
                        $query
                            ->where(
                                'kategori_akun',
                                'Beban'
                            )
                            ->where(
                                'is_active',
                                true
                            )
                            ->whereNotIn(
                                'kode_akun',
                                [
                                    '5101',
                                    '6106',
                                ]
                            );
                    }
                ),
            ],

            'details.*.tujuan' => [
                'required',
                'string',
                'max:150',
            ],

            'details.*.jumlah' => [
                'required',
                'numeric',
                'gt:0',
            ],
        ];
    }

    private function validationMessages():
        array
    {
        return [
            'details.required' =>
                'Minimal harus ada satu rincian pengeluaran.',

            'details.array' =>
                'Format rincian pengeluaran tidak valid.',

            'details.min' =>
                'Minimal harus ada satu rincian pengeluaran.',

            'details.*.akun_id.required' =>
                'Akun biaya wajib dipilih.',

            'details.*.akun_id.exists' =>
                'Akun biaya yang dipilih tidak valid.',

            'details.*.tujuan.required' =>
                'Keterangan setiap pengeluaran wajib diisi.',

            'details.*.tujuan.max' =>
                'Keterangan pengeluaran maksimal 150 karakter.',

            'details.*.jumlah.required' =>
                'Jumlah setiap pengeluaran wajib diisi.',

            'details.*.jumlah.numeric' =>
                'Jumlah pengeluaran harus berupa angka.',

            'details.*.jumlah.gt' =>
                'Jumlah pengeluaran harus lebih besar dari 0.',
        ];
    }

    private function generateNomorTransaksi():
        string
    {
        $tahun =
            now()->format('Y');

        $bulan =
            now()->format('m');

        $prefix =
            'UK/'
            . $tahun
            . '/'
            . $bulan
            . '/';

        $terakhir =
            UangKeluar::query()
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
        UangKeluar $uangKeluar
    ): string {

        $parts = [
            'Uang Keluar '
            . $uangKeluar
                ->nomor_transaksi,
        ];

        if (
            $uangKeluar
                ->details
                ->isNotEmpty()
        ) {

            $ringkasan =
                $uangKeluar
                    ->details
                    ->map(
                        function ($detail) {

                            $akun =
                                $detail->akun
                                    ? $detail
                                            ->akun
                                            ->kode_akun
                                        . ' '
                                        . $detail
                                            ->akun
                                            ->nama_akun
                                    : 'Tanpa akun';

                            return
                                $akun
                                . ' - '
                                . $detail
                                    ->tujuan
                                . ' Rp '
                                . number_format(
                                    (float)
                                    $detail
                                        ->jumlah,
                                    0,
                                    ',',
                                    '.'
                                );
                        }
                    )
                    ->implode(', ');

            $parts[] =
                'Rincian: '
                . $ringkasan;

        } elseif (
            $uangKeluar->tujuan
        ) {

            /*
             * Kompatibilitas dengan
             * transaksi Uang Keluar lama.
             */
            $parts[] =
                'Tujuan: '
                . $uangKeluar
                    ->tujuan;
        }

        if (
            $uangKeluar->referensi
        ) {
            $parts[] =
                'Referensi: '
                . $uangKeluar
                    ->referensi;
        }

        if (
            $uangKeluar->keterangan
        ) {
            $parts[] =
                $uangKeluar
                    ->keterangan;
        }

        return implode(
            ' | ',
            $parts
        );
    }
}