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
        // Hanya jurnal yang sudah diposting boleh memengaruhi saldo buku besar.
        // Draft tidak boleh mengubah saldo COA, neraca saldo, atau rekonsiliasi Kas/Bank.
        $postedDetails = $this->jurnalDetails()
            ->whereHas('jurnal', static function ($query) {
                $query->where('is_posted', true);
            });

        $debit = (float) (clone $postedDetails)->sum('debit');
        $kredit = (float) (clone $postedDetails)->sum('kredit');

        if ($this->saldo_normal === 'DEBIT') {
            return round((float) $this->saldo_awal + $debit - $kredit, 2);
        }

        return round((float) $this->saldo_awal + $kredit - $debit, 2);
    }
}
