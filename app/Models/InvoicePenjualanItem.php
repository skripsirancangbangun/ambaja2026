<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoicePenjualanItem extends Model
{
    protected $fillable = [
        'invoice_penjualan_id',
        'barang_id',
        'qty',
        'harga_satuan',
        'hpp_satuan',
        'total_hpp',
        'diskon',
        'subtotal',
        'catatan',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'hpp_satuan' => 'decimal:2',
        'total_hpp' => 'decimal:2',
        'diskon' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function invoicePenjualan()
    {
        return $this->belongsTo(InvoicePenjualan::class);
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }
}
