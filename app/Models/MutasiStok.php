<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MutasiStok extends Model
{
    protected $fillable = [
        'tanggal',
        'barang_id',
        'gudang_id',
        'tipe',
        'qty_masuk',
        'qty_keluar',
        'harga_satuan',
        'nilai',
        'saldo_qty',
        'saldo_nilai',
        'reference_type',
        'reference_id',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'qty_masuk' => 'decimal:2',
        'qty_keluar' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'nilai' => 'decimal:2',
        'saldo_qty' => 'decimal:2',
        'saldo_nilai' => 'decimal:2',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }
}