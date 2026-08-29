<?php

namespace App\Console\Commands;

use App\Models\TransaksiBelumTeridentifikasi;
use Illuminate\Console\Command;

class BackfillNamaPengirimPenerima extends Command
{
    /*
    |--------------------------------------------------------------------------
    | SIGNATURE
    |--------------------------------------------------------------------------
    */

    protected $signature =
        'rekening-koran:backfill-nama';


    /*
    |--------------------------------------------------------------------------
    | DESCRIPTION
    |--------------------------------------------------------------------------
    */

    protected $description =
        'Mengisi nama pengirim/penerima transaksi rekening koran lama berdasarkan deskripsi bank';


    /*
    |--------------------------------------------------------------------------
    | HANDLE
    |--------------------------------------------------------------------------
    */

    public function handle(): int
    {
        $this->info(
            'Memulai pembacaan nama pengirim/penerima...'
        );


        /*
        |--------------------------------------------------------------------------
        | AMBIL TRANSAKSI YANG NAMANYA MASIH KOSONG
        |--------------------------------------------------------------------------
        */

        $query =
            TransaksiBelumTeridentifikasi::query()
                ->where(function ($query) {

                    $query
                        ->whereNull(
                            'nama_pengirim_penerima'
                        )
                        ->orWhere(
                            'nama_pengirim_penerima',
                            ''
                        );
                })
                ->whereNotNull(
                    'deskripsi_bank'
                )
                ->where(
                    'deskripsi_bank',
                    '!=',
                    ''
                );


        $total =
            $query->count();


        if ($total === 0) {

            $this->info(
                'Tidak ada transaksi yang perlu diperbarui.'
            );

            return self::SUCCESS;
        }


        $this->info(
            "Ditemukan {$total} transaksi."
        );


        /*
        |--------------------------------------------------------------------------
        | PROGRESS BAR
        |--------------------------------------------------------------------------
        */

        $progressBar =
            $this->output
                ->createProgressBar(
                    $total
                );


        $progressBar->start();


        /*
        |--------------------------------------------------------------------------
        | COUNTER
        |--------------------------------------------------------------------------
        */

        $berhasil = 0;

        $tidakTerdeteksi = 0;

        $gagal = 0;


        /*
        |--------------------------------------------------------------------------
        | PROSES PER CHUNK
        |--------------------------------------------------------------------------
        */

        $query
            ->orderBy('id')
            ->chunkById(
                100,
                function ($transaksis) use (
                    &$berhasil,
                    &$tidakTerdeteksi,
                    &$gagal,
                    $progressBar
                ) {

                    foreach (
                        $transaksis
                        as $transaksi
                    ) {

                        try {

                            /*
                            |--------------------------------------------------------------------------
                            | AMBIL NAMA
                            |--------------------------------------------------------------------------
                            */

                            $nama =
                                $this
                                    ->ambilNamaPengirimPenerima(
                                        $transaksi
                                            ->deskripsi_bank
                                    );


                            /*
                            |--------------------------------------------------------------------------
                            | JIKA BERHASIL DIDETEKSI
                            |--------------------------------------------------------------------------
                            */

                            if ($nama) {

                                $transaksi->update([

                                    'nama_pengirim_penerima' =>
                                        $nama,
                                ]);


                                $berhasil++;

                            } else {

                                $tidakTerdeteksi++;
                            }

                        } catch (\Throwable $e) {

                            $gagal++;
                        }


                        $progressBar->advance();
                    }
                }
            );


        $progressBar->finish();


        $this->newLine(2);


        /*
        |--------------------------------------------------------------------------
        | HASIL
        |--------------------------------------------------------------------------
        */

        $this->table(
            [
                'Keterangan',
                'Jumlah',
            ],
            [
                [
                    'Nama berhasil diisi',
                    $berhasil,
                ],
                [
                    'Nama tidak terdeteksi',
                    $tidakTerdeteksi,
                ],
                [
                    'Gagal diproses',
                    $gagal,
                ],
                [
                    'Total diperiksa',
                    $total,
                ],
            ]
        );


        $this->info(
            'Backfill nama pengirim/penerima selesai.'
        );


        return self::SUCCESS;
    }


    /*
    |--------------------------------------------------------------------------
    | AMBIL NAMA PENGIRIM / PENERIMA
    |--------------------------------------------------------------------------
    */

    private function ambilNamaPengirimPenerima(
        ?string $deskripsi
    ): ?string {

        if (!$deskripsi) {

            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | NORMALISASI SPASI
        |--------------------------------------------------------------------------
        */

        $deskripsi =
            trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    $deskripsi
                )
            );


