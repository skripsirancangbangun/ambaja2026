<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UangMasuk extends Model
{
    protected $fillable = [
        'nomor_transaksi',
        'tanggal',
        'rekening_kas_bank_id',
        'akun_lawan_id',
        'jumlah',
        'sumber',
        'referensi',
        'keterangan',
        'status',
        'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'decimal:2',
    ];

    public function rekeningKasBank()
    {
        return $this->belongsTo(
            RekeningKasBank::class,
            'rekening_kas_bank_id'
        );
    }

    /**
     * Akun lawan untuk jurnal Uang Masuk.
     *
     * Contoh:
     * - Setoran owner -> Modal Owner
     * - Pendapatan lain -> Pendapatan Lain-lain
     * - Pinjaman bank -> Utang Bank
     */
    public function akunLawan()
    {
        return $this->belongsTo(
            Akun::class,
            'akun_lawan_id'
        );
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function mutasiKasBank()
    {
        return $this->hasOne(
            MutasiKasBank::class,
            'reference_id'
        )->where(
            'reference_type',
            'UANG_MASUK'
        );
    }

    /**
     * Jurnal otomatis dari transaksi Uang Masuk.
     */
    public function jurnal()
    {
        return $this->hasOne(
            Jurnal::class,
            'referensi_id'
        )->where(
            'referensi_type',
            'UANG_MASUK'
        );
    }
}