<?php

namespace App\Services;

use App\Models\MutasiStok;
use App\Models\StokGudang;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    /**
     * Stok masuk.
     */
    public function stokMasuk(
        int $barangId,
        int $gudangId,
        float $qty,
        float $hargaSatuan,
        string $referenceType,
        int $referenceId,
        ?string $keterangan = null,
        string $tipeMutasi = 'MASUK',
        $tanggalTransaksi = null
    ): StokGudang {

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

        $allowedTypes = [
            'MASUK',
            'MUTASI_MASUK',
            'RETUR_MASUK',
            'PENYESUAIAN',
        ];

        if (!in_array(
            $tipeMutasi,
            $allowedTypes,
            true
        )) {
            throw new RuntimeException(
                'Tipe mutasi stok masuk tidak valid.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | TANGGAL TRANSAKSI
        |--------------------------------------------------------------------------
        */

        $tanggalMutasi =
            $this->resolveTanggalTransaksi(
                $tanggalTransaksi
            );


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
                $tanggalMutasi
            ) {

                /*
                |--------------------------------------------------------------------------
                | LOCK STOK
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
                | VALIDASI URUTAN TANGGAL
                |--------------------------------------------------------------------------
                |
                | Dilakukan setelah lock stok supaya transaksi
                | untuk barang + gudang yang sama tidak saling
                | mendahului saat diproses bersamaan.
                */

                $this->validateUrutanTanggal(
                    $barangId,
                    $gudangId,
                    $tanggalMutasi
                );


                /*
                |--------------------------------------------------------------------------
                | BUAT STOK JIKA BELUM ADA
                |--------------------------------------------------------------------------
                */

                if (!$stok) {

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
                            ->firstOrFail();
                }


                /*
                |--------------------------------------------------------------------------
                | SALDO LAMA
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

                $nilaiMasuk =
                    $qty * $hargaSatuan;


                /*
                |--------------------------------------------------------------------------
                | SALDO BARU
                |--------------------------------------------------------------------------
                */

                $qtyBaru =
                    $qtyLama + $qty;

                $nilaiBaru =
                    $nilaiLama
                    + $nilaiMasuk;


                /*
                |--------------------------------------------------------------------------
                | MOVING AVERAGE
                |--------------------------------------------------------------------------
                */

                $hargaRataRataBaru =
                    $qtyBaru > 0
                        ? $nilaiBaru / $qtyBaru
                        : 0;


                /*
                |--------------------------------------------------------------------------
                | UPDATE STOK GUDANG
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
                | CATAT MUTASI STOK
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

                    /*
                     * Nilai masuk disimpan positif.
                     */
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
            }
        );
    }


    /**
     * Stok keluar.
     */
    public function stokKeluar(
        int $barangId,
        int $gudangId,
        float $qty,
        string $referenceType,
        int $referenceId,
        ?string $keterangan = null,
        string $tipeMutasi = 'KELUAR',
        $tanggalTransaksi = null
    ): StokGudang {

        if ($qty <= 0) {
            throw new RuntimeException(
                'Qty stok keluar harus lebih dari 0.'
            );
        }


        $allowedTypes = [
            'KELUAR',
            'MUTASI_KELUAR',
            'RETUR_KELUAR',
            'PENYESUAIAN',
        ];


        if (!in_array(
            $tipeMutasi,
            $allowedTypes,
            true
        )) {
            throw new RuntimeException(
                'Tipe mutasi stok keluar tidak valid.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | TANGGAL TRANSAKSI
        |--------------------------------------------------------------------------
        */

        $tanggalMutasi =
            $this->resolveTanggalTransaksi(
                $tanggalTransaksi
            );


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
                | LOCK STOK
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
                | VALIDASI STOK
                |--------------------------------------------------------------------------
                */

                if (!$stok) {
                    throw new RuntimeException(
                        'Stok barang di gudang tidak ditemukan.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | VALIDASI URUTAN TANGGAL
                |--------------------------------------------------------------------------
                */

                $this->validateUrutanTanggal(
                    $barangId,
                    $gudangId,
                    $tanggalMutasi
                );


                $qtyLama =
                    (float) $stok->qty;


                if ($qty > $qtyLama) {
                    throw new RuntimeException(
                        'Stok tidak mencukupi.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | MOVING AVERAGE SAAT BARANG KELUAR
                |--------------------------------------------------------------------------
                */

                $hargaRataRata =
                    (float)
                    $stok->harga_rata_rata;


                $nilaiKeluar =
                    $qty
                    * $hargaRataRata;


                /*
                |--------------------------------------------------------------------------
                | SALDO BARU
                |--------------------------------------------------------------------------
                */

                $qtyBaru =
                    $qtyLama - $qty;


                $nilaiLama =
                    (float)
                    $stok->nilai_persediaan;


                $nilaiBaru =
                    $nilaiLama
                    - $nilaiKeluar;


                /*
                 * Cegah angka negatif sangat kecil
                 * akibat floating point.
                 */

                if (
                    $nilaiBaru < 0
                    &&
                    abs($nilaiBaru) < 0.01
                ) {
                    $nilaiBaru = 0;
                }


                /*
                 * Nilai persediaan tidak boleh negatif.
                 */

                if ($nilaiBaru < 0) {
                    $nilaiBaru = 0;
                }


                /*
                 * Jika stok habis, saldo qty dan nilai
                 * dibuat benar-benar nol.
                 */

                if (
                    abs($qtyBaru)
                    < 0.0001
                ) {
                    $qtyBaru = 0;
                    $nilaiBaru = 0;
                }


                /*
                |--------------------------------------------------------------------------
                | UPDATE STOK GUDANG
                |--------------------------------------------------------------------------
                */

                $stok->update([
                    'qty' =>
                        $qtyBaru,

                    /*
                     * Barang keluar tidak mengubah
                     * moving average.
                     *
                     * Jika stok habis, average direset.
                     */
                    'harga_rata_rata' =>
                        $qtyBaru > 0
                            ? $hargaRataRata
                            : 0,

                    'nilai_persediaan' =>
                        $nilaiBaru,
                ]);


                /*
                |--------------------------------------------------------------------------
                | CATAT MUTASI
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

                    /*
                     * Harga keluar menggunakan
                     * moving average saat transaksi.
                     */
                    'harga_satuan' =>
                        $hargaRataRata,

                    /*
                     * Nilai keluar disimpan positif.
                     */
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
            }
        );
    }


    /**
     * Cegah transaksi stok dimasukkan
     * dengan tanggal yang lebih lama
     * daripada mutasi terakhir.
     *
     * Validasi dilakukan per:
     *
     * barang + gudang
     *
     * Transaksi pada tanggal yang sama
     * tetap diperbolehkan.
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
                ->orderByDesc('tanggal')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();


        if (!$mutasiTerakhir) {
            return;
        }


        $tanggalTerakhir =
            Carbon::parse(
                $mutasiTerakhir->tanggal
            );


        /*
        |--------------------------------------------------------------------------
        | BANDINGKAN BERDASARKAN TANGGAL
        |--------------------------------------------------------------------------
        |
        | Jam sengaja tidak dibandingkan.
        |
        | Contoh:
        | Mutasi terakhir : 28/08/2026 15:00
        | Transaksi baru  : 28/08/2026 10:00
        |
        | Tetap boleh karena masih pada tanggal
        | transaksi yang sama.
        |
        */

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
                'Tanggal transaksi stok tidak boleh sebelum tanggal mutasi stok terakhir untuk barang dan gudang ini. '
                . 'Tanggal mutasi terakhir: '
                . $tanggalTerakhir
                    ->format('d/m/Y')
                . '.'
            );
        }
    }


    /**
     * Tentukan tanggal yang dicatat
     * ke ledger MutasiStok.
     */
    private function resolveTanggalTransaksi(
        $tanggalTransaksi = null
    ): Carbon {

        if (empty($tanggalTransaksi)) {
            return now();
        }


        try {

            $tanggal =
                Carbon::parse(
                    $tanggalTransaksi
                );


            /*
             * Jika controller hanya mengirim YYYY-MM-DD,
             * gunakan jam saat proses dilakukan.
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

                $sekarang = now();


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
}