<?php

namespace App\Services;

use App\Models\MutasiKasBank;
use App\Models\RekeningKasBank;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class KasBankService
{
    /**
     * ===============================================================
     * UANG MASUK
     * ===============================================================
     */
    public function uangMasuk(
        int $rekeningKasBankId,
        float $jumlah,
        string $referenceType,
        int $referenceId,
        ?string $nomorReferensi = null,
        ?string $keterangan = null,
        ?string $tanggalTransaksi = null,
        string $tipe = 'UANG_MASUK'
    ): MutasiKasBank {

        $jumlah =
            round(
                $jumlah,
                2
            );

        if ($jumlah <= 0) {
            throw new RuntimeException(
                'Jumlah uang masuk harus lebih besar dari 0.'
            );
        }

        return DB::transaction(
            function () use (
                $rekeningKasBankId,
                $jumlah,
                $referenceType,
                $referenceId,
                $nomorReferensi,
                $keterangan,
                $tanggalTransaksi,
                $tipe
            ) {
                /*
                |--------------------------------------------------------------------------
                | LOCK REKENING
                |--------------------------------------------------------------------------
                */

                $rekening =
                    RekeningKasBank::query()
                        ->with('akun')
                        ->lockForUpdate()
                        ->findOrFail(
                            $rekeningKasBankId
                        );

                if (!$rekening->is_active) {
                    throw new RuntimeException(
                        'Kas/Rekening Bank yang dipilih sudah tidak aktif.'
                    );
                }

                if (!$rekening->akun_id) {
                    throw new RuntimeException(
                        'Kas/Rekening Bank belum terhubung dengan akun COA.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | TANGGAL
                |--------------------------------------------------------------------------
                */

                $tanggal =
                    $this->resolveTanggal(
                        $tanggalTransaksi
                    );

                $this->validateUrutanTanggal(
                    $rekening->id,
                    $tanggal
                );

                /*
                |--------------------------------------------------------------------------
                | CEK DUPLIKAT
                |--------------------------------------------------------------------------
                */

                $this->validateDuplikat(
                    $referenceType,
                    $referenceId,
                    $tipe
                );

                /*
                |--------------------------------------------------------------------------
                | SALDO
                |--------------------------------------------------------------------------
                */

                $saldoSebelumnya =
                    $this->getSaldoTerakhir(
                        $rekening
                    );

                $saldoBaru =
                    round(
                        $saldoSebelumnya
                        + $jumlah,
                        2
                    );

                /*
                |--------------------------------------------------------------------------
                | MUTASI
                |--------------------------------------------------------------------------
                */

                return MutasiKasBank::create([
                    'tanggal' =>
                        $tanggal,

                    'rekening_kas_bank_id' =>
                        $rekening->id,

                    'tipe' =>
                        $tipe,

                    'debit' =>
                        $jumlah,

                    'kredit' =>
                        0,

                    'saldo' =>
                        $saldoBaru,

                    'reference_type' =>
                        $referenceType,

                    'reference_id' =>
                        $referenceId,

                    'nomor_referensi' =>
                        $nomorReferensi,

                    'keterangan' =>
                        $keterangan,
                ]);
            },
            3
        );
    }

    /**
     * ===============================================================
     * UANG KELUAR
     * ===============================================================
     */
    public function uangKeluar(
        int $rekeningKasBankId,
        float $jumlah,
        string $referenceType,
        int $referenceId,
        ?string $nomorReferensi = null,
        ?string $keterangan = null,
        ?string $tanggalTransaksi = null,
        string $tipe = 'UANG_KELUAR'
    ): MutasiKasBank {

        $jumlah =
            round(
                $jumlah,
                2
            );

        if ($jumlah <= 0) {
            throw new RuntimeException(
                'Jumlah uang keluar harus lebih besar dari 0.'
            );
        }

        return DB::transaction(
            function () use (
                $rekeningKasBankId,
                $jumlah,
                $referenceType,
                $referenceId,
                $nomorReferensi,
                $keterangan,
                $tanggalTransaksi,
                $tipe
            ) {
                /*
                |--------------------------------------------------------------------------
                | LOCK REKENING
                |--------------------------------------------------------------------------
                */

                $rekening =
                    RekeningKasBank::query()
                        ->with('akun')
                        ->lockForUpdate()
                        ->findOrFail(
                            $rekeningKasBankId
                        );

                if (!$rekening->is_active) {
                    throw new RuntimeException(
                        'Kas/Rekening Bank yang dipilih sudah tidak aktif.'
                    );
                }

                if (!$rekening->akun_id) {
                    throw new RuntimeException(
                        'Kas/Rekening Bank belum terhubung dengan akun COA.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | TANGGAL
                |--------------------------------------------------------------------------
                */

                $tanggal =
                    $this->resolveTanggal(
                        $tanggalTransaksi
                    );

                $this->validateUrutanTanggal(
                    $rekening->id,
                    $tanggal
                );

                /*
                |--------------------------------------------------------------------------
                | CEK DUPLIKAT
                |--------------------------------------------------------------------------
                */

                $this->validateDuplikat(
                    $referenceType,
                    $referenceId,
                    $tipe
                );

                /*
                |--------------------------------------------------------------------------
                | SALDO
                |--------------------------------------------------------------------------
                */

                $saldoSebelumnya =
                    $this->getSaldoTerakhir(
                        $rekening
                    );

                if (
                    $jumlah
                    >
                    $saldoSebelumnya
                ) {
                    throw new RuntimeException(
                        'Saldo Kas/Rekening Bank tidak mencukupi. '
                        . 'Saldo tersedia: Rp '
                        . number_format(
                            $saldoSebelumnya,
                            0,
                            ',',
                            '.'
                        )
                        . '.'
                    );
                }

                $saldoBaru =
                    round(
                        $saldoSebelumnya
                        - $jumlah,
                        2
                    );

                /*
                |--------------------------------------------------------------------------
                | MUTASI
                |--------------------------------------------------------------------------
                */

                return MutasiKasBank::create([
                    'tanggal' =>
                        $tanggal,

                    'rekening_kas_bank_id' =>
                        $rekening->id,

                    'tipe' =>
                        $tipe,

                    'debit' =>
                        0,

                    'kredit' =>
                        $jumlah,

                    'saldo' =>
                        $saldoBaru,

                    'reference_type' =>
                        $referenceType,

                    'reference_id' =>
                        $referenceId,

                    'nomor_referensi' =>
                        $nomorReferensi,

                    'keterangan' =>
                        $keterangan,
                ]);
            },
            3
        );
    }

    /**
     * ===============================================================
     * TRANSFER ANTAR REKENING
     * ===============================================================
     */
    public function transfer(
        int $rekeningAsalId,
        int $rekeningTujuanId,
        float $jumlah,
        string $referenceType,
        int $referenceId,
        ?string $nomorReferensi = null,
        ?string $keterangan = null,
        ?string $tanggalTransaksi = null
    ): array {

        $jumlah =
            round(
                $jumlah,
                2
            );

        if (
            $rekeningAsalId
            ===
            $rekeningTujuanId
        ) {
            throw new RuntimeException(
                'Rekening asal dan rekening tujuan tidak boleh sama.'
            );
        }

        if ($jumlah <= 0) {
            throw new RuntimeException(
                'Jumlah transfer harus lebih besar dari 0.'
            );
        }

        return DB::transaction(
            function () use (
                $rekeningAsalId,
                $rekeningTujuanId,
                $jumlah,
                $referenceType,
                $referenceId,
                $nomorReferensi,
                $keterangan,
                $tanggalTransaksi
            ) {
                /*
                |--------------------------------------------------------------------------
                | LOCK KEDUA REKENING
                |--------------------------------------------------------------------------
                |
                | Lock selalu berdasarkan urutan ID supaya risiko
                | deadlock lebih kecil.
                |
                */

                $ids = [
                    $rekeningAsalId,
                    $rekeningTujuanId,
                ];

                sort(
                    $ids
                );

                $rekeningLocked =
                    RekeningKasBank::query()
                        ->with('akun')
                        ->whereIn(
                            'id',
                            $ids
                        )
                        ->orderBy(
                            'id'
                        )
                        ->lockForUpdate()
                        ->get()
                        ->keyBy(
                            'id'
                        );

                $rekeningAsal =
                    $rekeningLocked->get(
                        $rekeningAsalId
                    );

                $rekeningTujuan =
                    $rekeningLocked->get(
                        $rekeningTujuanId
                    );

                if (
                    !$rekeningAsal
                    ||
                    !$rekeningTujuan
                ) {
                    throw new RuntimeException(
                        'Kas/Rekening Bank tidak ditemukan.'
                    );
                }

                if (
                    !$rekeningAsal->is_active
                    ||
                    !$rekeningTujuan->is_active
                ) {
                    throw new RuntimeException(
                        'Rekening asal dan rekening tujuan harus aktif.'
                    );
                }

                if (
                    !$rekeningAsal->akun_id
                    ||
                    !$rekeningTujuan->akun_id
                ) {
                    throw new RuntimeException(
                        'Rekening asal dan tujuan harus sudah terhubung dengan akun COA.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | TANGGAL
                |--------------------------------------------------------------------------
                */

                $tanggal =
                    $this->resolveTanggal(
                        $tanggalTransaksi
                    );

                $this->validateUrutanTanggal(
                    $rekeningAsal->id,
                    $tanggal
                );

                $this->validateUrutanTanggal(
                    $rekeningTujuan->id,
                    $tanggal
                );

                /*
                |--------------------------------------------------------------------------
                | CEK DUPLIKAT
                |--------------------------------------------------------------------------
                */

                $this->validateDuplikat(
                    $referenceType,
                    $referenceId,
                    'TRANSFER_KELUAR'
                );

                $this->validateDuplikat(
                    $referenceType,
                    $referenceId,
                    'TRANSFER_MASUK'
                );

                /*
                |--------------------------------------------------------------------------
                | SALDO ASAL
                |--------------------------------------------------------------------------
                */

                $saldoAsal =
                    $this->getSaldoTerakhir(
                        $rekeningAsal
                    );

                if (
                    $jumlah
                    >
                    $saldoAsal
                ) {
                    throw new RuntimeException(
                        'Saldo rekening asal tidak mencukupi untuk transfer. '
                        . 'Saldo tersedia: Rp '
                        . number_format(
                            $saldoAsal,
                            0,
                            ',',
                            '.'
                        )
                        . '.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | SALDO TUJUAN
                |--------------------------------------------------------------------------
                */

                $saldoTujuan =
                    $this->getSaldoTerakhir(
                        $rekeningTujuan
                    );

                /*
                |--------------------------------------------------------------------------
                | SALDO BARU
                |--------------------------------------------------------------------------
                */

                $saldoAsalBaru =
                    round(
                        $saldoAsal
                        - $jumlah,
                        2
                    );

                $saldoTujuanBaru =
                    round(
                        $saldoTujuan
                        + $jumlah,
                        2
                    );

                /*
                |--------------------------------------------------------------------------
                | MUTASI KELUAR
                |--------------------------------------------------------------------------
                */

                $mutasiKeluar =
                    MutasiKasBank::create([
                        'tanggal' =>
                            $tanggal,

                        'rekening_kas_bank_id' =>
                            $rekeningAsal->id,

                        'tipe' =>
                            'TRANSFER_KELUAR',

                        'debit' =>
                            0,

                        'kredit' =>
                            $jumlah,

                        'saldo' =>
                            $saldoAsalBaru,

                        'reference_type' =>
                            $referenceType,

                        'reference_id' =>
                            $referenceId,

                        'nomor_referensi' =>
                            $nomorReferensi,

                        'keterangan' =>
                            $keterangan,
                    ]);

                /*
                |--------------------------------------------------------------------------
                | MUTASI MASUK
                |--------------------------------------------------------------------------
                */

                $mutasiMasuk =
                    MutasiKasBank::create([
                        'tanggal' =>
                            $tanggal,

                        'rekening_kas_bank_id' =>
                            $rekeningTujuan->id,

                        'tipe' =>
                            'TRANSFER_MASUK',

                        'debit' =>
                            $jumlah,

                        'kredit' =>
                            0,

                        'saldo' =>
                            $saldoTujuanBaru,

                        'reference_type' =>
                            $referenceType,

                        'reference_id' =>
                            $referenceId,

                        'nomor_referensi' =>
                            $nomorReferensi,

                        'keterangan' =>
                            $keterangan,
                    ]);

                return [
                    'keluar' =>
                        $mutasiKeluar,

                    'masuk' =>
                        $mutasiMasuk,
                ];
            },
            3
        );
    }

    /**
     * ===============================================================
     * GET SALDO
     * ===============================================================
     */
    public function getSaldo(
        int $rekeningKasBankId
    ): float {

        $rekening =
            RekeningKasBank::findOrFail(
                $rekeningKasBankId
            );

        return $this->getSaldoTerakhir(
            $rekening
        );
    }

    /**
     * ===============================================================
     * VALIDASI DUPLIKAT
     * ===============================================================
     */
    private function validateDuplikat(
        string $referenceType,
        int $referenceId,
        string $tipe
    ): void {

        $exists =
            MutasiKasBank::query()
                ->where(
                    'reference_type',
                    $referenceType
                )
                ->where(
                    'reference_id',
                    $referenceId
                )
                ->where(
                    'tipe',
                    $tipe
                )
                ->lockForUpdate()
                ->exists();

        if ($exists) {
            throw new RuntimeException(
                'Transaksi ini sudah tercatat pada Mutasi Kas/Bank.'
            );
        }
    }

    /**
     * ===============================================================
     * SALDO SAAT INI
     * ===============================================================
     *
     * Sumber kebenaran:
     *
     * Saldo Awal
     * + Total Debit
     * - Total Kredit
     *
     * Tidak menggunakan kolom saldo dari mutasi terakhir sebagai
     * sumber utama karena saldo awal rekening dapat pernah dikoreksi.
     */
    private function getSaldoTerakhir(
        RekeningKasBank $rekening
    ): float {

        $ringkasan =
            MutasiKasBank::query()
                ->where(
                    'rekening_kas_bank_id',
                    $rekening->id
                )
                ->selectRaw('
                    COALESCE(SUM(debit), 0) AS total_debit,
                    COALESCE(SUM(kredit), 0) AS total_kredit
                ')
                ->first();

        $saldoAwal =
            (float)
            $rekening->saldo_awal;

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

        return round(
            $saldoAwal
            + $totalDebit
            - $totalKredit,
            2
        );
    }

    /**
     * ===============================================================
     * NORMALISASI TANGGAL
     * ===============================================================
     */
    private function resolveTanggal(
        ?string $tanggalTransaksi
    ): Carbon {

        if (!$tanggalTransaksi) {
            return now();
        }

        try {
            $tanggal =
                Carbon::parse(
                    $tanggalTransaksi
                );

            /*
             * Jika hanya YYYY-MM-DD,
             * gunakan jam saat ini.
             */
            if (
                preg_match(
                    '/^\d{4}-\d{2}-\d{2}$/',
                    $tanggalTransaksi
                )
            ) {
                $sekarang =
                    now();

                $tanggal->setTime(
                    $sekarang->hour,
                    $sekarang->minute,
                    $sekarang->second
                );
            }

            return $tanggal;

        } catch (\Throwable $e) {

            throw new RuntimeException(
                'Tanggal transaksi Kas/Bank tidak valid.'
            );
        }
    }

    /**
     * ===============================================================
     * VALIDASI URUTAN TANGGAL
     * ===============================================================
     */
    private function validateUrutanTanggal(
        int $rekeningKasBankId,
        Carbon $tanggal
    ): void {

        $mutasiTerakhir =
            MutasiKasBank::query()
                ->where(
                    'rekening_kas_bank_id',
                    $rekeningKasBankId
                )
                ->orderByDesc(
                    'tanggal'
                )
                ->orderByDesc(
                    'id'
                )
                ->lockForUpdate()
                ->first();

        if (!$mutasiTerakhir) {
            return;
        }

        $tanggalTerakhir =
            Carbon::parse(
                $mutasiTerakhir->tanggal
            );

        if (
            $tanggal
                ->copy()
                ->startOfDay()
                ->lt(
                    $tanggalTerakhir
                        ->copy()
                        ->startOfDay()
                )
        ) {
            throw new RuntimeException(
                'Tanggal transaksi tidak boleh sebelum tanggal mutasi Kas/Bank terakhir. '
                . 'Tanggal mutasi terakhir: '
                . $tanggalTerakhir
                    ->format('d/m/Y')
                . '.'
            );
        }
    }
}