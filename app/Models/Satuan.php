<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Satuan extends Model
{
    protected $fillable = [
        'kode_satuan',
        'nama_satuan',
        'keterangan',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function barangs()
    {
        return $this->hasMany(Barang::class);
    }
}