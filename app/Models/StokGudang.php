<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokGudang extends Model
{
    protected $fillable = [
        'barang_id',
        'gudang_id',
        'qty',
        'harga_rata_rata',
        'nilai_persediaan',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'harga_rata_rata' => 'decimal:2',
        'nilai_persediaan' => 'decimal:2',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    /**
     * HPP per unit menggunakan Moving Average.
     */
    public function getHppSatuanAttribute(): float
    {
        return (float) $this->harga_rata_rata;
    }
}