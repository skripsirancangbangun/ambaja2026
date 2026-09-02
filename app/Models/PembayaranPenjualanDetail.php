<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembayaranPenjualanDetail extends Model
{
    protected $fillable = [
        'pembayaran_penjualan_id',
        'invoice_penjualan_id',
        'nominal_bayar',
    ];

    protected $casts = [
        'nominal_bayar' => 'decimal:2',
    ];

    public function pembayaranPenjualan()
    {
        return $this->belongsTo(PembayaranPenjualan::class);
    }

    public function invoicePenjualan()
    {
        return $this->belongsTo(InvoicePenjualan::class);
    }
}
