<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturPenjualanDetail extends Model
{
    protected $fillable = [
        'retur_penjualan_id',
        'barang_id',
        'qty',
        'harga_satuan',
        'subtotal',
        'alasan',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function returPenjualan()
    {
        return $this->belongsTo(ReturPenjualan::class);
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }
}
