<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JurnalDetail extends Model
{
    protected $fillable = [
        'jurnal_id',
        'akun_id',
        'debit',
        'kredit',
        'keterangan',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'kredit' => 'decimal:2',
    ];

    public function jurnal()
    {
        return $this->belongsTo(Jurnal::class);
    }

    public function akun()
    {
        return $this->belongsTo(Akun::class);
    }
}
