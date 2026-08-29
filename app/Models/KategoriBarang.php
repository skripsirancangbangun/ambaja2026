<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriBarang extends Model
{
    protected $fillable = [
        'kode_kategori',
        'nama_kategori',
        'keterangan',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function barangs()
    {
        return $this->hasMany(
            Barang::class,
            'kategori_barang_id'
        );
    }
}