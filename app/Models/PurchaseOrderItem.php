<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'barang_id',
        'qty',
        'harga_satuan',
        'diskon',
        'subtotal',
        'qty_diterima',
        'qty_dibatalkan',
        'keterangan',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'diskon' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'qty_diterima' => 'decimal:2',
        'qty_dibatalkan' => 'decimal:2',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(
            PurchaseOrder::class
        );
    }

    public function barang()
    {
        return $this->belongsTo(
            Barang::class
        );
    }

    public function penerimaanItems()
    {
        return $this->hasMany(
            PenerimaanBarangItem::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SISA YANG MASIH BISA DITERIMA
    |--------------------------------------------------------------------------
    */

    public function getSisaPenerimaanAttribute()
    {
        return max(
            0,
            (float) $this->qty
            - (float) $this->qty_diterima
            - (float) $this->qty_dibatalkan
        );
    }
}