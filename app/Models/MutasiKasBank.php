<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MutasiKasBank extends Model
{
    protected $fillable = [
        'tanggal',
        'rekening_kas_bank_id',
        'tipe',
        'debit',
        'kredit',
        'saldo',
        'reference_type',
        'reference_id',
        'nomor_referensi',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'debit' => 'decimal:2',
        'kredit' => 'decimal:2',
        'saldo' => 'decimal:2',
    ];


    /*
    |--------------------------------------------------------------------------
    | REKENING KAS / BANK
    |--------------------------------------------------------------------------
    */

    public function rekeningKasBank()
    {
        return $this->belongsTo(
            RekeningKasBank::class,
            'rekening_kas_bank_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | REKONSILIASI BANK
    |--------------------------------------------------------------------------
    */

    public function rekonsiliasiBank()
    {
        return $this->hasOne(
            RekonsiliasiBank::class,
            'mutasi_kas_bank_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PEMBAYARAN SUPPLIER
    |--------------------------------------------------------------------------
    |
    | Relasi ini hanya berlaku apabila mutasi berasal dari
    | pembayaran supplier.
    |
    */

    public function pembayaranSupplier()
    {
        return $this->belongsTo(
            PembayaranSupplier::class,
            'reference_id'
        )
            ->where(
                'reference_type',
                'PEMBAYARAN_SUPPLIER'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | NILAI MUTASI
    |--------------------------------------------------------------------------
    */

    public function getNilaiMutasiAttribute(): float
    {
        if (
            (float) $this->debit > 0
        ) {
            return (float) $this->debit;
        }

        return (float) $this->kredit;
    }


    /*
    |--------------------------------------------------------------------------
    | ARAH MUTASI
    |--------------------------------------------------------------------------
    */

    public function getArahAttribute(): string
    {
        if (
            (float) $this->debit > 0
        ) {
            return 'MASUK';
        }

        return 'KELUAR';
    }


    /*
    |--------------------------------------------------------------------------
    | STATUS REKONSILIASI
    |--------------------------------------------------------------------------
    */

    public function getSudahDirekonsiliasiAttribute(): bool
    {
        return $this->rekonsiliasiBank !== null;
    }
}