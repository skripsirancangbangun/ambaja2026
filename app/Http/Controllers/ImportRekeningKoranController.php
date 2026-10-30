<?php

namespace App\Http\Controllers;

use App\Models\RekeningKasBank;
use App\Models\TransaksiBelumTeridentifikasi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportRekeningKoranController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | HALAMAN IMPORT
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $rekeningBanks =
            RekeningKasBank::query()
                ->where('jenis', 'BANK')
                ->where('is_active', true)
                ->orderBy('nama_rekening')
                ->get();


        return view(
            'transaksi-belum-teridentifikasi.import',
            compact('rekeningBanks')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PREVIEW FILE
    |--------------------------------------------------------------------------
    */

    public function preview(Request $request)
    {
        $validated =
            $request->validate([

                'rekening_kas_bank_id' => [
                    'required',
                    'exists:rekening_kas_banks,id',
                ],

                'file_rekening_koran' => [
                    'required',
                    'file',
                    'mimes:xlsx,xls,csv,txt',
                    'max:10240',
                ],
            ]);


        /*
        |--------------------------------------------------------------------------
        | VALIDASI REKENING
        |--------------------------------------------------------------------------
        */

        $rekening =
            RekeningKasBank::query()
                ->whereKey(
                    $validated['rekening_kas_bank_id']
                )
                ->where('jenis', 'BANK')
                ->where('is_active', true)
                ->first();


        if (!$rekening) {

            return back()
                ->withInput()
                ->withErrors([
                    'rekening_kas_bank_id' =>
                        'Rekening bank tidak valid atau tidak aktif.',
                ]);
        }


        $file =
            $request->file(
                'file_rekening_koran'
            );


        /*
        |--------------------------------------------------------------------------
        | BACA FILE
        |--------------------------------------------------------------------------
        */

        try {

            $extension =
                strtolower(
                    $file->getClientOriginalExtension()
                );


            /*
            |--------------------------------------------------------------------------
            | CSV / TXT
            |--------------------------------------------------------------------------
            |
            | Untuk CSV BCA kita baca manual karena export BCA membungkus
            | SATU BARIS penuh dengan tanda kutip.
            |
            */

            if (
                in_array(
                    $extension,
                    [
                        'csv',
                        'txt',
                    ],
                    true
                )
            ) {

                $rows =
                    $this->bacaCsvBank(
                        $file->getRealPath()
                    );

            }

            /*
            |--------------------------------------------------------------------------
            | XLS / XLSX
            |--------------------------------------------------------------------------
            */

            else {

                $spreadsheet =
                    IOFactory::load(
                        $file->getRealPath()
                    );


                $sheet =
                    $spreadsheet
                        ->getActiveSheet();


                $rows =
                    $sheet->toArray(
                        null,
                        true,
                        true,
                        false
                    );
            }

        } catch (\Throwable $e) {

            return back()
                ->withInput()
                ->withErrors([
                    'file_rekening_koran' =>
                        'File tidak dapat dibaca. Detail: '
                        . $e->getMessage(),
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | FILE KOSONG
        |--------------------------------------------------------------------------
        */

        if (
            empty($rows)
        ) {

            return back()
                ->withInput()
                ->withErrors([
                    'file_rekening_koran' =>
                        'File tidak memiliki data.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | AMBIL TAHUN DARI PERIODE
        |--------------------------------------------------------------------------
        */

        $tahunPeriode =
            null;


        foreach (
            $rows as $row
        ) {

            foreach (
                $row as $value
            ) {

                $text =
                    trim(
                        (string) $value
                    );


                if (
                    stripos(
                        $text,
                        'periode'
                    ) !== false
                    &&
                    preg_match(
                        '/\b\d{1,2}\/\d{1,2}\/(\d{4})\b/',
                        $text,
                        $matches
                    )
                ) {

                    $tahunPeriode =
                        (int) $matches[1];

                    break 2;
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | CARI HEADER
        |--------------------------------------------------------------------------
        */

        $headerRowIndex =
            null;


        $headers =
            [];


        foreach (
            $rows as $index => $row
        ) {

            $normalizedRow =
                [];


            foreach (
                $row as $columnIndex => $value
            ) {

                $normalizedRow[$columnIndex] =
                    $this->normalizeHeader(
                        $value
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | DETEKSI KOLOM
            |--------------------------------------------------------------------------
            */

            $adaTanggal =
                $this->findHeaderIndex(
                    $normalizedRow,
                    [
                        'tanggal',
                        'tanggal transaksi',
                        'date',
                        'transaction date',
                    ]
                ) !== null;


            $adaDebit =
                $this->findHeaderIndex(
                    $normalizedRow,
                    [
                        'debit',
                        'debet',
                        'keluar',
                        'debit amount',
                    ]
                ) !== null;


            $adaKredit =
                $this->findHeaderIndex(
                    $normalizedRow,
                    [
                        'kredit',
                        'credit',
                        'masuk',
                        'credit amount',
                    ]
                ) !== null;


            $adaJumlah =
                $this->findHeaderIndex(
                    $normalizedRow,
                    [
                        'jumlah',
                        'amount',
                        'nominal',
                        'transaction amount',
                    ]
                ) !== null;


            /*
            |--------------------------------------------------------------------------
            | FORMAT VALID
            |--------------------------------------------------------------------------
            */

            if (
                $adaTanggal
                &&
                (
                    ($adaDebit && $adaKredit)
                    ||
                    $adaJumlah
                )
            ) {

                $headerRowIndex =
                    $index;


                $headers =
                    $normalizedRow;


                break;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | HEADER TIDAK ADA
        |--------------------------------------------------------------------------
        */

        if (
            $headerRowIndex === null
        ) {

            return back()
                ->withInput()
                ->withErrors([
                    'file_rekening_koran' =>
                        'Header transaksi tidak ditemukan.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | POSISI KOLOM
        |--------------------------------------------------------------------------
        */

        $tanggalIndex =
            $this->findHeaderIndex(
                $headers,
                [
                    'tanggal',
                    'tanggal transaksi',
                    'date',
                    'transaction date',
                ]
            );


        $deskripsiIndex =
            $this->findHeaderIndex(
                $headers,
                [
                    'deskripsi',
                    'keterangan',
                    'description',
                    'uraian',
                    'remark',
                    'remarks',
                ]
            );


        $debitIndex =
            $this->findHeaderIndex(
                $headers,
                [
                    'debit',
                    'debet',
                    'keluar',
                    'debit amount',
                ]
            );


        $kreditIndex =
            $this->findHeaderIndex(
                $headers,
                [
                    'kredit',
                    'credit',
                    'masuk',
                    'credit amount',
                ]
            );


        $jumlahIndex =
            $this->findHeaderIndex(
                $headers,
                [
                    'jumlah',
                    'amount',
                    'nominal',
                    'transaction amount',
                ]
            );


        $referensiIndex =
            $this->findHeaderIndex(
                $headers,
                [
                    'referensi',
                    'reference',
                    'no referensi',
                    'nomor referensi',
                    'reference number',
                    'ref',
                ]
            );


        $cabangIndex =
            $this->findHeaderIndex(
                $headers,
                [
                    'cabang',
                    'branch',
                ]
            );


        /*
        |--------------------------------------------------------------------------
        | FORMAT
        |--------------------------------------------------------------------------
        */

        $formatDebitKredit =
            $debitIndex !== null
            &&
            $kreditIndex !== null;


        $formatJumlah =
            $jumlahIndex !== null;


        if (
            !$formatDebitKredit
            &&
            !$formatJumlah
        ) {

            return back()
                ->withInput()
                ->withErrors([
                    'file_rekening_koran' =>
                        'Format rekening koran tidak dikenali.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | DATA SETELAH HEADER
        |--------------------------------------------------------------------------
        */

        $dataRows =
            array_slice(
                $rows,
                $headerRowIndex + 1
            );


        $preview =
            [];


        /*
         * Nomor baris pada file.
         */

        $barisFile =
            $headerRowIndex + 1;


        foreach (
            $dataRows as $row
        ) {

            $barisFile++;


            /*
            |--------------------------------------------------------------------------
            | BARIS KOSONG
            |--------------------------------------------------------------------------
            */

            if (
                $this->rowKosong(
                    $row
                )
            ) {

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | ISI BARIS
            |--------------------------------------------------------------------------
            */

            $isiBaris =
                strtolower(
                    trim(
                        implode(
                            ' ',
                            array_map(
                                fn ($value) =>
                                    trim(
                                        (string) $value
                                    ),
                                $row
                            )
                        )
                    )
                );


            /*
            |--------------------------------------------------------------------------
            | SKIP FOOTER
            |--------------------------------------------------------------------------
            */

            if (
                str_contains(
                    $isiBaris,
                    'saldo awal'
                )
                ||
                str_contains(
                    $isiBaris,
                    'mutasi debet'
                )
                ||
                str_contains(
                    $isiBaris,
                    'mutasi debit'
                )
                ||
                str_contains(
                    $isiBaris,
                    'mutasi kredit'
                )
                ||
                str_contains(
                    $isiBaris,
                    'saldo akhir'
                )
            ) {

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | DATA DASAR
            |--------------------------------------------------------------------------
            */

            $tanggalRaw =
                $tanggalIndex !== null
                    ? (
                        $row[$tanggalIndex]
                        ?? null
                    )
                    : null;


            $deskripsi =
                $deskripsiIndex !== null
                    ? trim(
                        (string) (
                            $row[$deskripsiIndex]
                            ?? ''
                        )
                    )
                    : '';


            $referensi =
                $referensiIndex !== null
                    ? trim(
                        (string) (
                            $row[$referensiIndex]
                            ?? ''
                        )
                    )
                    : '';


            $cabang =
                $cabangIndex !== null
                    ? trim(
                        (string) (
                            $row[$cabangIndex]
                            ?? ''
                        )
                    )
                    : '';


            /*
            |--------------------------------------------------------------------------
            | CABANG SEBAGAI REFERENSI
            |--------------------------------------------------------------------------
            */

            if (
                $referensi === ''
                &&
                $cabang !== ''
            ) {

                $referensi =
                    $cabang;
            }


            /*
            |--------------------------------------------------------------------------
            | NAMA PENGIRIM / PENERIMA
            |--------------------------------------------------------------------------
            */

            $namaPengirimPenerima =
                $this->ambilNamaPengirimPenerima(
                    $deskripsi
                );


            /*
            |--------------------------------------------------------------------------
            | PARSE TANGGAL
            |--------------------------------------------------------------------------
            */

            try {

                $tanggal =
                    $this->parseTanggal(
                        $tanggalRaw,
                        $tahunPeriode
                    );

            } catch (\Throwable $e) {

                $preview[] =
                    $this->buatBarisError(
                        baris: $barisFile,
                        deskripsi: $deskripsi,
                        referensi: $referensi,
                        nama: $namaPengirimPenerima,
                        pesan: 'Format tanggal tidak valid.'
                    );


                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | FORMAT DEBIT / KREDIT
            |--------------------------------------------------------------------------
            */

            if (
                $formatDebitKredit
            ) {

                $debit =
                    $this->parseNominal(
                        $row[$debitIndex]
                        ?? null
                    );


                $kredit =
                    $this->parseNominal(
                        $row[$kreditIndex]
                        ?? null
                    );


                /*
                 * Dua-duanya terisi.
                 */

                if (
                    $debit > 0
                    &&
                    $kredit > 0
                ) {

                    $preview[] =
                        $this->buatBarisError(
                            baris: $barisFile,
                            deskripsi: $deskripsi,
                            referensi: $referensi,
                            nama: $namaPengirimPenerima,
                            pesan: 'Debit dan Kredit tidak boleh terisi bersamaan.',
                            tanggal: $tanggal
                        );


                    continue;
                }


                /*
                 * Tidak ada nominal.
                 */

                if (
                    $debit <= 0
                    &&
                    $kredit <= 0
                ) {

                    $preview[] =
                        $this->buatBarisError(
                            baris: $barisFile,
                            deskripsi: $deskripsi,
                            referensi: $referensi,
                            nama: $namaPengirimPenerima,
                            pesan: 'Nominal Debit/Kredit tidak ditemukan.',
                            tanggal: $tanggal
                        );


                    continue;
                }


                /*
                 * Debit bank = uang keluar.
                 */

                if (
                    $debit > 0
                ) {

                    $jenis =
                        'KELUAR';


                    $jumlah =
                        $debit;

                } else {

                    /*
                     * Kredit bank = uang masuk.
                     */

                    $jenis =
                        'MASUK';


                    $jumlah =
                        $kredit;
                }

            }

            /*
            |--------------------------------------------------------------------------
            | FORMAT JUMLAH + DB / CR
            |--------------------------------------------------------------------------
            */

            else {

                $jumlahRaw =
                    trim(
                        (string) (
                            $row[$jumlahIndex]
                            ?? ''
                        )
                    );


                if (
                    $jumlahRaw === ''
                ) {

                    $preview[] =
                        $this->buatBarisError(
                            baris: $barisFile,
                            deskripsi: $deskripsi,
                            referensi: $referensi,
                            nama: $namaPengirimPenerima,
                            pesan: 'Kolom Jumlah kosong.',
                            tanggal: $tanggal
                        );


                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | DETEKSI DB / CR
                |--------------------------------------------------------------------------
                */

                if (
                    preg_match(
                        '/\bDB\b/i',
                        $jumlahRaw
                    )
                ) {

                    $jenis =
                        'KELUAR';

                } elseif (
                    preg_match(
                        '/\bCR\b/i',
                        $jumlahRaw
                    )
                ) {

                    $jenis =
                        'MASUK';

                } else {

                    $preview[] =
                        $this->buatBarisError(
                            baris: $barisFile,
                            deskripsi: $deskripsi,
                            referensi: $referensi,
                            nama: $namaPengirimPenerima,
                            pesan: 'Kode DB/CR pada kolom Jumlah tidak ditemukan.',
                            tanggal: $tanggal
                        );


                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | HAPUS DB / CR
                |--------------------------------------------------------------------------
                */

                $nominalBersih =
                    preg_replace(
                        '/\b(DB|CR)\b/i',
                        '',
                        $jumlahRaw
                    );


                $jumlah =
                    $this->parseNominal(
                        $nominalBersih
                    );


                if (
                    $jumlah <= 0
                ) {

                    $preview[] =
                        $this->buatBarisError(
                            baris: $barisFile,
                            deskripsi: $deskripsi,
                            referensi: $referensi,
                            nama: $namaPengirimPenerima,
                            pesan: 'Nominal transaksi tidak valid.',
                            tanggal: $tanggal
                        );


                    continue;
                }
            }


            /*
            |--------------------------------------------------------------------------
            | IMPORT HASH
            |--------------------------------------------------------------------------
            */

            $hash =
                $this->buatImportHash(

                    rekeningId:
                        (int) $rekening->id,

                    tanggal:
                        $tanggal,

                    jenis:
                        $jenis,

                    jumlah:
                        $jumlah,

                    referensi:
                        $referensi,

                    deskripsi:
                        $deskripsi
                );


            /*
            |--------------------------------------------------------------------------
            | CEK DUPLIKAT DATABASE
            |--------------------------------------------------------------------------
            */

            $sudahAda =
                TransaksiBelumTeridentifikasi::query()
                    ->where(
                        'import_hash',
                        $hash
                    )
                    ->exists();


            /*
            |--------------------------------------------------------------------------
            | PREVIEW
            |--------------------------------------------------------------------------
            */

            $preview[] = [

                'baris' =>
                    $barisFile,

                'tanggal' =>
                    $tanggal,

                'jenis' =>
                    $jenis,

                'jumlah' =>
                    $jumlah,

                'nama_pengirim_penerima' =>
                    $namaPengirimPenerima,

                'deskripsi_bank' =>
                    $deskripsi,

                'nomor_referensi_bank' =>
                    $referensi,

                'status_import' =>
                    $sudahAda
                        ? 'DUPLIKAT'
                        : 'SIAP',

                'pesan' =>
                    $sudahAda
                        ? 'Transaksi sudah pernah diimport.'
                        : null,

                'import_hash' =>
                    $hash,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | TIDAK ADA TRANSAKSI
        |--------------------------------------------------------------------------
        */

        if (
            count($preview) === 0
        ) {

            return back()
                ->withInput()
                ->withErrors([
                    'file_rekening_koran' =>
                        'Tidak ditemukan transaksi pada file.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | BATCH
        |--------------------------------------------------------------------------
        */

        $batch =
            'RK-'
            . now()->format(
                'YmdHis'
            )
            . '-'
            . strtoupper(
                Str::random(6)
            );


        /*
        |--------------------------------------------------------------------------
        | SESSION
        |--------------------------------------------------------------------------
        */

        session([

            'rekening_koran_import' => [

                'batch' =>
                    $batch,

                'rekening_kas_bank_id' =>
                    $rekening->id,

                'nama_file' =>
                    $file->getClientOriginalName(),

                'data' =>
                    $preview,
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | PREVIEW
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route(
                'rekening-koran-import.preview'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | TAMPILKAN PREVIEW
    |--------------------------------------------------------------------------
    */

    public function showPreview()
    {
        $import =
            session(
                'rekening_koran_import'
            );


        if (!$import) {

            return redirect()
                ->route(
                    'rekening-koran-import.create'
                )
                ->withErrors([
                    'file_rekening_koran' =>
                        'Data preview tidak ditemukan. Silakan upload file kembali.',
                ]);
        }


        $rekening =
            RekeningKasBank::find(
                $import['rekening_kas_bank_id']
            );


        if (!$rekening) {

            session()->forget(
                'rekening_koran_import'
            );


            return redirect()
                ->route(
                    'rekening-koran-import.create'
                )
                ->withErrors([
                    'rekening_kas_bank_id' =>
                        'Rekening bank tidak ditemukan.',
                ]);
        }


        $data =
            collect(
                $import['data']
            );


        $jumlahSiap =
            $data
                ->where(
                    'status_import',
                    'SIAP'
                )
                ->count();


        $jumlahDuplikat =
            $data
                ->where(
                    'status_import',
                    'DUPLIKAT'
                )
                ->count();


        $jumlahError =
            $data
                ->where(
                    'status_import',
                    'ERROR'
                )
                ->count();


        return view(
            'transaksi-belum-teridentifikasi.import-preview',
            compact(
                'import',
                'rekening',
                'data',
                'jumlahSiap',
                'jumlahDuplikat',
                'jumlahError'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SIMPAN IMPORT
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $import =
            session(
                'rekening_koran_import'
            );


        if (!$import) {

            return redirect()
                ->route(
                    'rekening-koran-import.create'
                )
                ->withErrors([
                    'file_rekening_koran' =>
                        'Session import sudah tidak tersedia. Silakan upload ulang.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | VALIDASI REKENING
        |--------------------------------------------------------------------------
        */

        $rekening =
            RekeningKasBank::query()
                ->whereKey(
                    $import['rekening_kas_bank_id']
                )
                ->where('jenis', 'BANK')
                ->where('is_active', true)
                ->first();


        if (!$rekening) {

            return redirect()
                ->route(
                    'rekening-koran-import.create'
                )
                ->withErrors([
                    'rekening_kas_bank_id' =>
                        'Rekening bank tidak valid atau tidak aktif.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | DATA SIAP
        |--------------------------------------------------------------------------
        */

        $data =
            collect(
                $import['data']
            );


        $dataSiap =
            $data->where(
                'status_import',
                'SIAP'
            );


        if (
            $dataSiap->isEmpty()
        ) {

            return back()
                ->withErrors([
                    'import' =>
                        'Tidak ada transaksi yang dapat diimport.',
                ]);
        }


        $berhasil =
            0;


        $dilewati =
            0;


        /*
        |--------------------------------------------------------------------------
        | SIMPAN DATABASE
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use (
                $dataSiap,
                $rekening,
                $import,
                &$berhasil,
                &$dilewati
            ) {

                foreach (
                    $dataSiap as $row
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | CEK ULANG DUPLIKAT
                    |--------------------------------------------------------------------------
                    */

                    $sudahAda =
                        TransaksiBelumTeridentifikasi::query()
                            ->where(
                                'import_hash',
                                $row['import_hash']
                            )
                            ->exists();


                    if (
                        $sudahAda
                    ) {

                        $dilewati++;

                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | INSERT
                    |--------------------------------------------------------------------------
                    */

                    TransaksiBelumTeridentifikasi::create([

                        'tanggal' =>
                            $row['tanggal'],

                        'rekening_kas_bank_id' =>
                            $rekening->id,

                        'jenis' =>
                            $row['jenis'],

                        'jumlah' =>
                            $row['jumlah'],

                        'nama_pengirim_penerima' =>
                            $row[
                                'nama_pengirim_penerima'
                            ] ?? null,

                        'nomor_referensi_bank' =>
                            $row[
                                'nomor_referensi_bank'
                            ] ?: null,

                        'deskripsi_bank' =>
                            $row[
                                'deskripsi_bank'
                            ] ?: null,

                        'import_batch' =>
                            $import['batch'],

                        'import_hash' =>
                            $row['import_hash'],

                        'nama_file_import' =>
                            $import['nama_file'],

                        'status' =>
                            'BELUM_TERIDENTIFIKASI',

                        'created_by' =>
                            auth()->id(),
                    ]);


                    $berhasil++;
                }
            }
        );


        /*
        |--------------------------------------------------------------------------
        | HAPUS SESSION
        |--------------------------------------------------------------------------
        */

        session()->forget(
            'rekening_koran_import'
        );


        /*
        |--------------------------------------------------------------------------
        | SELESAI
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route(
                'transaksi-belum-teridentifikasi.index'
            )
            ->with(
                'success',
                "{$berhasil} transaksi rekening koran berhasil diimport. {$dilewati} transaksi dilewati karena duplikat."
            );
    }


    /*
    |--------------------------------------------------------------------------
    | BATAL IMPORT
    |--------------------------------------------------------------------------
    */

    public function cancel()
    {
        session()->forget(
            'rekening_koran_import'
        );


        return redirect()
            ->route(
                'transaksi-belum-teridentifikasi.index'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | BACA CSV BANK
    |--------------------------------------------------------------------------
    |
    | Format BCA:
    |
    | "Tanggal,""Keterangan"",""Cabang"",""Jumlah"",""Saldo"""
    |
    | Ini BUKAN CSV standar biasa karena satu baris penuh masih
    | dibungkus quote.
    |
    */

    private function bacaCsvBank(
        string $path
    ): array {

        $content =
            file_get_contents(
                $path
            );


        if (
            $content === false
        ) {

            throw new \RuntimeException(
                'File CSV tidak dapat dibuka.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | HAPUS UTF-8 BOM
        |--------------------------------------------------------------------------
        */

        $content =
            preg_replace(
                '/^\xEF\xBB\xBF/',
                '',
                $content
            );


        /*
        |--------------------------------------------------------------------------
        | NORMALISASI LINE BREAK
        |--------------------------------------------------------------------------
        */

        $content =
            str_replace(
                [
                    "\r\n",
                    "\r",
                ],
                "\n",
                $content
            );


        $lines =
            explode(
                "\n",
                $content
            );


        $rows =
            [];


        foreach (
            $lines as $line
        ) {

            /*
            |--------------------------------------------------------------------------
            | JANGAN HILANGKAN SPASI DALAM DESKRIPSI
            |--------------------------------------------------------------------------
            */

            $line =
                trim(
                    $line,
                    "\r\n"
                );


            if (
                trim($line) === ''
            ) {

                $rows[] =
                    [''];

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | FORMAT KHUSUS BCA
            |--------------------------------------------------------------------------
            |
            | Contoh:
            |
            | "01/08/2026,""BI-FAST..."",""0000"",""22,735,000.00 CR""..."
            |
            */

            if (
                str_starts_with(
                    $line,
                    '"'
                )
                &&
                str_ends_with(
                    $line,
                    '"'
                )
            ) {

                /*
                |--------------------------------------------------------------------------
                | BUANG OUTER QUOTE
                |--------------------------------------------------------------------------
                */

                $line =
                    substr(
                        $line,
                        1,
                        -1
                    );


                /*
                |--------------------------------------------------------------------------
                | "" MENJADI "
                |--------------------------------------------------------------------------
                */

                $line =
                    str_replace(
                        '""',
                        '"',
                        $line
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | PARSE CSV SETELAH DINORMALISASI
            |--------------------------------------------------------------------------
            */

            $row =
                str_getcsv(
                    $line,
                    ',',
                    '"',
                    '\\'
                );


            /*
            |--------------------------------------------------------------------------
            | BERSIHKAN BOM DI CELL PERTAMA
            |--------------------------------------------------------------------------
            */

            if (
                isset(
                    $row[0]
                )
            ) {

                $row[0] =
                    preg_replace(
                        '/^\xEF\xBB\xBF/',
                        '',
                        (string) $row[0]
                    );
            }


            $rows[] =
                $row;
        }


        return $rows;
    }


    /*
    |--------------------------------------------------------------------------
    | BUAT BARIS ERROR
    |--------------------------------------------------------------------------
    */

    private function buatBarisError(
        int $baris,
        string $deskripsi,
        string $referensi,
        ?string $nama,
        string $pesan,
        ?string $tanggal = null
    ): array {

        return [

            'baris' =>
                $baris,

            'tanggal' =>
                $tanggal,

            'jenis' =>
                null,

            'jumlah' =>
                0,

            'nama_pengirim_penerima' =>
                $nama,

            'deskripsi_bank' =>
                $deskripsi,

            'nomor_referensi_bank' =>
                $referensi,

            'status_import' =>
                'ERROR',

            'pesan' =>
                $pesan,

            'import_hash' =>
                null,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | AMBIL NAMA PENGIRIM / PENERIMA
    |--------------------------------------------------------------------------
    */

    private function ambilNamaPengirimPenerima(
        ?string $deskripsi
    ): ?string {

        if (
            !$deskripsi
        ) {

            return null;
        }


        $deskripsi =
            trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    $deskripsi
                )
            );


        if (
            $deskripsi === ''
        ) {

            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | BI-FAST
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/BI[\-\s]?FAST.*?TRANSFER\s+(?:DR|FROM|TO)\s+\d+\s+(.+)$/i',
                $deskripsi,
                $match
            )
        ) {

            return $this->bersihkanNamaBank(
                $match[1]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | TRANSFER DR/FROM/TO
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/TRANSFER\s+(?:DR|FROM|TO)\s+(?:\d+\s+)?(.+)$/i',
                $deskripsi,
                $match
            )
        ) {

            return $this->bersihkanNamaBank(
                $match[1]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | BCA TRSF E-BANKING
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/TRSF\s+E[\-\s]?BANKING.*?([A-Z][A-Z\.\'\s]{3,})$/i',
                $deskripsi,
                $match
            )
        ) {

            $nama =
                $this->ambilNamaDariAkhir(
                    $match[1]
                );


            if (
                $nama
            ) {

                return $nama;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | TRANSFER + KODE BANK
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/(?:TRSF|TRANSFER).*?\s\d{3}\s+([A-Z][A-Z\s\.\']{3,})$/i',
                $deskripsi,
                $match
            )
        ) {

            return $this->bersihkanNamaBank(
                $match[1]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | FALLBACK
        |--------------------------------------------------------------------------
        */

        return $this->ambilNamaDariAkhir(
            $deskripsi
        );
    }


    /*
    |--------------------------------------------------------------------------
    | NAMA DARI AKHIR
    |--------------------------------------------------------------------------
    */

    private function ambilNamaDariAkhir(
        string $text
    ): ?string {

        $text =
            trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    $text
                )
            );


        if (
            $text === ''
        ) {

            return null;
        }


        if (
            preg_match(
                '/([A-Z][A-Z\.\'\s]{3,})$/i',
                $text,
                $match
            )
        ) {

            $nama =
                $this->bersihkanNamaBank(
                    $match[1]
                );


            if (
                !$nama
                ||
                $this->namaBankTidakValid(
                    $nama
                )
            ) {

                return null;
            }


            return $nama;
        }


        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | BERSIHKAN NAMA BANK
    |--------------------------------------------------------------------------
    */

    private function bersihkanNamaBank(
        ?string $nama
    ): ?string {

        if (
            !$nama
        ) {

            return null;
        }


        $nama =
            trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    $nama
                )
            );


        $nama =
            trim(
                $nama,
                " \t\n\r\0\x0B-_/.,:"
            );


        $nama =
            preg_replace(
                '/^(?:DB|CR|DR|FROM|TO)\s+/i',
                '',
                $nama
            );


        $nama =
            trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    $nama
                )
            );


        if (
            $nama === ''
            ||
            mb_strlen(
                $nama
            ) < 3
        ) {

            return null;
        }


        /*
         * Tidak boleh hanya angka.
         */

        if (
            preg_match(
                '/^[0-9\s\.\,\-]+$/',
                $nama
            )
        ) {

            return null;
        }


        /*
         * Nama kandidat jangan mengandung angka.
         */

        if (
            preg_match(
                '/\d/',
                $nama
            )
        ) {

            return null;
        }


        $nama =
            strtoupper(
                $nama
            );


        if (
            $this->namaBankTidakValid(
                $nama
            )
        ) {

            return null;
        }


        return $nama;
    }


    /*
    |--------------------------------------------------------------------------
    | BLACKLIST NAMA
    |--------------------------------------------------------------------------
    */

    private function namaBankTidakValid(
        string $nama
    ): bool {

        $nama =
            strtoupper(
                trim(
                    preg_replace(
                        '/\s+/',
                        ' ',
                        $nama
                    )
                )
            );


        $blacklist = [

            'TRANSFER',
            'TRSF',
            'BI FAST',
            'BIFAST',
            'E BANKING',
            'EBANKING',
            'MOBILE BANKING',
            'INTERNET BANKING',
            'PAYMENT',
            'PEMBAYARAN',
            'DEBIT',
            'DEBET',
            'CREDIT',
            'KREDIT',
            'DB',
            'CR',
            'DR',
            'SALDO',
            'SALDO AWAL',
            'SALDO AKHIR',
            'MUTASI',
            'MUTASI DEBET',
            'MUTASI DEBIT',
            'MUTASI KREDIT',
            'CABANG',

        ];


        return in_array(
            $nama,
            $blacklist,
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALISASI HEADER
    |--------------------------------------------------------------------------
    */

    private function normalizeHeader(
        $value
    ): string {

        $value =
            (string) (
                $value
                ?? ''
            );


        /*
         * BOM.
         */

        $value =
            preg_replace(
                '/^\xEF\xBB\xBF/',
                '',
                $value
            );


        /*
         * Non-breaking space dan karakter tak terlihat.
         */

        $value =
            str_replace(
                [
                    "\xC2\xA0",
                    "\r",
                    "\n",
                    "\t",
                ],
                ' ',
                $value
            );


        $value =
            Str::lower(
                $value
            );


        $value =
            str_replace(
                [
                    '_',
                    '-',
                    '"',
                    "'",
                    ':',
                    ';',
                ],
                ' ',
                $value
            );


        $value =
            preg_replace(
                '/\s+/u',
                ' ',
                $value
            );


        return trim(
            $value
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CARI INDEX HEADER
    |--------------------------------------------------------------------------
    */

    private function findHeaderIndex(
        array $headers,
        array $candidates
    ): ?int {

        $normalizedCandidates =
            array_map(
                fn ($candidate) =>
                    $this->normalizeHeader(
                        $candidate
                    ),
                $candidates
            );


        foreach (
            $headers as $index => $header
        ) {

            $header =
                $this->normalizeHeader(
                    $header
                );


            if (
                $header === ''
            ) {

                continue;
            }


            /*
             * Exact match lebih dulu.
             */

            if (
                in_array(
                    $header,
                    $normalizedCandidates,
                    true
                )
            ) {

                return $index;
            }


            /*
             * Flexible match.
             */

            foreach (
                $normalizedCandidates as $candidate
            ) {

                if (
                    $candidate === ''
                ) {

                    continue;
                }


                if (
                    str_contains(
                        $header,
                        $candidate
                    )
                ) {

                    return $index;
                }
            }
        }


        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | BARIS KOSONG
    |--------------------------------------------------------------------------
    */

    private function rowKosong(
        array $row
    ): bool {

        foreach (
            $row as $value
        ) {

            if (
                $value !== null
                &&
                trim(
                    (string) $value
                ) !== ''
            ) {

                return false;
            }
        }


        return true;
    }


    /*
    |--------------------------------------------------------------------------
    | PARSE TANGGAL
    |--------------------------------------------------------------------------
    */

    private function parseTanggal(
        $value,
        ?int $tahunPeriode = null
    ): string {

        if (
            $value === null
            ||
            trim(
                (string) $value
            ) === ''
        ) {

            throw new \Exception(
                'Tanggal kosong.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | EXCEL SERIAL DATE
        |--------------------------------------------------------------------------
        */

        if (
            is_numeric(
                $value
            )
        ) {

            return Carbon::instance(
                ExcelDate::excelToDateTimeObject(
                    $value
                )
            )
                ->format(
                    'Y-m-d'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | STRING
        |--------------------------------------------------------------------------
        */

        $value =
            trim(
                (string) $value
            );


        $value =
            trim(
                $value,
                "\"'"
            );


        /*
        |--------------------------------------------------------------------------
        | FORMAT LENGKAP
        |--------------------------------------------------------------------------
        */

        $formats = [

            'd/m/Y',
            'd-m-Y',
            'Y-m-d',
            'd/m/y',
            'd-m-y',
            'm/d/Y',

        ];


        foreach (
            $formats as $format
        ) {

            try {

                $tanggal =
                    Carbon::createFromFormat(
                        '!' . $format,
                        $value
                    );


                if (
                    $tanggal
                ) {

                    return $tanggal
                        ->format(
                            'Y-m-d'
                        );
                }

            } catch (\Throwable $e) {

                //
            }
        }


        /*
        |--------------------------------------------------------------------------
        | d/m TANPA TAHUN
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/^(\d{1,2})\/(\d{1,2})$/',
                $value,
                $matches
            )
        ) {

            if (
                !$tahunPeriode
            ) {

                throw new \Exception(
                    'Tahun periode rekening koran tidak ditemukan.'
                );
            }


            $hari =
                (int) $matches[1];


            $bulan =
                (int) $matches[2];


            if (
                !checkdate(
                    $bulan,
                    $hari,
                    $tahunPeriode
                )
            ) {

                throw new \Exception(
                    'Tanggal transaksi tidak valid.'
                );
            }


            return Carbon::create(
                $tahunPeriode,
                $bulan,
                $hari
            )
                ->format(
                    'Y-m-d'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | d-m TANPA TAHUN
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/^(\d{1,2})-(\d{1,2})$/',
                $value,
                $matches
            )
        ) {

            if (
                !$tahunPeriode
            ) {

                throw new \Exception(
                    'Tahun periode rekening koran tidak ditemukan.'
                );
            }


            $hari =
                (int) $matches[1];


            $bulan =
                (int) $matches[2];


            if (
                !checkdate(
                    $bulan,
                    $hari,
                    $tahunPeriode
                )
            ) {

                throw new \Exception(
                    'Tanggal transaksi tidak valid.'
                );
            }


            return Carbon::create(
                $tahunPeriode,
                $bulan,
                $hari
            )
                ->format(
                    'Y-m-d'
                );
        }


        throw new \Exception(
            'Format tanggal tidak dikenali.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PARSE NOMINAL
    |--------------------------------------------------------------------------
    */

    private function parseNominal($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        // Spreadsheet numeric cells are already numeric; do not reinterpret
        // them using locale-specific thousands separators.
        if (is_int($value) || is_float($value)) {
            return round(abs((float) $value), 2);
        }

        $value = trim((string) $value);
        if ($value === '' || $value === '-') {
            return 0.0;
        }

        // Remove common bank debit/credit markers and currency text.
        $value = preg_replace('/\b(DB|CR)\b/i', '', $value) ?? '';
        $value = preg_replace('/[^\d,.\-]/u', '', $value) ?? '';
        $value = trim($value);

        if ($value === '' || $value === '-') {
            return 0.0;
        }

        // A minus sign is only valid at the beginning. The importer stores
        // the amount as an absolute value and derives direction separately.
        if (substr_count($value, '-') > 1 || (str_contains($value, '-') && !str_starts_with($value, '-'))) {
            return 0.0;
        }
        $value = ltrim($value, '-');
        if ($value === '') {
            return 0.0;
        }

        $hasComma = str_contains($value, ',');
        $hasDot = str_contains($value, '.');

        if ($hasComma && $hasDot) {
            // The last separator is the decimal separator:
            // 1,234,567.89 (international) or 1.234.567,89 (Indonesian).
            if (strrpos($value, '.') > strrpos($value, ',')) {
                $value = str_replace(',', '', $value);
            } else {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            }
        } elseif ($hasComma) {
            // Commas in complete 3-digit groups are thousands separators.
            if (preg_match('/^\d{1,3}(,\d{3})+$/', $value)) {
                $value = str_replace(',', '', $value);
            } elseif (preg_match('/^\d+,\d{1,2}$/', $value)) {
                $value = str_replace(',', '.', $value);
            } else {
                // Ambiguous/invalid currency precision must not silently
                // become a materially different amount.
                return 0.0;
            }
        } elseif ($hasDot) {
            // Treat 5.000 and 1.234.567 as Indonesian-style grouping, not
            // decimal numbers; native Excel numeric cells are handled above.
            if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
                $value = str_replace('.', '', $value);
            } elseif (preg_match('/^\d+(\.\d{1,2})?$/', $value)) {
                // Decimal point with up to two fractional digits.
            } else {
                return 0.0;
            }
        } elseif (!preg_match('/^\d+$/', $value)) {
            return 0.0;
        }

        if (!is_numeric($value)) {
            return 0.0;
        }

        return round(abs((float) $value), 2);
    }


    /*
    |--------------------------------------------------------------------------
    | HASH DUPLIKAT
    |--------------------------------------------------------------------------
    */

    private function buatImportHash(
        int $rekeningId,
        string $tanggal,
        string $jenis,
        float $jumlah,
        string $referensi,
        string $deskripsi
    ): string {

        $data =
            implode(
                '|',
                [

                    $rekeningId,

                    $tanggal,

                    strtoupper(
                        trim(
                            $jenis
                        )
                    ),

                    number_format(
                        $jumlah,
                        2,
                        '.',
                        ''
                    ),

                    Str::lower(
                        trim(
                            $referensi
                        )
                    ),

                    Str::lower(
                        preg_replace(
                            '/\s+/',
                            ' ',
                            trim(
                                $deskripsi
                            )
                        )
                    ),
                ]
            );


        return hash(
            'sha256',
            $data
        );
    }
}