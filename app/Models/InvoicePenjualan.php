<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoicePenjualan extends Model
{
    protected $fillable = [
        'nomor_invoice',
        'penjualan_id',
        'customer_id',
        'gudang_id',
        'tanggal_invoice',
        'tanggal_jatuh_tempo',
        'tipe_pembayaran',
        'rekening_kas_bank_id',
        'subtotal',
        'diskon',
        'pajak_persen',
        'pajak',
        'total',
        'sudah_dibayar',
        'sisa_piutang',
        'status_pembayaran',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_invoice' => 'date',
        'tanggal_jatuh_tempo' => 'date',
        'subtotal' => 'decimal:2',
        'diskon' => 'decimal:2',
        'pajak_persen' => 'decimal:2',
        'pajak' => 'decimal:2',
        'total' => 'decimal:2',
        'sudah_dibayar' => 'decimal:2',
        'sisa_piutang' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class);
    }

    public function rekeningKasBank()
    {
        return $this->belongsTo(RekeningKasBank::class, 'rekening_kas_bank_id');
    }

    public function items()
    {
        return $this->hasMany(InvoicePenjualanItem::class);
    }

    public function pembayaranDetails()
    {
        return $this->hasMany(PembayaranPenjualanDetail::class);
    }

    public function returPenjualans()
    {
        return $this->hasMany(ReturPenjualan::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
