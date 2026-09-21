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
        'qty' =>
            'decimal:2',

        'harga_satuan' =>
            'decimal:2',

        'subtotal' =>
            'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | RETUR PEMBELIAN
    |--------------------------------------------------------------------------
    */

    public function returPembelian()
    {
        return $this->belongsTo(
            ReturPembelian::class,
            'retur_pembelian_id',
            'id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PENERIMAAN BARANG ITEM
    |--------------------------------------------------------------------------
    */

    public function penerimaanBarangItem()
    {
        return $this->belongsTo(
            PenerimaanBarangItem::class,
            'penerimaan_barang_item_id',
            'id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | BARANG
    |--------------------------------------------------------------------------
    */

    public function barang()
    {
        return $this->belongsTo(
            Barang::class,
            'barang_id',
            'id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ALOKASI KE INVOICE SUPPLIER
    |--------------------------------------------------------------------------
    */

    public function allocations()
    {
        return $this->hasMany(
            ReturPembelianAllocation::class,
            'retur_pembelian_detail_id',
            'id'
        );
    }
}