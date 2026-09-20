<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfilPerusahaan extends Model
{
    protected $fillable = [
        'nama_perusahaan',
        'nama_singkat',
        'alamat',
        'telepon',
        'email',
        'website',
        'kota',
        'rekening_bca',
        'rekening_mandiri',
        'catatan_dokumen',
    ];

    public static function getProfil()
    {
        return static::first() ?? new static([
            'nama_perusahaan' => 'CV ANDIKA MUSTIKA (AMBAJA)',
            'nama_singkat' => 'AMBAJA',
            'alamat' => 'Jl. Mustika No. 88, Surabaya',
            'telepon' => '(031) 888-9999 / 085711223344',
            'email' => 'easeproject128@gmail.com',
            'website' => 'www.ambaja.co.id',
            'kota' => 'Surabaya',
            'rekening_bca' => '123-456-7890 a.n CV Andika Mustika',
            'rekening_mandiri' => '987-654-3210 a.n CV Andika Mustika',
            'catatan_dokumen' => 'Terima kasih atas kepercayaan Anda bertransaksi dengan CV Andika Mustika.',
        ]);
    }
}
