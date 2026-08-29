<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenerimaanBarang extends Model
{
    protected $fillable = [
        'nomor_penerimaan',
        'purchase_order_id',
        'gudang_id',
        'tanggal_penerimaan',
        'nomor_surat_jalan',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_penerimaan' => 'date',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function items()
    {
        return $this->hasMany(PenerimaanBarangItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
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