<?php

namespace App\Http\Controllers;

use App\Models\InvoiceSupplier;
use App\Models\Supplier;
use Illuminate\Http\Request;

class UtangController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | DAFTAR UTANG SUPPLIER
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query = InvoiceSupplier::with([
            'supplier',
            'purchaseOrder',
        ])
            ->where('sisa_utang', '>', 0);


        /*
        |--------------------------------------------------------------------------
        | PENCARIAN
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where(
                    'nomor_invoice_internal',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'nomor_invoice_supplier',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereHas(
                        'supplier',
                        function ($supplierQuery) use ($search) {

                            $supplierQuery->where(
                                'nama_supplier',
                                'like',
                                "%{$search}%"
                            );
                        }
                    );
            });
        }


        /*
        |--------------------------------------------------------------------------
        | FILTER SUPPLIER
        |--------------------------------------------------------------------------
        */

        if ($request->filled('supplier_id')) {

            $query->where(
                'supplier_id',
                $request->supplier_id
            );
        }


        /*
        |--------------------------------------------------------------------------
        | FILTER STATUS
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {

            $query->where(
                'status',
                $request->status
            );
        }


        /*
        |--------------------------------------------------------------------------
        | FILTER JATUH TEMPO
        |--------------------------------------------------------------------------
        */

        if ($request->jatuh_tempo === 'lewat') {

            $query->whereDate(
                'tanggal_jatuh_tempo',
                '<',
                today()
            );

        } elseif ($request->jatuh_tempo === 'hari_ini') {

            $query->whereDate(
                'tanggal_jatuh_tempo',
                today()
            );

        } elseif ($request->jatuh_tempo === '7_hari') {

            $query
                ->whereDate(
                    'tanggal_jatuh_tempo',
                    '>=',
                    today()
                )
                ->whereDate(
                    'tanggal_jatuh_tempo',
                    '<=',
                    today()->addDays(7)
                );
        }


        /*
        |--------------------------------------------------------------------------
        | DATA UTANG
        |--------------------------------------------------------------------------
        */

        $utangs = $query
            ->orderBy('tanggal_jatuh_tempo')
            ->paginate(15)
            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | SUPPLIER UNTUK FILTER
        |--------------------------------------------------------------------------
        */

        $suppliers = Supplier::orderBy(
            'nama_supplier'
        )->get();


        /*
        |--------------------------------------------------------------------------
        | RINGKASAN
        |--------------------------------------------------------------------------
        */

        $baseOutstanding = InvoiceSupplier::query()
            ->where('sisa_utang', '>', 0);


        $totalUtang = (clone $baseOutstanding)
            ->sum('sisa_utang');


        $belumLunas = (clone $baseOutstanding)
            ->where(
                'status',
                'BELUM_LUNAS'
            )
            ->count();


        $sebagian = (clone $baseOutstanding)
            ->where(
                'status',
                'SEBAGIAN'
            )
            ->count();


        $jatuhTempo = (clone $baseOutstanding)
            ->whereDate(
                'tanggal_jatuh_tempo',
                '<',
                today()
            )
            ->sum('sisa_utang');


        return view(
            'utang.index',
            compact(
                'utangs',
                'suppliers',
                'totalUtang',
                'belumLunas',
                'sebagian',
                'jatuhTempo'
            )
        );
    }
}