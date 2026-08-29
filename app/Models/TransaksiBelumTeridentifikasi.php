<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiBelumTeridentifikasi extends Model
{
    protected $fillable = [
        'tanggal',
        'rekening_kas_bank_id',
        'jenis',
        'jumlah',
        'nama_pengirim_penerima',
        'nomor_referensi_bank',
        'deskripsi_bank',

        'import_batch',
        'import_hash',
        'nama_file_import',

        'status',
        'matched_reference_type',
        'matched_reference_id',
        'identified_at',
        'identified_by',
        'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'decimal:2',
        'identified_at' => 'datetime',
    ];

    public function rekeningKasBank()
    {
        return $this->belongsTo(
            RekeningKasBank::class,
            'rekening_kas_bank_id'
        );
    }

    public function identifier()
    {
        return $this->belongsTo(
            User::class,
            'identified_by'
        );
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function rekonsiliasiBank()
    {
        return $this->hasOne(
            RekonsiliasiBank::class,
            'transaksi_bank_id'
        );
    }
}