<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenyesuaianStok extends Model
{
    protected $fillable = [
        'nomor_penyesuaian',
        'tanggal_penyesuaian',
        'stock_opname_id',
        'gudang_id',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_penyesuaian' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELASI STOCK OPNAME
    |--------------------------------------------------------------------------
    */

    public function stockOpname()
    {
        return $this->belongsTo(
            StockOpname::class
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
    | RELASI DETAIL PENYESUAIAN
    |--------------------------------------------------------------------------
    */

    public function items()
    {
        return $this->hasMany(
            PenyesuaianStokItem::class
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