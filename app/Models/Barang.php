<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $fillable = [
        'kode_barang',
        'nama_barang',
        'kategori_barang_id',
        'satuan_id',
        'spesifikasi',
        'merek',
        'harga_beli_default',
        'margin_persen',
        'minimum_stok',
        'harga_jual',
        'is_active',
    ];

    protected $casts = [
        'harga_beli_default' => 'decimal:2',
        'margin_persen' => 'decimal:2',
        'minimum_stok' => 'decimal:2',
        'harga_jual' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function kategori()
    {
        return $this->belongsTo(
            KategoriBarang::class,
            'kategori_barang_id'
        );
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class);
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
    public function mutasiGudangItems()
    {
        return $this->hasMany(
            MutasiGudangItem::class
        );
    }
    /*
|--------------------------------------------------------------------------
| RELASI STOCK OPNAME ITEM
|--------------------------------------------------------------------------
*/

public function stockOpnameItems()
{
    return $this->hasMany(StockOpnameItem::class);
}

/*
|--------------------------------------------------------------------------
| RELASI PENYESUAIAN STOK ITEM
|--------------------------------------------------------------------------
*/

public function penyesuaianStokItems()
{
    return $this->hasMany(
        PenyesuaianStokItem::class
    );
}

/*
|--------------------------------------------------------------------------
| RELASI INVOICE SUPPLIER ITEM
|--------------------------------------------------------------------------
*/

public function invoiceSupplierItems()
{
    return $this->hasMany(
        InvoiceSupplierItem::class
    );
}

/*
|--------------------------------------------------------------------------
| RELASI RETUR PEMBELIAN DETAIL
|--------------------------------------------------------------------------
*/

public function returPembelianDetails()
{
    return $this->hasMany(
        ReturPembelianDetail::class
    );
}
}