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
    | ITEMS
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
    | Pembayaran yang dilakukan setelah Invoice Supplier terbentuk.
    |
    | Ini berbeda dengan Pembayaran PO Supplier.
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
    | ALOKASI UANG MUKA PO
    |--------------------------------------------------------------------------
    |
    | Mencatat uang muka dari Pembayaran PO Supplier
    | yang sudah digunakan untuk invoice ini.
    |
    */

    public function uangMukaAllocations()
    {
        return $this->hasMany(
            InvoiceSupplierUangMukaAllocation::class,
            'invoice_supplier_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TOTAL UANG MUKA DIALOKASIKAN
    |--------------------------------------------------------------------------
    */

    public function getTotalUangMukaDialokasikanAttribute(): float
    {
        if (
            $this->relationLoaded(
                'uangMukaAllocations'
            )
        ) {
            $total =
                $this
                    ->uangMukaAllocations
                    ->sum(
                        'jumlah_alokasi'
                    );
        } else {
            $total =
                $this
                    ->uangMukaAllocations()
                    ->sum(
                        'jumlah_alokasi'
                    );
        }

        return round(
            (float) $total,
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TOTAL PEMBAYARAN INVOICE
    |--------------------------------------------------------------------------
    |
    | Pembayaran invoice biasa.
    | Tidak termasuk uang muka PO.
    |
    */

    public function getTotalPembayaranInvoiceAttribute(): float
    {
        return round(
            (float)
            $this
                ->pembayaranSuppliers()
                ->sum(
                    'jumlah_bayar'
                ),
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TOTAL PELUNASAN
    |--------------------------------------------------------------------------
    |
    | Invoice dapat dilunasi melalui:
    |
    | 1. Uang muka PO
    | 2. Pembayaran Supplier setelah invoice
    |
    */

    public function getTotalPelunasanAttribute(): float
    {
        return round(
            (float)
            $this->total_uang_muka_dialokasikan
            +
            (float)
            $this->total_pembayaran_invoice,
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREATOR
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
    | RETUR ALLOCATIONS
    |--------------------------------------------------------------------------
    */

    public function returAllocations()
    {
        return $this->hasMany(
            ReturPembelianAllocation::class,
            'invoice_supplier_id'
        );
    }
}