<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceSupplierItem extends Model
{
    protected $fillable = [
        'invoice_supplier_id',
        'barang_id',
        'penerimaan_barang_item_id',
        'qty',
        'harga_satuan',
        'diskon',
        'subtotal',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'diskon' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELASI INVOICE SUPPLIER
    |--------------------------------------------------------------------------
    */

    public function invoiceSupplier()
    {
        return $this->belongsTo(
            InvoiceSupplier::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI BARANG
    |--------------------------------------------------------------------------
    */

    public function barang()
    {
        return $this->belongsTo(
            Barang::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI PENERIMAAN BARANG ITEM
    |--------------------------------------------------------------------------
    */

    public function penerimaanBarangItem()
    {
        return $this->belongsTo(
            PenerimaanBarangItem::class
        );
    }
}