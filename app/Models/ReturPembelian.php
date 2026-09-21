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
            PenerimaanBarang::class,
            'penerimaan_barang_id',
            'id'
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
            PurchaseOrder::class,
            'purchase_order_id',
            'id'
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
            Supplier::class,
            'supplier_id',
            'id'
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
            Gudang::class,
            'gudang_id',
            'id'
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
            ReturPembelianDetail::class,
            'retur_pembelian_id',
            'id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI ALOKASI RETUR KE INVOICE SUPPLIER
    |--------------------------------------------------------------------------
    */

    public function allocations()
    {
        return $this->hasMany(
            ReturPembelianAllocation::class,
            'retur_pembelian_id',
            'id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI JURNAL RETUR PEMBELIAN
    |--------------------------------------------------------------------------
    */

    public function jurnal()
    {
        return $this->hasOne(
            Jurnal::class,
            'referensi_id',
            'id'
        )
        ->where(
            'referensi_type',
            'RETUR_PEMBELIAN'
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
            'created_by',
            'id'
        );
    }
}