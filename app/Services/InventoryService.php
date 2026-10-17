<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\MutasiStok;
use App\Models\StokGudang;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    /**
     * ===============================================================
     * SISTEM PERSEDIAAN AMBAJA
     * ===============================================================
     *
     * Metode pencatatan:
     * PERPETUAL
     *
     * Metode penilaian:
     * MOVING AVERAGE / RATA-RATA BERGERAK
     *
     * Service ini TIDAK BOLEH mengubah:
     *
     * - harga_beli_default
     * - margin_persen
     * - harga_jual
     *
     * Dengan begitu pembelian/penerimaan barang tidak akan
     * mengubah setting markup/laba barang.
     * ===============================================================
     */

    public function stokMasuk(
        int $barangId,
        int $gudangId,
        float $qty,
        float $hargaSatuan,
        string $referenceType,
        int $referenceId,
        ?string $keterangan = null,
        string $tipeMutasi = MutasiStok::TIPE_MASUK,
        $tanggalTransaksi = null,
        ?float $nilaiMasukOverride = null
    ): StokGudang {

        $qty =
            $this->normalisasiQty($qty);

        $hargaSatuan =
            $this->normalisasiNilai(
                $hargaSatuan
            );

        /*
        |--------------------------------------------------------------------------
        | VALIDASI
        |--------------------------------------------------------------------------
        */

        if ($qty <= 0) {
            throw new RuntimeException(
                'Qty stok masuk harus lebih dari 0.'
            );
        }

        if ($hargaSatuan < 0) {
            throw new RuntimeException(
                'Harga satuan tidak boleh negatif.'
            );
        }

        if (
            $nilaiMasukOverride !== null
            && (
                !is_finite($nilaiMasukOverride)
                || $nilaiMasukOverride < 0
            )
        ) {
            throw new RuntimeException(
                'Nilai total stok masuk tidak valid.'
            );
        }

        if ($nilaiMasukOverride !== null) {
            $nilaiMasukOverride = $this->normalisasiNilai(
                $nilaiMasukOverride
            );
        }

        /*
        |--------------------------------------------------------------------------
        | NORMALISASI TIPE MUTASI
        |--------------------------------------------------------------------------
        */

        $tipeMutasi =
            $this->resolveTipeMasuk(
                $tipeMutasi,
                $referenceType
            );

        if (
            !in_array(
                $tipeMutasi,
                MutasiStok::tipeMasuk(),
                true
            )
        ) {
            throw new RuntimeException(
                "Tipe mutasi stok masuk '{$tipeMutasi}' tidak valid."
            );
        }

        $this->validateReference(
            $referenceType,
            $referenceId
        );

        $tanggalMutasi =
            $this->resolveTanggalTransaksi(
                $tanggalTransaksi
            );

        /*
        |--------------------------------------------------------------------------
        | TRANSACTION
        |--------------------------------------------------------------------------
        */

        return DB::transaction(
            function () use (
                $barangId,
                $gudangId,
                $qty,
                $hargaSatuan,
                $referenceType,
                $referenceId,
                $keterangan,
                $tipeMutasi,
                $tanggalMutasi,
                $nilaiMasukOverride
            ) {

                /*
                |--------------------------------------------------------------------------
                | AMBIL / BUAT STOK
                |--------------------------------------------------------------------------
                */

                $stok =
                    $this->ambilAtauBuatStok(
                        barangId:
                            $barangId,

                        gudangId:
                            $gudangId
                    );

                /*
                |--------------------------------------------------------------------------
                | CEK URUTAN TANGGAL
                |--------------------------------------------------------------------------
                */

                $this->validateUrutanTanggal(
                    barangId:
                        $barangId,

                    gudangId:
                        $gudangId,

                    tanggalMutasi:
                        $tanggalMutasi
                );

                /*
                |--------------------------------------------------------------------------
                | SALDO SEBELUM
                |--------------------------------------------------------------------------
                */

                $qtyLama =
                    (float) $stok->qty;

                $nilaiLama =
                    (float)
                    $stok->nilai_persediaan;

                /*
                |--------------------------------------------------------------------------
                | NILAI BARANG MASUK
                |--------------------------------------------------------------------------
                */

                /*
                |--------------------------------------------------------------
                | NILAI BARANG MASUK
                |--------------------------------------------------------------
                |
                | Untuk transfer gudang, nilai total dapat diberikan eksplisit
                | agar nilai masuk sama persis dengan nilai yang keluar dari
                | gudang asal, termasuk koreksi residu pembulatan saat seluruh
                | saldo dipindahkan. Transaksi masuk biasa tetap menggunakan
                | qty * harga satuan.
                */
                $nilaiMasuk = $nilaiMasukOverride !== null
                    ? $nilaiMasukOverride
                    : $this->normalisasiNilai(
                        $qty * $hargaSatuan
                    );

                /*
                |--------------------------------------------------------------------------
                | QTY BARU
                |--------------------------------------------------------------------------
                */

                $qtyBaru =
                    $this->normalisasiQty(
                        $qtyLama
                        + $qty
                    );

                /*
                |--------------------------------------------------------------------------
                | NILAI PERSEDIAAN BARU
                |--------------------------------------------------------------------------
                */

                $nilaiBaru =
                    $this->normalisasiNilai(
                        $nilaiLama
                        + $nilaiMasuk
                    );

                /*
                |--------------------------------------------------------------------------
                | MOVING AVERAGE
                |--------------------------------------------------------------------------
                |
                | Rumus:
                |
                | Nilai Persediaan Lama
                | +
                | Nilai Barang Masuk
                |
                | dibagi
                |
                | Qty Baru
                |
                */

                $hargaRataRataBaru =
                    $qtyBaru > 0
                        ? $this
                            ->normalisasiNilai(
                                $nilaiBaru
                                / $qtyBaru
                            )
                        : 0;

                /*
                |--------------------------------------------------------------------------
                | UPDATE STOK
                |--------------------------------------------------------------------------
                */

                $stok->update([
                    'qty' =>
                        $qtyBaru,

                    'harga_rata_rata' =>
                        $hargaRataRataBaru,

                    'nilai_persediaan' =>
                        $nilaiBaru,
                ]);

                /*
                |--------------------------------------------------------------------------
                | MUTASI / KARTU STOK
                |--------------------------------------------------------------------------
                */

                MutasiStok::create([
                    'tanggal' =>
                        $tanggalMutasi,

                    'barang_id' =>
                        $barangId,

                    'gudang_id' =>
                        $gudangId,

                    'tipe' =>
                        $tipeMutasi,

                    'qty_masuk' =>
                        $qty,

                    'qty_keluar' =>
                        0,

                    'harga_satuan' =>
                        $hargaSatuan,

                    'nilai' =>
                        $nilaiMasuk,

                    'saldo_qty' =>
                        $qtyBaru,

                    'saldo_nilai' =>
                        $nilaiBaru,

                    'reference_type' =>
                        $referenceType,

                    'reference_id' =>
                        $referenceId,

                    'keterangan' =>
                        $keterangan,
                ]);

                return $stok->fresh();
            },
            3
        );
    }

    /**
     * ===============================================================
     * STOK KELUAR
     * ===============================================================
     *
     * HPP menggunakan harga rata-rata yang berlaku
     * tepat sebelum transaksi keluar.
     */
    public function stokKeluar(
        int $barangId,
        int $gudangId,
        float $qty,
        string $referenceType,
        int $referenceId,
        ?string $keterangan = null,
        string $tipeMutasi = MutasiStok::TIPE_KELUAR,
        $tanggalTransaksi = null
    ): StokGudang {

        $qty =
            $this->normalisasiQty($qty);

        /*
        |--------------------------------------------------------------------------
        | VALIDASI
        |--------------------------------------------------------------------------
        */

        if ($qty <= 0) {
            throw new RuntimeException(
                'Qty stok keluar harus lebih dari 0.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | NORMALISASI TIPE
        |--------------------------------------------------------------------------
        */

        $tipeMutasi =
            $this->resolveTipeKeluar(
                $tipeMutasi,
                $referenceType
            );

        if (
            !in_array(
                $tipeMutasi,
                MutasiStok::tipeKeluar(),
                true
            )
        ) {
            throw new RuntimeException(
                "Tipe mutasi stok keluar '{$tipeMutasi}' tidak valid."
            );
        }

        $this->validateReference(
            $referenceType,
            $referenceId
        );

        $tanggalMutasi =
            $this->resolveTanggalTransaksi(
                $tanggalTransaksi
            );

        /*
        |--------------------------------------------------------------------------
        | DATABASE TRANSACTION
        |--------------------------------------------------------------------------
        */

        return DB::transaction(
            function () use (
                $barangId,
                $gudangId,
                $qty,
                $referenceType,
                $referenceId,
                $keterangan,
                $tipeMutasi,
                $tanggalMutasi
            ) {

                /*
                |--------------------------------------------------------------------------
                | AMBIL STOK
                |--------------------------------------------------------------------------
                */

                $stok =
                    StokGudang::where(
                        'barang_id',
                        $barangId
                    )
                        ->where(
                            'gudang_id',
                            $gudangId
                        )
                        ->lockForUpdate()
                        ->first();

                /*
                |--------------------------------------------------------------------------
                | STOK BELUM ADA
                |--------------------------------------------------------------------------
                */

                if (!$stok) {

                    $barang =
                        Barang::find(
                            $barangId
                        );

                    $namaBarang =
                        $barang
                            ? "{$barang->kode_barang} - {$barang->nama_barang}"
                            : "ID {$barangId}";

                    throw new RuntimeException(
                        "Stok barang '{$namaBarang}' "
                        . 'di gudang yang dipilih belum tersedia.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | CEK URUTAN TANGGAL
                |--------------------------------------------------------------------------
                */

                $this->validateUrutanTanggal(
                    barangId:
                        $barangId,

                    gudangId:
                        $gudangId,

                    tanggalMutasi:
                        $tanggalMutasi
                );

                /*
                |--------------------------------------------------------------------------
                | QTY SEBELUM
                |--------------------------------------------------------------------------
                */

                $qtyLama =
                    (float) $stok->qty;

                /*
                |--------------------------------------------------------------------------
                | CEK STOK CUKUP
                |--------------------------------------------------------------------------
                */

                if ($qty > $qtyLama) {

                    $barang =
                        Barang::find(
                            $barangId
                        );

                    $namaBarang =
                        $barang
                            ? "{$barang->kode_barang} - {$barang->nama_barang}"
                            : "ID {$barangId}";

                    throw new RuntimeException(
                        "Stok barang '{$namaBarang}' "
                        . 'di gudang yang dipilih tidak mencukupi. '
                        . '(Stok tersedia: '
                        . number_format(
                            $qtyLama,
                            2,
                            ',',
                            '.'
                        )
                        . ', Dibutuhkan: '
                        . number_format(
                            $qty,
                            2,
                            ',',
                            '.'
                        )
                        . ')'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | HPP MOVING AVERAGE
                |--------------------------------------------------------------------------
                */

                $hargaRataRata =
                    $this->normalisasiNilai(
                        (float)
                        $stok->harga_rata_rata
                    );

                /*
                |--------------------------------------------------------------------------
                | NILAI PERSEDIAAN LAMA
                |--------------------------------------------------------------------------
                */

                $nilaiLama =
                    $this->normalisasiNilai(
                        (float)
                        $stok->nilai_persediaan
                    );

                /*
                |--------------------------------------------------------------------------
                | QTY BARU
                |--------------------------------------------------------------------------
                */

                $qtyBaru =
                    $this->normalisasiQty(
                        $qtyLama
                        - $qty
                    );

                /*
                |--------------------------------------------------------------------------
                | HITUNG HPP KELUAR
                |--------------------------------------------------------------------------
                |
                | Kalau semua stok keluar,
                | gunakan seluruh nilai saldo persediaan.
                |
                | Tujuannya supaya tidak ada sisa Rp0,01
                | karena pembulatan harga rata-rata.
                */

                if ($qtyBaru == 0.0) {

                    $nilaiKeluar =
                        $nilaiLama;

                    $nilaiBaru =
                        0.0;

                } else {

                    $nilaiKeluar =
                        $this->normalisasiNilai(
                            $qty
                            * $hargaRataRata
                        );

                    $nilaiBaru =
                        $this->normalisasiNilai(
                            $nilaiLama
                            - $nilaiKeluar
                        );

                    /*
                     * Toleransi pembulatan.
                     */
                    if (
                        $nilaiBaru < 0
                        && abs($nilaiBaru) < 0.01
                    ) {
                        $nilaiBaru = 0.0;
                    }

                    /*
                     * Nilai persediaan tidak boleh minus.
                     */
                    if ($nilaiBaru < 0) {
                        throw new RuntimeException(
                            'Nilai persediaan menjadi negatif. '
                            . 'Periksa saldo stok dan harga rata-rata.'
                        );
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | UPDATE STOK
                |--------------------------------------------------------------------------
                */

                $stok->update([
                    'qty' =>
                        $qtyBaru,

                    /*
                     * Harga rata-rata terakhir tetap disimpan
                     * walaupun qty menjadi 0.
                     */
                    'harga_rata_rata' =>
                        $hargaRataRata,

                    'nilai_persediaan' =>
                        $nilaiBaru,
                ]);

                /*
                |--------------------------------------------------------------------------
                | CATAT KARTU STOK
                |--------------------------------------------------------------------------
                */

                MutasiStok::create([
                    'tanggal' =>
                        $tanggalMutasi,

                    'barang_id' =>
                        $barangId,

                    'gudang_id' =>
                        $gudangId,

                    'tipe' =>
                        $tipeMutasi,

                    'qty_masuk' =>
                        0,

                    'qty_keluar' =>
                        $qty,

                    'harga_satuan' =>
                        $hargaRataRata,

                    'nilai' =>
                        $nilaiKeluar,

                    'saldo_qty' =>
                        $qtyBaru,

                    'saldo_nilai' =>
                        $nilaiBaru,

                    'reference_type' =>
                        $referenceType,

                    'reference_id' =>
                        $referenceId,

                    'keterangan' =>
                        $keterangan,
                ]);

                return $stok->fresh();
            },
            3
        );
    }

    /**
     * ===============================================================
     * ALIAS STOK MASUK
     * ===============================================================
     *
     * Dipertahankan supaya ReturPenjualanController
     * yang sekarang tidak rusak.
     */
    public function masuk(
        int $gudangId,
        int $barangId,
        float $qty,
        float $hargaSatuan,
        string $referenceType,
        int $referenceId,
        ?string $referenceNumber = null,
        ?string $keterangan = null,
        $tanggalTransaksi = null
    ): StokGudang {

        $tipeMutasi =
            strtoupper($referenceType)
                === 'RETUR_PENJUALAN'

                ? MutasiStok::TIPE_RETUR_PENJUALAN

                : MutasiStok::TIPE_RETUR_MASUK;

        return $this->stokMasuk(
            barangId:
                $barangId,

            gudangId:
                $gudangId,

            qty:
                $qty,

            hargaSatuan:
                $hargaSatuan,

            referenceType:
                $referenceType,

            referenceId:
                $referenceId,

            keterangan:
                $keterangan,

            tipeMutasi:
                $tipeMutasi,

            tanggalTransaksi:
                $tanggalTransaksi
        );
    }

    /**
     * ===============================================================
     * ALIAS STOK KELUAR
     * ===============================================================
     */
    public function keluar(
        int $gudangId,
        int $barangId,
        float $qty,
        string $referenceType,
        int $referenceId,
        ?string $referenceNumber = null,
        ?string $keterangan = null,
        $tanggalTransaksi = null
    ): StokGudang {

        $tipeMutasi =
            strtoupper($referenceType)
                === 'RETUR_PEMBELIAN'

                ? MutasiStok::TIPE_RETUR_KELUAR

                : MutasiStok::TIPE_KELUAR;

        return $this->stokKeluar(
            barangId:
                $barangId,

            gudangId:
                $gudangId,

            qty:
                $qty,

            referenceType:
                $referenceType,

            referenceId:
                $referenceId,

            keterangan:
                $keterangan,

            tipeMutasi:
                $tipeMutasi,

            tanggalTransaksi:
                $tanggalTransaksi
        );
    }

    /**
     * ===============================================================
     * AMBIL / BUAT STOK
     * ===============================================================
     */
    private function ambilAtauBuatStok(
        int $barangId,
        int $gudangId
    ): StokGudang {

        $stok =
            StokGudang::where(
                'barang_id',
                $barangId
            )
                ->where(
                    'gudang_id',
                    $gudangId
                )
                ->lockForUpdate()
                ->first();

        if ($stok) {
            return $stok;
        }

        /*
         * Buat saldo awal 0 jika kombinasi
         * barang + gudang belum tersedia.
         */

        try {

            StokGudang::create([
                'barang_id' =>
                    $barangId,

                'gudang_id' =>
                    $gudangId,

                'qty' =>
                    0,

                'harga_rata_rata' =>
                    0,

                'nilai_persediaan' =>
                    0,
            ]);

        } catch (
            \Illuminate\Database\QueryException $e
        ) {

            /*
             * Jika dua transaksi pertama untuk barang/gudang
             * yang sama terjadi bersamaan,
             * unique index bisa menghasilkan duplicate key.
             *
             * Duplicate tersebut boleh diabaikan.
             * Error database lain tetap dilempar.
             */

            if (
                !in_array(
                    (string) $e->getCode(),
                    [
                        '23000',
                        '23505',
                    ],
                    true
                )
            ) {
                throw $e;
            }
        }

        return StokGudang::where(
            'barang_id',
            $barangId
        )
            ->where(
                'gudang_id',
                $gudangId
            )
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * ===============================================================
     * VALIDASI TANGGAL
     * ===============================================================
     *
     * Moving average tidak aman jika transaksi dimasukkan
     * mundur melewati mutasi yang sudah ada.
     */
    private function validateUrutanTanggal(
        int $barangId,
        int $gudangId,
        Carbon $tanggalMutasi
    ): void {

        $mutasiTerakhir =
            MutasiStok::where(
                'barang_id',
                $barangId
            )
                ->where(
                    'gudang_id',
                    $gudangId
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
            $tanggalMutasi
                ->copy()
                ->startOfDay()
                ->lt(
                    $tanggalTerakhir
                        ->copy()
                        ->startOfDay()
                )
        ) {
            throw new RuntimeException(
                'Tanggal transaksi stok tidak boleh '
                . 'sebelum tanggal mutasi stok terakhir '
                . 'untuk barang dan gudang ini. '
                . 'Tanggal mutasi terakhir: '
                . $tanggalTerakhir
                    ->format('d/m/Y')
                . '.'
            );
        }
    }

    /**
     * ===============================================================
     * NORMALISASI TIPE STOK MASUK
     * ===============================================================
     *
     * Controller lama masih mengirim MASUK generik.
     *
     * Supaya histori lebih jelas, reference_type tertentu
     * otomatis menjadi tipe mutasi spesifik.
     */
    private function resolveTipeMasuk(
        string $tipeMutasi,
        string $referenceType
    ): string {

        if (
            $tipeMutasi
            !== MutasiStok::TIPE_MASUK
        ) {
            return $tipeMutasi;
        }

        return match (
            strtoupper($referenceType)
        ) {

            'RETUR_PENJUALAN' =>
                MutasiStok::TIPE_RETUR_PENJUALAN,

            'PEMBATALAN_INVOICE' =>
                MutasiStok::TIPE_PEMBATALAN_INVOICE,

            'KONVERSI_BARANG' =>
                MutasiStok::TIPE_KONVERSI_MASUK,

            default =>
                $tipeMutasi,
        };
    }

    /**
     * ===============================================================
     * NORMALISASI TIPE STOK KELUAR
     * ===============================================================
     */
    private function resolveTipeKeluar(
        string $tipeMutasi,
        string $referenceType
    ): string {

        if (
            $tipeMutasi
            !== MutasiStok::TIPE_KELUAR
        ) {
            return $tipeMutasi;
        }

        return match (
            strtoupper($referenceType)
        ) {

            'PENJUALAN' =>
                MutasiStok::TIPE_PENJUALAN,

            'INVOICE_PENJUALAN' =>
                MutasiStok::TIPE_INVOICE_PENJUALAN,

            'RETUR_PEMBELIAN' =>
                MutasiStok::TIPE_RETUR_KELUAR,

            'KONVERSI_BARANG' =>
                MutasiStok::TIPE_KONVERSI_KELUAR,

            default =>
                $tipeMutasi,
        };
    }

    /**
     * ===============================================================
     * VALIDASI REFERENSI
     * ===============================================================
     */
    private function validateReference(
        string $referenceType,
        int $referenceId
    ): void {

        if (
            trim($referenceType)
            === ''
        ) {
            throw new RuntimeException(
                'Reference type mutasi stok wajib diisi.'
            );
        }

        /*
         * Reference ID 0 masih diperbolehkan
         * karena Import Stok Awal saat ini
         * menggunakan reference_id = 0.
         */
        if ($referenceId < 0) {
            throw new RuntimeException(
                'Reference ID mutasi stok tidak valid.'
            );
        }
    }

    /**
     * ===============================================================
     * RESOLVE TANGGAL TRANSAKSI
     * ===============================================================
     */
    private function resolveTanggalTransaksi(
        $tanggalTransaksi = null
    ): Carbon {

        if (
            empty($tanggalTransaksi)
        ) {
            return now();
        }

        try {

            $tanggal =
                Carbon::parse(
                    $tanggalTransaksi
                );

            /*
             * Jika hanya tanggal YYYY-MM-DD,
             * gunakan jam saat transaksi diproses.
             */
            if (
                is_string(
                    $tanggalTransaksi
                )
                &&
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
                'Tanggal transaksi stok tidak valid.'
            );
        }
    }

    /**
     * Qty database menggunakan 2 digit desimal.
     */
    private function normalisasiQty(
        float $qty
    ): float {

        return round(
            $qty,
            2
        );
    }

    /**
     * Nilai uang database menggunakan 2 digit desimal.
     */
    private function normalisasiNilai(
        float $nilai
    ): float {

        return round(
            $nilai,
            2
        );
    }
}