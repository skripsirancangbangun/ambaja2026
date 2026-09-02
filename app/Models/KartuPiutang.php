<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KartuPiutang extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'tanggal',
        'nomor_referensi',
        'jenis_transaksi',
        'debit',
        'kredit',
        'saldo_akhir',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
