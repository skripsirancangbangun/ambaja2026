<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturPenjualan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_retur',
        'invoice_penjualan_id',
        'customer_id',
        'gudang_id',
        'tanggal_retur',
        'tipe_retur',
        'subtotal',
        'potongan_persen',
        'nominal_potongan',
        'pajak',
        'total',
        'total_retur',
        'potong_piutang',
        'pengembalian_kas',
        'rekening_kas_bank_id',
        'alasan',
        'alasan_retur',
        'created_by',
        'created_by_user_id',
    ];

    protected $casts = [
        'tanggal_retur' => 'date',
        'subtotal' => 'decimal:2',
        'potongan_persen' => 'decimal:2',
        'nominal_potongan' => 'decimal:2',
        'pajak' => 'decimal:2',
        'total' => 'decimal:2',
        'potong_piutang' => 'decimal:2',
        'pengembalian_kas' => 'decimal:2',
    ];

    public function rekeningKasBank()
    {
        return $this->belongsTo(RekeningKasBank::class);
    }

    public function invoicePenjualan()
    {
        return $this->belongsTo(InvoicePenjualan::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function items()
    {
        return $this->hasMany(ReturPenjualanItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
