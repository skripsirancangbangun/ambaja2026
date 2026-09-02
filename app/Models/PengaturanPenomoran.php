<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengaturanPenomoran extends Model
{
    use HasFactory;

    protected $fillable = [
        'jenis_dokumen',
        'nama_dokumen',
        'prefix',
        'digits',
    ];
}
