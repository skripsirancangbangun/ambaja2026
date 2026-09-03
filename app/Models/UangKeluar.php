<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UangKeluar extends Model
{
    protected $fillable = [
        'nomor_transaksi',
        'tanggal',
        'rekening_kas_bank_id',
        'jumlah',
        'tujuan',
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
            'UANG_KELUAR'
        );
    }

    public function details()
{
    return $this->hasMany(UangKeluarDetail::class);
}
}