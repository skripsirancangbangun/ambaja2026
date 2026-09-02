<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TindakLanjutPiutang extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_penjualan_id',
        'customer_id',
        'tanggal_tindak_lanjut',
        'jenis_tindak_lanjut',
        'tanggal_janji_bayar',
        'catatan',
        'created_by_user_id',
    ];

    protected $casts = [
        'tanggal_tindak_lanjut' => 'date',
        'tanggal_janji_bayar' => 'date',
    ];

    public function invoicePenjualan()
    {
        return $this->belongsTo(InvoicePenjualan::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
