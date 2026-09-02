<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Peringatan {{ $levelText }} - {{ $invoice->nomor_invoice }}</title>
    <style>
        @page { margin: 30px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.6; }
        .header { border-bottom: 2px solid #dc2626; padding-bottom: 10px; margin-bottom: 20px; }
        .company { font-size: 16px; font-weight: bold; color: #15235F; }
        .title { font-size: 15px; font-weight: bold; text-align: center; text-decoration: underline; margin: 15px 0; color: #dc2626; }
        table.detail { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table.detail th, table.detail td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; }
        table.detail th { background: #fef2f2; color: #991b1b; font-weight: bold; }
        .right { text-align: right; }
        .sign { margin-top: 40px; width: 100%; }
        .alert-box { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; padding: 10px; margin: 10px 0; font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <table width="100%">
            <tr>
                <td>
                    <div class="company">CV ANDIKA MUSTIKA (AMBAJA)</div>
                    <div style="font-size: 10px; color: #64748b;">Jl. Mustika No. 88, Surabaya | Telp: (031) 888-9999</div>
                </td>
                <td class="right" style="font-size: 10px; color: #64748b;">
                    Tanggal: {{ date('d F Y') }}<br>
                    Nomor: {{ $levelText }}/{{ date('Ym') }}/{{ str_pad($invoice->id, 4, '0', STR_PAD_LEFT) }}
                </td>
            </tr>
        </table>
    </div>

    <table width="100%" style="margin-bottom: 15px;">
        <tr>
            <td width="15%"><strong>Kepada Yth,</strong></td>
            <td width="85%">: <strong>{{ $invoice->customer?->nama_customer }}</strong></td>
        </tr>
        <tr>
            <td><strong>Alamat</strong></td>
            <td>: {{ $invoice->customer?->alamat ?: '-' }}</td>
        </tr>
        <tr>
            <td><strong>Telepon</strong></td>
            <td>: {{ $invoice->customer?->telepon ?: '-' }}</td>
        </tr>
    </table>

    <div class="title">SURAT PERINGATAN KETERLAMBATAN PEMBAYARAN ({{ $levelText }})</div>

    <p>Dengan hormat,</p>
    <p>Menunjuk pada catatan transaksi akuntansi kami, bersama surat ini kami sampaikan bahwa pembayaran tagihan faktur atas nama perusahaan Anda telah **MELEWATI TANGGAL JATUH TEMPO** sebagai berikut:</p>

    <table class="detail">
        <thead>
            <tr>
                <th>No. Invoice</th>
                <th>Tanggal Faktur</th>
                <th>Tgl Jatuh Tempo</th>
                <th class="right">Total Tagihan</th>
                <th class="right">Sisa Terhutang (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>{{ $invoice->nomor_invoice }}</strong></td>
                <td>{{ $invoice->tanggal_invoice ? $invoice->tanggal_invoice->format('d/m/Y') : '-' }}</td>
                <td>{{ $invoice->tanggal_jatuh_tempo ? $invoice->tanggal_jatuh_tempo->format('d/m/Y') : '-' }}</td>
                <td class="right">Rp {{ number_format($invoice->total, 0, ',', '.') }}</td>
                <td class="right" style="font-weight: bold; color: #dc2626;">Rp {{ number_format($invoice->sisa_piutang, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    @if($level == '1')
        <div class="alert-box">
            PERINGATAN I: Kami memohon kesediaan Anda untuk dapat melakukan pelunasan tunggakan sebesar Rp {{ number_format($invoice->sisa_piutang, 0, ',', '.') }} selambat-lambatnya dalam waktu 3 (tiga) hari kerja sejak diterimanya surat ini.
        </div>
    @elseif($level == '2')
        <div class="alert-box">
            PERINGATAN II (SP-2): Karena pemberitahuan sebelumnya belum direspon, kami tegaskan agar pelunasan tunggakan sebesar Rp {{ number_format($invoice->sisa_piutang, 0, ',', '.') }} dapat diselesaikan selambat-lambatnya 2 (dua) hari kerja. Pengiriman barang selanjutnya ditangguhkan sementara.
        </div>
    @else
        <div class="alert-box">
            PERINGATAN III / SP-3 (TERAKHIR): Ini adalah Peringatan Terakhir. Jika pembayaran tunggakan sebesar Rp {{ number_format($invoice->sisa_piutang, 0, ',', '.') }} tidak dilunasi dalam 1x24 jam, maka transaksi kredit akan dibekukan total dan penagihan akan dilimpahkan ke tim hukum/manajemen resmi CV Ambaja.
        </div>
    @endif

    <p>Pembayaran dapat ditransfer ke rekening resmi BCA 123-456-7890 a.n CV Andika Mustika. Apabila Anda telah melakukan pembayaran, abaikan surat ini atau kirimkan bukti transfer kepada kami.</p>

    <table class="sign">
        <tr>
            <td width="60%"></td>
            <td width="40%" style="text-align: center;">
                Hormat kami,<br>
                <strong>CV ANDIKA MUSTIKA</strong><br><br><br><br>
                <strong>( Manajer Keuangan & Penagihan )</strong>
            </td>
        </tr>
    </table>

</body>
</html>
