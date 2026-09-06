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
        'hpp_satuan',
        'total_hpp',
        'subtotal',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'hpp_satuan' => 'decimal:2',
        'total_hpp' => 'decimal:2',
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
