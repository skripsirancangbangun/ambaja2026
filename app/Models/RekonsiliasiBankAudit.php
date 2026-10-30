<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekonsiliasiBankAudit extends Model
{
    protected $fillable = [
        'rekonsiliasi_id',
        'transaksi_bank_id',
        'mutasi_kas_bank_id',
        'aksi',
        'data_sebelum',
        'alasan',
        'dibatalkan_oleh',
        'dibatalkan_pada',
    ];

    protected $casts = [
        'data_sebelum' => 'array',
        'dibatalkan_pada' => 'datetime',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'dibatalkan_oleh');
    }
}
