<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceSupplier extends Model
{
    protected $fillable = [
        'nomor_invoice_internal',
        'nomor_invoice_supplier',
        'supplier_id',
        'purchase_order_id',
        'tanggal_invoice',
        'tanggal_jatuh_tempo',
        'subtotal',
        'diskon',
        'pajak',
        'pajak_persen',
        'total',
        'total_dibayar',
        'sisa_utang',
        'status',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_invoice' => 'date',
        'tanggal_jatuh_tempo' => 'date',

        'subtotal' => 'decimal:2',
        'diskon' => 'decimal:2',
        'pajak' => 'decimal:2',
        'pajak_persen' => 'decimal:2',

        'total' => 'decimal:2',
        'total_dibayar' => 'decimal:2',
        'sisa_utang' => 'decimal:2',
    ];


    /*
    |--------------------------------------------------------------------------
    | SUPPLIER
    |--------------------------------------------------------------------------
    */

    public function supplier()
    {
        return $this->belongsTo(
            Supplier::class,
            'supplier_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PURCHASE ORDER
    |--------------------------------------------------------------------------
    */

    public function purchaseOrder()
    {
        return $this->belongsTo(
            PurchaseOrder::class,
            'purchase_order_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DETAIL ITEM INVOICE
    |--------------------------------------------------------------------------
    */

    public function items()
    {
        return $this->hasMany(
            InvoiceSupplierItem::class,
            'invoice_supplier_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PEMBAYARAN SUPPLIER
    |--------------------------------------------------------------------------
    |
    | Satu invoice supplier dapat memiliki beberapa pembayaran.
    |
    | Contoh:
    | Invoice Rp 10.000.000
    | - Bayar pertama Rp 4.000.000
    | - Bayar kedua   Rp 3.000.000
    | - Bayar ketiga  Rp 3.000.000
    |
    */

    public function pembayaranSuppliers()
    {
        return $this->hasMany(
            PembayaranSupplier::class,
            'invoice_supplier_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | USER PEMBUAT
    |--------------------------------------------------------------------------
    */

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}