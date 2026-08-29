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


    /*
    |--------------------------------------------------------------------------
    | CAST
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'tanggal_pembayaran' => 'date',
        'jumlah_bayar' => 'decimal:2',
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
    | REKENING KAS / BANK
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
    | MUTASI KAS / BANK
    |--------------------------------------------------------------------------
    |
    | Satu pembayaran supplier menghasilkan satu mutasi Kas/Bank.
    |
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
    | STATUS POSTING KAS / BANK
    |--------------------------------------------------------------------------
    */

    public function getSudahPostingKasBankAttribute(): bool
    {
        return $this->mutasiKasBank !== null;
    }


    /*
    |--------------------------------------------------------------------------
    | STATUS REKONSILIASI
    |--------------------------------------------------------------------------
    */

    public function getSudahDirekonsiliasiAttribute(): bool
    {
        return $this->mutasiKasBank
            ?->rekonsiliasiBank
            !== null;
    }
}