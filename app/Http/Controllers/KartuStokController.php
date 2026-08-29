<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\MutasiStok;
use Illuminate\Http\Request;

class KartuStokController extends Controller
{
    public function index(Request $request)
    {
        $query = MutasiStok::with([
            'barang.satuan',
            'barang.kategori',
            'gudang',
        ]);

        /*
        |--------------------------------------------------------------------------
        | FILTER BARANG
        |--------------------------------------------------------------------------
        */

        if ($request->filled('barang_id')) {
            $query->where(
                'barang_id',
                $request->barang_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER GUDANG
        |--------------------------------------------------------------------------
        */

        if ($request->filled('gudang_id')) {
            $query->where(
                'gudang_id',
                $request->gudang_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER TIPE
        |--------------------------------------------------------------------------
        */

        if ($request->filled('tipe')) {
            $query->where(
                'tipe',
                $request->tipe
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER TANGGAL
        |--------------------------------------------------------------------------
        */

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate(
                'tanggal',
                '>=',
                $request->tanggal_mulai
            );
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate(
                'tanggal',
                '<=',
                $request->tanggal_selesai
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where(
                    'keterangan',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'reference_type',
                    'like',
                    "%{$search}%"
                )
                ->orWhereHas(
                    'barang',
                    function ($barangQuery) use ($search) {

                        $barangQuery
                            ->where(
                                'kode_barang',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'nama_barang',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'spesifikasi',
                                'like',
                                "%{$search}%"
                            );
                    }
                );
            });
        }

        $mutasiStoks = $query
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $barangs = Barang::with('satuan')
            ->where('is_active', true)
            ->orderBy('nama_barang')
            ->get();

        $gudangs = Gudang::where(
            'is_active',
            true
        )
            ->orderBy('nama_gudang')
            ->get();

        return view(
            'kartu-stok.index',
            compact(
                'mutasiStoks',
                'barangs',
                'gudangs'
            )
        );
    }
}