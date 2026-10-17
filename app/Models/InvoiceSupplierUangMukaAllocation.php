<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceSupplierUangMukaAllocation extends Model
{
    protected $table =
        'invoice_supplier_uang_muka_allocations';

    protected $fillable = [
        'invoice_supplier_id',
        'purchase_order_id',
        'pembayaran_po_supplier_id',
        'jumlah_alokasi',
        'tanggal_alokasi',
        'created_by',
    ];

    protected $casts = [
        'jumlah_alokasi' => 'decimal:2',
        'tanggal_alokasi' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | INVOICE SUPPLIER
    |--------------------------------------------------------------------------
    */

    public function invoiceSupplier()
    {
        return $this->belongsTo(
            InvoiceSupplier::class,
            'invoice_supplier_id'
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
    | PEMBAYARAN PO SUPPLIER
    |--------------------------------------------------------------------------
    */

    public function pembayaranPOSupplier()
    {
        return $this->belongsTo(
            PembayaranPOSupplier::class,
            'pembayaran_po_supplier_id'
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