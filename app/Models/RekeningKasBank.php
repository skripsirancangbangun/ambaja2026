<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekeningKasBank extends Model
{
    protected $fillable = [
        'akun_id',
        'kode_rekening',
        'nama_rekening',
        'jenis',
        'nama_bank',
        'nomor_rekening',
        'atas_nama',
        'saldo_awal',
        'is_active',
        'catatan',
    ];

    protected $casts = [
        'saldo_awal' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function akun()
    {
        return $this->belongsTo(Akun::class, 'akun_id');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER
    |--------------------------------------------------------------------------
    */

    public function getLabelAttribute(): string
    {
        if ($this->jenis === 'BANK') {
            $bank = $this->nama_bank
                ? ' - ' . $this->nama_bank
                : '';

            return $this->nama_rekening . $bank;
        }

        return $this->nama_rekening;
    }
}