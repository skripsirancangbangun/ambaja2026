<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MutasiGudangItem extends Model
{
    protected $fillable = [
        'mutasi_gudang_id',
        'barang_id',
        'qty',
        'harga_rata_rata',
        'nilai',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'harga_rata_rata' => 'decimal:2',
        'nilai' => 'decimal:2',
    ];

    public function mutasiGudang()
    {
        return $this->belongsTo(
            MutasiGudang::class
        );
    }

    public function barang()
    {
        return $this->belongsTo(
            Barang::class
        );
    }
}