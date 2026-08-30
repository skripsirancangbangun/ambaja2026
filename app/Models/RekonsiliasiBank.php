<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekonsiliasiBank extends Model
{
    protected $fillable = [
        'transaksi_bank_id',
        'mutasi_kas_bank_id',
        'tanggal_rekonsiliasi',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_rekonsiliasi' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | TRANSAKSI REKENING KORAN
    |--------------------------------------------------------------------------
    */

    public function transaksiBank()
    {
        return $this->belongsTo(
            TransaksiBelumTeridentifikasi::class,
            'transaksi_bank_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MUTASI INTERNAL KAS / BANK
    |--------------------------------------------------------------------------
    */

    public function mutasiKasBank()
    {
        return $this->belongsTo(
            MutasiKasBank::class,
            'mutasi_kas_bank_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | USER YANG MELAKUKAN REKONSILIASI
    |--------------------------------------------------------------------------
    */

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}