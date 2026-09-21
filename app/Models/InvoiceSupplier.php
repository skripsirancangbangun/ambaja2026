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
        'total_retur',
        'sisa_utang',
        'kredit_supplier',

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
        'total_retur' => 'decimal:2',
        'sisa_utang' => 'decimal:2',
        'kredit_supplier' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(
            Supplier::class,
            'supplier_id'
        );
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(
            PurchaseOrder::class,
            'purchase_order_id'
        );
    }

    public function items()
    {
        return $this->hasMany(
            InvoiceSupplierItem::class,
            'invoice_supplier_id'
        );
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function returAllocations()
    {
        return $this->hasMany(
            ReturPembelianAllocation::class,
            'invoice_supplier_id'
        );
    }
}