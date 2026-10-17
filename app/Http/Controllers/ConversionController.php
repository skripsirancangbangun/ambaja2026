<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Conversion;
use App\Models\Gudang;
use App\Models\StokGudang;
use App\Services\ConversionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ConversionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | DAFTAR KONVERSI
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $conversions = Conversion::with([
            'gudang',
            'creator',
            'poster',
            'items.barang.satuan',
        ])
            ->latest('tanggal')
            ->latest('id')
            ->paginate(15);

        return view(
            'conversions.index',
            compact('conversions')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FORM KONVERSI
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        /*
        |--------------------------------------------------------------------------
        | GUDANG
        |--------------------------------------------------------------------------
        */

        $gudangs = Gudang::query()
            ->orderBy('nama_gudang')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | BARANG
        |--------------------------------------------------------------------------
        |
        | Satuan ikut diambil karena akan digunakan
        | pada tampilan form.
        |
        */

        $barangs = Barang::with('satuan')
            ->orderBy('nama_barang')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | STOK
        |--------------------------------------------------------------------------
        |
        | Data stok digunakan untuk membantu menampilkan
        | stok tersedia berdasarkan barang + gudang.
        |
        */

        $stokGudangs = StokGudang::query()
            ->get([
                'barang_id',
                'gudang_id',
                'qty',
                'harga_rata_rata',
                'nilai_persediaan',
            ]);


        /*
        |--------------------------------------------------------------------------
        | TANGGAL DEFAULT
        |--------------------------------------------------------------------------
        */

        $tanggalDefault = now()->format('Y-m-d');


        return view(
            'conversions.create',
            compact(
                'gudangs',
                'barangs',
                'stokGudangs',
                'tanggalDefault'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SIMPAN & POSTING KONVERSI
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        ConversionService $conversionService
    ) {
        /*
        |--------------------------------------------------------------------------
        | VALIDASI FORM
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'gudang_id' => [
                'required',
                'integer',
                'exists:gudangs,id',
            ],

            'tanggal' => [
                'required',
                'date',
            ],

            'barang_input_id' => [
                'required',
                'integer',
                'exists:barangs,id',
            ],

            'qty_input' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'outputs' => [
                'required',
                'array',
                'min:1',
            ],

            'outputs.*.barang_id' => [
                'required',
                'integer',
                'exists:barangs,id',
            ],

            'outputs.*.qty' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'outputs.*.panjang' => [
                'nullable',
                'numeric',
                'gt:0',
            ],

            'outputs.*.berat' => [
                'nullable',
                'numeric',
                'gt:0',
            ],

            'keterangan' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | SIAPKAN OUTPUT
        |--------------------------------------------------------------------------
        |
        | HPP TIDAK DIAMBIL DARI FORM.
        |
        | HPP output akan dihitung oleh ConversionService
        | berdasarkan nilai barang input.
        |
        */

        $outputs = [];

        foreach (
            $validated['outputs']
            as $output
        ) {
            $outputs[] = [
                'barang_id' =>
                    (int) $output['barang_id'],

                'qty' =>
                    (float) $output['qty'],

                'panjang' =>
                    isset($output['panjang'])
                        && $output['panjang'] !== ''
                        ? (float) $output['panjang']
                        : null,

                'berat' =>
                    isset($output['berat'])
                        && $output['berat'] !== ''
                        ? (float) $output['berat']
                        : null,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | PROSES KONVERSI
        |--------------------------------------------------------------------------
        |
        | Service:
        |
        | 1. Mengecek stok input
        | 2. Mengambil HPP moving average
        | 3. Mengurangi stok input
        | 4. Menghitung nilai output
        | 5. Menghitung HPP output otomatis
        | 6. Menambah stok output
        | 7. Mencatat mutasi stok
        |
        */

        try {

            $conversion =
                $conversionService->createAndPost(
                    gudangId:
                        (int) $validated['gudang_id'],

                    tanggal:
                        $validated['tanggal'],

                    barangInputId:
                        (int) $validated['barang_input_id'],

                    qtyInput:
                        (float) $validated['qty_input'],

                    outputs:
                        $outputs,

                    /*
                     * Waste sengaja tidak digunakan
                     * pada versi awal.
                     */
                    wasteQty:
                        null,

                    createdBy:
                        auth()->id(),

                    keterangan:
                        $validated['keterangan']
                            ?? null
                );


        } catch (
            RuntimeException $e
        ) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );


        } catch (
            \Throwable $e
        ) {

            /*
            |--------------------------------------------------------------------------
            | FALLBACK ERROR
            |--------------------------------------------------------------------------
            |
            | Error teknis tidak ditampilkan secara detail
            | kepada user.
            |
            */

            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Konversi barang gagal diproses. '
                    . 'Silakan periksa data dan coba kembali.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | BERHASIL
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route(
                'conversions.show',
                $conversion
            )
            ->with(
                'success',
                'Konversi barang '
                . $conversion->nomor_konversi
                . ' berhasil diproses.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | DETAIL KONVERSI
    |--------------------------------------------------------------------------
    */

    public function show(
        Conversion $conversion
    ) {
        $conversion->load([
            'gudang',
            'creator',
            'poster',
            'items.barang.satuan',
        ]);


        return view(
            'conversions.show',
            compact('conversion')
        );
    }
}