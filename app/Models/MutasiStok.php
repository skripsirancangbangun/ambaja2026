<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MutasiStok extends Model
{
    public const TIPE_MASUK = 'MASUK';
    public const TIPE_KELUAR = 'KELUAR';
    public const TIPE_PENYESUAIAN = 'PENYESUAIAN';

    public const TIPE_MUTASI_MASUK = 'MUTASI_MASUK';
    public const TIPE_MUTASI_KELUAR = 'MUTASI_KELUAR';

    public const TIPE_RETUR_MASUK = 'RETUR_MASUK';
    public const TIPE_RETUR_KELUAR = 'RETUR_KELUAR';

    public const TIPE_PENJUALAN = 'PENJUALAN';
    public const TIPE_INVOICE_PENJUALAN = 'INVOICE_PENJUALAN';
    public const TIPE_RETUR_PENJUALAN = 'RETUR_PENJUALAN';
    public const TIPE_PEMBATALAN_INVOICE = 'PEMBATALAN_INVOICE';

    /*
     * Sudah disiapkan untuk fitur Konversi Barang.
     */
    public const TIPE_KONVERSI_MASUK = 'KONVERSI_MASUK';
    public const TIPE_KONVERSI_KELUAR = 'KONVERSI_KELUAR';
    public const TIPE_WASTE = 'WASTE';

    protected $fillable = [
        'tanggal',
        'barang_id',
        'gudang_id',
        'tipe',
        'qty_masuk',
        'qty_keluar',
        'harga_satuan',
        'nilai',
        'saldo_qty',
        'saldo_nilai',
        'reference_type',
        'reference_id',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'qty_masuk' => 'decimal:2',
        'qty_keluar' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'nilai' => 'decimal:2',
        'saldo_qty' => 'decimal:2',
        'saldo_nilai' => 'decimal:2',
    ];

    public static function tipeMasuk(): array
    {
        return [
            self::TIPE_MASUK,
            self::TIPE_MUTASI_MASUK,
            self::TIPE_RETUR_MASUK,
            self::TIPE_RETUR_PENJUALAN,
            self::TIPE_PEMBATALAN_INVOICE,
            self::TIPE_PENYESUAIAN,
            self::TIPE_KONVERSI_MASUK,
        ];
    }

    public static function tipeKeluar(): array
    {
        return [
            self::TIPE_KELUAR,
            self::TIPE_MUTASI_KELUAR,
            self::TIPE_RETUR_KELUAR,
            self::TIPE_PENJUALAN,
            self::TIPE_INVOICE_PENJUALAN,
            self::TIPE_PENYESUAIAN,
            self::TIPE_KONVERSI_KELUAR,
            self::TIPE_WASTE,
        ];
    }

    public static function semuaTipe(): array
    {
        return array_values(
            array_unique([
                ...self::tipeMasuk(),
                ...self::tipeKeluar(),
            ])
        );
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }
}