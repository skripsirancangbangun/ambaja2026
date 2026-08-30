<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturPenjualan extends Model
{
    protected $fillable = [
        'nomor_retur',
        'invoice_penjualan_id',
        'customer_id',
        'gudang_id',
        'tanggal_retur',
        'total_retur',
        'alasan',
        'status',
        'created_by',
    ];

    protected $casts = [
        'tanggal_retur' => 'date',
        'total_retur' => 'decimal:2',
    ];

    public function invoicePenjualan()
    {
        return $this->belongsTo(InvoicePenjualan::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function details()
    {
        return $this->hasMany(ReturPenjualanDetail::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
