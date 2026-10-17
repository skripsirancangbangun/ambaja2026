<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenerimaanBarangItem extends Model
{
    protected $fillable = [
        'penerimaan_barang_id',
        'purchase_order_item_id',
        'barang_id',
        'qty_diterima',
        'harga_satuan',
        'nilai_persediaan',
    ];

    protected $casts = [
        'qty_diterima' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'nilai_persediaan' => 'decimal:2',
    ];

    public function penerimaanBarang()
    {
        return $this->belongsTo(PenerimaanBarang::class);
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class);
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