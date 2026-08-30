<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    protected $fillable = [
        'nomor_penjualan',
        'customer_id',
        'gudang_id',
        'tanggal_penjualan',
        'tanggal_estimasi_kirim',
        'catatan',
        'subtotal',
        'diskon',
        'pajak_persen',
        'pajak',
        'total',
        'status',
        'created_by',
    ];

    protected $casts = [
        'tanggal_penjualan' => 'date',
        'tanggal_estimasi_kirim' => 'date',
        'subtotal' => 'decimal:2',
        'diskon' => 'decimal:2',
        'pajak_persen' => 'decimal:2',
        'pajak' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function items()
    {
        return $this->hasMany(PenjualanItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoicePenjualans()
    {
        return $this->hasMany(InvoicePenjualan::class);
    }
}
