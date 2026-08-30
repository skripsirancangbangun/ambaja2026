<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturPembelian extends Model
{
    protected $fillable = [
        'nomor_retur',
        'penerimaan_barang_id',
        'purchase_order_id',
        'supplier_id',
        'gudang_id',
        'tanggal_retur',
        'alasan_retur',
        'status',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_retur' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELASI PENERIMAAN BARANG
    |--------------------------------------------------------------------------
    */

    public function penerimaanBarang()
    {
        return $this->belongsTo(
            PenerimaanBarang::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI PURCHASE ORDER
    |--------------------------------------------------------------------------
    */

    public function purchaseOrder()
    {
        return $this->belongsTo(
            PurchaseOrder::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI SUPPLIER
    |--------------------------------------------------------------------------
    */

    public function supplier()
    {
        return $this->belongsTo(
            Supplier::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI GUDANG
    |--------------------------------------------------------------------------
    */

    public function gudang()
    {
        return $this->belongsTo(
            Gudang::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI DETAIL RETUR
    |--------------------------------------------------------------------------
    */

    public function details()
    {
        return $this->hasMany(
            ReturPembelianDetail::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI USER PEMBUAT
    |--------------------------------------------------------------------------
    */

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}