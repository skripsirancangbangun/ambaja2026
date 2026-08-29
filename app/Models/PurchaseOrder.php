<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'nomor_po',
        'supplier_id',
        'tanggal_po',
        'tanggal_estimasi_datang',
        'catatan',
        'subtotal',
        'diskon',
        'pajak',
        'pajak_persen',
        'total',
        'status',
        'created_by',
    ];

    protected $casts = [
        'tanggal_po' => 'date',
        'tanggal_estimasi_datang' => 'date',
        'subtotal' => 'decimal:2',
        'diskon' => 'decimal:2',
        'pajak' => 'decimal:2',
        'pajak_persen' => 'decimal:2',
        'total' => 'decimal:2',
    ];


    /*
    |--------------------------------------------------------------------------
    | RELASI SUPPLIER
    |--------------------------------------------------------------------------
    */

    public function supplier()
    {
        return $this->belongsTo(
            Supplier::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | RELASI ITEM
    |--------------------------------------------------------------------------
    */

    public function items()
    {
        return $this->hasMany(
            PurchaseOrderItem::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | RELASI USER PEMBUAT
    |--------------------------------------------------------------------------
    */

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | RELASI PENERIMAAN BARANG
    |--------------------------------------------------------------------------
    */

    public function penerimaanBarangs()
    {
        return $this->hasMany(
            PenerimaanBarang::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | RELASI INVOICE SUPPLIER
    |--------------------------------------------------------------------------
    */

    public function invoiceSuppliers()
    {
        return $this->hasMany(
            InvoiceSupplier::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | RELASI RETUR PEMBELIAN
    |--------------------------------------------------------------------------
    */

    public function returPembelians()
    {
        return $this->hasMany(
            ReturPembelian::class
        );
    }
}