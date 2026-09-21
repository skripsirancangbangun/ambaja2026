<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembayaranSupplier extends Model
{
    protected $fillable = [
        'nomor_pembayaran',
        'invoice_supplier_id',
        'supplier_id',
        'tanggal_pembayaran',
        'jumlah_bayar',
        'metode_pembayaran',
        'rekening_kas_bank_id',
        'referensi',
        'catatan',
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
    | Invoice Supplier
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
    | Supplier
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
    | Kas / Bank
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
    | Mutasi Kas / Bank
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
                'PEMBAYARAN_SUPPLIER'
            )
            ->where(
                'tipe',
                'PEMBAYARAN_SUPPLIER'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Jurnal
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
                'PEMBAYARAN_SUPPLIER'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Creator
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
    | Sudah Posting Kas Bank
    |--------------------------------------------------------------------------
    */

    public function getSudahPostingKasBankAttribute(): bool
    {
        return $this->mutasiKasBank
            !== null;
    }

    /*
    |--------------------------------------------------------------------------
    | Sudah Direkonsiliasi
    |--------------------------------------------------------------------------
    */

    public function getSudahDirekonsiliasiAttribute(): bool
    {
        return $this
            ->mutasiKasBank
            ?->rekonsiliasiBank
            !== null;
    }
}