<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'kode_customer',
        'nama_customer',
        'npwp',
        'contact_person',
        'telepon',
        'email',
        'alamat',
        'kota',
        'limit_piutang',
        'is_active',
    ];

    protected $casts = [
        'limit_piutang' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function penjualans()
    {
        return $this->hasMany(Penjualan::class);
    }

    public function invoicePenjualans()
    {
        return $this->hasMany(InvoicePenjualan::class);
    }

    public function pembayaranPenjualans()
    {
        return $this->hasMany(PembayaranPenjualan::class);
    }

    public function returPenjualans()
    {
        return $this->hasMany(ReturPenjualan::class);
    }

    public function totalPiutang()
    {
        return $this->invoicePenjualans()
            ->where('status_pembayaran', '!=', 'Lunas')
            ->sum('sisa_piutang');
    }
}
