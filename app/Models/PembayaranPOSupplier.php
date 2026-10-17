<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembayaranPOSupplier extends Model
{
    protected $table =
        'pembayaran_po_suppliers';

    protected $fillable = [
        'nomor_pembayaran',
        'purchase_order_id',
        'supplier_id',
        'rekening_kas_bank_id',
        'tanggal_pembayaran',
        'jumlah_bayar',
        'metode_pembayaran',
        'nomor_referensi',
        'bukti_pembayaran',
        'status',
        'keterangan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_pembayaran' =>
            'date',

        'jumlah_bayar' =>
            'decimal:2',
    ];

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
    | KAS / BANK
    |--------------------------------------------------------------------------
    */

    public function rekeningKasBank()
    {
        return $this->belongsTo(
            RekeningKasBank::class,
            'rekening_kas_bank_id'
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

    /*
    |--------------------------------------------------------------------------
    | JURNAL
    |--------------------------------------------------------------------------
    */

    public function jurnal()
    {
        return $this->hasOne(
            Jurnal::class,
            'referensi_id'
        )
            ->where(
                'referensi_type',
                'PEMBAYARAN_PO_SUPPLIER'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | MUTASI KAS / BANK
    |--------------------------------------------------------------------------
    */

    public function mutasiKasBank()
    {
        return $this->hasOne(
            MutasiKasBank::class,
            'reference_id'
        )
            ->where(
                'reference_type',
                'PEMBAYARAN_PO_SUPPLIER'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | ALOKASI UANG MUKA
    |--------------------------------------------------------------------------
    |
    | Satu pembayaran PO dapat digunakan untuk
    | beberapa Invoice Supplier.
    |
    */

    public function uangMukaAllocations()
    {
        return $this->hasMany(
            InvoiceSupplierUangMukaAllocation::class,
            'pembayaran_po_supplier_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TOTAL SUDAH DIALOKASIKAN
    |--------------------------------------------------------------------------
    */

    public function getTotalDialokasikanAttribute(): float
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
    | SISA BELUM DIALOKASIKAN
    |--------------------------------------------------------------------------
    |
    | Contoh:
    |
    | Pembayaran PO       : Rp 6.300.000
    | Sudah ke Invoice    : Rp   630.000
    |
    | Sisa uang muka      : Rp 5.670.000
    |
    */

    public function getSisaBelumDialokasikanAttribute(): float
    {
        if (
            $this->status !==
            'TERCATAT'
        ) {
            return 0;
        }

        return round(
            max(
                0,

                (float)
                $this->jumlah_bayar

                -

                (float)
                $this->total_dialokasikan
            ),
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER STATUS
    |--------------------------------------------------------------------------
    */

    public function getIsDraftAttribute(): bool
    {
        return
            $this->status
            === 'DRAFT';
    }

    public function getIsTercatatAttribute(): bool
    {
        return
            $this->status
            === 'TERCATAT';
    }

    public function getIsDibatalkanAttribute(): bool
    {
        return
            $this->status
            === 'DIBATALKAN';
    }
}