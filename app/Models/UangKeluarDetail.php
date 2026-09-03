<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UangKeluarDetail extends Model
{
    protected $fillable = [
        'uang_keluar_id',
        'akun_id',
        'tujuan',
        'jumlah',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
    ];

    public function uangKeluar()
    {
        return $this->belongsTo(UangKeluar::class);
    }

    public function akun()
    {
        return $this->belongsTo(Akun::class);
    }
}