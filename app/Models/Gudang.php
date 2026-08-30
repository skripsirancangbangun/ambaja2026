<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gudang extends Model
{
    protected $fillable = [
        'kode_gudang',
        'nama_gudang',
        'alamat',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELASI PENERIMAAN BARANG
    |--------------------------------------------------------------------------
    */

    public function penerimaanBarangs()
    {
        return $this->hasMany(PenerimaanBarang::class);
    }


    /*
    |--------------------------------------------------------------------------
    | RELASI MUTASI GUDANG KELUAR
    |--------------------------------------------------------------------------
    */

    public function mutasiKeluar()
    {
        return $this->hasMany(
            MutasiGudang::class,
            'gudang_asal_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | RELASI MUTASI GUDANG MASUK
    |--------------------------------------------------------------------------
    */

    public function mutasiMasuk()
    {
        return $this->hasMany(
            MutasiGudang::class,
            'gudang_tujuan_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | RELASI STOK GUDANG
    |--------------------------------------------------------------------------
    */

    public function stokGudangs()
    {
        return $this->hasMany(
            StokGudang::class,
            'gudang_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | RELASI MUTASI STOK
    |--------------------------------------------------------------------------
    */

    public function mutasiStoks()
    {
        return $this->hasMany(
            MutasiStok::class,
            'gudang_id'
        );
    }

    /*
|--------------------------------------------------------------------------
| RELASI STOCK OPNAME
|--------------------------------------------------------------------------
*/

public function stockOpnames()
{
    return $this->hasMany(StockOpname::class);
}
/*
|--------------------------------------------------------------------------
| RELASI PENYESUAIAN STOK
|--------------------------------------------------------------------------
*/

public function penyesuaianStoks()
{
    return $this->hasMany(
        PenyesuaianStok::class
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