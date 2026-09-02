<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jurnal extends Model
{
    protected $fillable = [
        'nomor_jurnal',
        'tanggal',
        'keterangan',
        'referensi_type',
        'referensi_id',
        'total_debit',
        'total_kredit',
        'is_posted',
        'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'total_debit' => 'decimal:2',
        'total_kredit' => 'decimal:2',
        'is_posted' => 'boolean',
    ];

    public function details()
    {
        return $this->hasMany(JurnalDetail::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
