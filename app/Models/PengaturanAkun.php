<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengaturanAkun extends Model
{
    use HasFactory;

    protected $fillable = [
        'kunci_pengaturan',
        'nama_pengaturan',
        'akun_id',
        'keterangan',
    ];

    public function akun()
    {
        return $this->belongsTo(Akun::class, 'akun_id');
    }
}
