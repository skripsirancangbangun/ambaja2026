<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturPembelianDetail extends Model
{
    protected $fillable = [
        'retur_pembelian_id',
        'penerimaan_barang_item_id',
        'barang_id',
        'qty',
        'harga_satuan',
        'subtotal',
        'keterangan',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELASI HEADER RETUR
    |--------------------------------------------------------------------------
    */

    public function returPembelian()
    {
        return $this->belongsTo(
            ReturPembelian::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI PENERIMAAN BARANG ITEM
    |--------------------------------------------------------------------------
    */

    public function penerimaanBarangItem()
    {
        return $this->belongsTo(
            PenerimaanBarangItem::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI BARANG
    |--------------------------------------------------------------------------
    */

    public function barang()
    {
        return $this->belongsTo(
            Barang::class
        );
    }
}