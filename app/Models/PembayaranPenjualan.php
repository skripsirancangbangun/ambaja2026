<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembayaranPenjualan extends Model
{
    protected $fillable = [
        'nomor_pembayaran',
        'customer_id',
        'rekening_kas_bank_id',
        'tanggal_pembayaran',
        'metode_pembayaran',
        'total_bayar',
        'referensi_transaksi',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_pembayaran' => 'date',
        'total_bayar' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function rekeningKasBank()
    {
        return $this->belongsTo(RekeningKasBank::class, 'rekening_kas_bank_id');
    }

    public function details()
    {
        return $this->hasMany(PembayaranPenjualanDetail::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
