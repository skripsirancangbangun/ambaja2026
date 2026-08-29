<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MutasiGudang extends Model
{
    protected $fillable = [
        'nomor_mutasi',
        'tanggal_mutasi',
        'gudang_asal_id',
        'gudang_tujuan_id',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_mutasi' => 'date',
    ];

    public function gudangAsal()
    {
        return $this->belongsTo(
            Gudang::class,
            'gudang_asal_id'
        );
    }

    public function gudangTujuan()
    {
        return $this->belongsTo(
            Gudang::class,
            'gudang_tujuan_id'
        );
    }

    public function items()
    {
        return $this->hasMany(
            MutasiGudangItem::class
        );
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}