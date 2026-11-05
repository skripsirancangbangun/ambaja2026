<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermintaanPembelianDetail extends Model
{
    protected $fillable = [
        'permintaan_pembelian_id',
        'barang_id',
        'stok_saat_pengajuan',
        'minimum_stok',
        'qty_diminta',
        'qty_disetujui',
    ];

    protected $casts = [
        'stok_saat_pengajuan' => 'decimal:2',
        'minimum_stok' => 'decimal:2',
        'qty_diminta' => 'decimal:2',
        'qty_disetujui' => 'decimal:2',
    ];

    public function permintaanPembelian(): BelongsTo
    {
        return $this->belongsTo(PermintaanPembelian::class);
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class);
    }
}
