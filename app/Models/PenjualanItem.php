<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanItem extends Model
{
    protected $fillable = [
        'penjualan_id',
        'barang_id',
        'qty',
        'qty_terinvoice',
        'harga_satuan',
        'diskon',
        'subtotal',
        'catatan',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'qty_terinvoice' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'diskon' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class);
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }
}
