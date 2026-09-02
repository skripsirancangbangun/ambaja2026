<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenutupanPeriode extends Model
{
    use HasFactory;

    protected $fillable = [
        'bulan',
        'tahun',
        'status',
        'tanggal_penutupan',
        'closed_by_user_id',
        'tanggal_pembukaan',
        'opened_by_user_id',
        'alasan_buka_kembali',
        'catatan',
    ];

    protected $casts = [
        'tanggal_penutupan' => 'datetime',
        'tanggal_pembukaan' => 'datetime',
    ];

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }
}
