<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    protected $fillable = [
        'nomor_opname',
        'tanggal_opname',
        'gudang_id',
        'status',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_opname' => 'date',
    ];

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function items()
    {
        return $this->hasMany(StockOpnameItem::class);
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
| RELASI PENYESUAIAN STOK
|--------------------------------------------------------------------------
*/

public function penyesuaianStok()
{
    return $this->hasOne(
        PenyesuaianStok::class
    );
}
}