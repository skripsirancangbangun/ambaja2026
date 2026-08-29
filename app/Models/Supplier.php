<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'kode_supplier',
        'nama_supplier',
        'npwp',
        'contact_person',
        'telepon',
        'email',
        'alamat',
        'kota',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
    public function purchaseOrders()
{
    return $this->hasMany(PurchaseOrder::class);
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
| RELASI PEMBAYARAN SUPPLIER
|--------------------------------------------------------------------------
*/

public function pembayaranSuppliers()
{
    return $this->hasMany(
        PembayaranSupplier::class
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