<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferKasBank extends Model
{
    protected $fillable = [
        'nomor_transaksi',
        'tanggal',
        'rekening_asal_id',
        'rekening_tujuan_id',
        'jumlah',
        'referensi',
        'keterangan',
        'status',
        'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'decimal:2',
    ];

    public function rekeningAsal()
    {
        return $this->belongsTo(
            RekeningKasBank::class,
            'rekening_asal_id'
        );
    }

    public function rekeningTujuan()
    {
        return $this->belongsTo(
            RekeningKasBank::class,
            'rekening_tujuan_id'
        );
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function mutasiKeluar()
    {
        return $this->hasOne(
            MutasiKasBank::class,
            'reference_id'
        )
            ->where('reference_type', 'TRANSFER_KAS_BANK')
            ->where('tipe', 'TRANSFER_KELUAR');
    }

    public function mutasiMasuk()
    {
        return $this->hasOne(
            MutasiKasBank::class,
            'reference_id'
        )
            ->where('reference_type', 'TRANSFER_KAS_BANK')
            ->where('tipe', 'TRANSFER_MASUK');
    }
}