<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenyesuaianStokItem extends Model
{
    protected $fillable = [
        'penyesuaian_stok_id',
        'stock_opname_item_id',
        'barang_id',
        'qty_sebelum',
        'qty_penyesuaian',
        'qty_sesudah',
        'jenis',
        'harga_rata_rata',
        'nilai_penyesuaian',
        'keterangan',
    ];

    protected $casts = [
        'qty_sebelum' => 'decimal:2',
        'qty_penyesuaian' => 'decimal:2',
        'qty_sesudah' => 'decimal:2',
        'harga_rata_rata' => 'decimal:2',
        'nilai_penyesuaian' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELASI HEADER PENYESUAIAN
    |--------------------------------------------------------------------------
    */

    public function penyesuaianStok()
    {
        return $this->belongsTo(
            PenyesuaianStok::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI STOCK OPNAME ITEM
    |--------------------------------------------------------------------------
    */

    public function stockOpnameItem()
    {
        return $this->belongsTo(
            StockOpnameItem::class
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