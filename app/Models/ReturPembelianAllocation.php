<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturPembelianAllocation extends Model
{
    protected $table = 'retur_pembelian_allocations';

    protected $guarded = [];

    public function returPembelian()
    {
        return $this->belongsTo(
            ReturPembelian::class,
            'retur_pembelian_id'
        );
    }

    public function returPembelianDetail()
    {
        return $this->belongsTo(
            ReturPembelianDetail::class,
            'retur_pembelian_detail_id'
        );
    }

    public function invoiceSupplier()
    {
        return $this->belongsTo(
            InvoiceSupplier::class,
            'invoice_supplier_id'
        );
    }

    public function invoiceSupplierItem()
    {
        return $this->belongsTo(
            InvoiceSupplierItem::class,
            'invoice_supplier_item_id'
        );
    }
}