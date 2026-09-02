<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturPenjualanItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'retur_penjualan_id',
        'barang_id',
        'qty',
        'harga_satuan',
        'subtotal',
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
