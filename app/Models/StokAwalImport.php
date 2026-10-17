<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokAwalImport extends Model
{
    protected $fillable = [
        'nomor_import', 'tanggal', 'file_hash', 'total_qty',
        'total_nilai', 'jurnal_id', 'created_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'total_qty' => 'decimal:2',
        'total_nilai' => 'decimal:2',
    ];

    public function jurnal()
    {
        return $this->belongsTo(Jurnal::class, 'jurnal_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
