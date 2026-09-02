<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenerimaanPiutang extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_penerimaan',
        'customer_id',
        'rekening_kas_bank_id',
        'tanggal',
        'total_dibayar',
        'catatan',
        'created_by_user_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function rekeningKasBank()
    {
        return $this->belongsTo(RekeningKasBank::class);
    }

    public function details()
    {
        return $this->hasMany(PenerimaanPiutangDetail::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
