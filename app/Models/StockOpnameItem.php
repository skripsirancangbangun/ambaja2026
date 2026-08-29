<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpnameItem extends Model
{
    protected $fillable = [
        'stock_opname_id',
        'barang_id',
        'stok_sistem',
        'stok_fisik',
        'selisih',
        'harga_rata_rata',
        'nilai_selisih',
        'keterangan',
    ];

    protected $casts = [
        'stok_sistem' => 'decimal:2',
        'stok_fisik' => 'decimal:2',
        'selisih' => 'decimal:2',
        'harga_rata_rata' => 'decimal:2',
        'nilai_selisih' => 'decimal:2',
    ];

    public function stockOpname()
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }
    /*
|--------------------------------------------------------------------------
| RELASI PENYESUAIAN STOK ITEM
|--------------------------------------------------------------------------
*/

public function penyesuaianStokItem()
{
    return $this->hasOne(
        PenyesuaianStokItem::class
    );
}
}