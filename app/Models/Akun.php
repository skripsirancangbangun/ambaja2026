<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Akun extends Model
{
    protected $fillable = [
        'kode_akun',
        'nama_akun',
        'kategori_akun',
        'sub_kategori',
        'saldo_normal',
        'saldo_awal',
        'is_active',
    ];

    protected $casts = [
        'saldo_awal' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function jurnalDetails()
    {
        return $this->hasMany(JurnalDetail::class);
    }

    public function getSaldoSaatIniAttribute(): float
    {
        $debit = $this->jurnalDetails()->sum('debit');
        $kredit = $this->jurnalDetails()->sum('kredit');

        if ($this->saldo_normal === 'DEBIT') {
            return (float) $this->saldo_awal + ($debit - $kredit);
        } else {
            return (float) $this->saldo_awal + ($kredit - $debit);
        }
    }
}