        if ($deskripsi === '') {

            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | 1. BI-FAST
        |--------------------------------------------------------------------------
        |
        | Contoh:
        |
        | BI-FAST CR TRANSFER DR 008 AGUS GUNARTO
        |
        */

        if (
            preg_match(
                '/BI[\-\s]?FAST.*?TRANSFER\s+(?:DR|FROM|TO)\s+\d+\s+(.+)$/i',
                $deskripsi,
                $match
            )
        ) {

            $nama =
                $this->bersihkanNamaBank(
                    $match[1]
                );


            if ($nama) {

                return $nama;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 2. TRANSFER DR / FROM / TO
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/TRANSFER\s+(?:DR|FROM|TO)\s+(?:\d+\s+)?(.+)$/i',
                $deskripsi,
                $match
            )
        ) {

            $nama =
                $this->bersihkanNamaBank(
                    $match[1]
                );


            if ($nama) {

                return $nama;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 3. BCA TRSF E-BANKING
        |--------------------------------------------------------------------------
        |
        | Contoh:
        |
        | TRSF E-BANKING DB 0107/FTSCY/WS95051
        | 4800000.00 grc 1x1 AMIN NURWACHID
        |
        */

        if (
            preg_match(
                '/TRSF\s+E[\-\s]?BANKING/i',
                $deskripsi
            )
        ) {

            $nama =
                $this->ambilNamaDariAkhir(
                    $deskripsi
                );


            if ($nama) {

                return $nama;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 4. TRANSFER + KODE BANK
        |--------------------------------------------------------------------------
        |
        | Contoh:
        |
        | TRANSFER ... 008 AGUS GUNARTO
        |
        */

        if (
            preg_match(
                '/(?:TRSF|TRANSFER).*?\s\d{3}\s+([A-Z][A-Z\s\.\']{3,})$/i',
                $deskripsi,
                $match
            )
        ) {

            $nama =
                $this->bersihkanNamaBank(
                    $match[1]
                );


            if ($nama) {

                return $nama;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | 5. FALLBACK
        |--------------------------------------------------------------------------
        |
        | Cari kumpulan huruf di bagian paling akhir.
        |
        */

        return $this->ambilNamaDariAkhir(
            $deskripsi
        );
    }


    /*
    |--------------------------------------------------------------------------
    | AMBIL NAMA DARI AKHIR
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


        if ($text === '') {

            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | BAGIAN BERUPA HURUF DI PALING AKHIR
        |--------------------------------------------------------------------------
        |
        | 4800000.00 grc 1x1 AMIN NURWACHID
        |
        | menjadi:
        |
        | AMIN NURWACHID
        |
        */

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


            if (!$nama) {

                return null;
            }


            if (
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

        if (!$nama) {

            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | NORMALISASI SPASI
        |--------------------------------------------------------------------------
        */

        $nama =
            trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    $nama
                )
            );


        /*
        |--------------------------------------------------------------------------
        | HAPUS KARAKTER DI AWAL / AKHIR
        |--------------------------------------------------------------------------
        */

        $nama =
            trim(
                $nama,
                " \t\n\r\0\x0B-_/.,:"
            );


        /*
        |--------------------------------------------------------------------------
        | HAPUS PREFIX
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | VALIDASI PANJANG
        |--------------------------------------------------------------------------
        */

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
        |--------------------------------------------------------------------------
        | ANGKA SAJA
        |--------------------------------------------------------------------------
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
        |--------------------------------------------------------------------------
        | NAMA TIDAK BOLEH MENGANDUNG ANGKA
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/\d/',
                $nama
            )
        ) {

            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | UPPERCASE
        |--------------------------------------------------------------------------
        */

        $nama =
            strtoupper(
                $nama
            );


        /*
        |--------------------------------------------------------------------------
        | BLACKLIST
        |--------------------------------------------------------------------------
        */

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
    | VALIDASI NAMA
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


        /*
        |--------------------------------------------------------------------------
        | BLACKLIST EXACT
        |--------------------------------------------------------------------------
        */

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


        if (
            in_array(
                $nama,
                $blacklist,
                true
            )
        ) {

            return true;
        }


        /*
        |--------------------------------------------------------------------------
        | JANGAN SIMPAN KALIMAT TRANSAKSI SEBAGAI NAMA
        |--------------------------------------------------------------------------
        */

        $kataTerlarang = [

            'SALDO',

            'MUTASI',

            'TRANSFER SUCCESS',

            'ADMIN BANK',

            'BIAYA ADMIN',

            'BIAYA TRANSFER',

            'BUNGA',

            'PAJAK BUNGA',
        ];


        foreach (
            $kataTerlarang
            as $kata
        ) {

            if (
                str_contains(
                    $nama,
                    $kata
                )
            ) {

                return true;
            }
        }


        return false;
    }
}