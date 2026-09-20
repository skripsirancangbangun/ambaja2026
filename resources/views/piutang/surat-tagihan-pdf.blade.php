<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Tagihan Piutang - {{ $invoice->nomor_invoice }}</title>
    <style>
        @page { margin: 30px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.6; }
        .header { border-bottom: 2px solid #15235F; padding-bottom: 10px; margin-bottom: 20px; }
        .company { font-size: 16px; font-weight: bold; color: #15235F; }
        .title { font-size: 14px; font-weight: bold; text-align: center; text-decoration: underline; margin: 15px 0; color: #15235F; }
        table.detail { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table.detail th, table.detail td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; }
        table.detail th { background: #f8fafc; font-weight: bold; }
        .right { text-align: right; }
        .sign { margin-top: 40px; width: 100%; }
    </style>
</head>
<body>

    @php
        $profil = \App\Models\ProfilPerusahaan::getProfil();
    @endphp

    <div class="header">
        <table width="100%">
            <tr>
                <td>
                    <div class="company">{{ strtoupper($profil->nama_perusahaan) }}</div>
                    <div style="font-size: 10px; color: #64748b;">{{ $profil->alamat }} | Telp: {{ $profil->telepon }}</div>
                </td>
                <td class="right" style="font-size: 10px; color: #64748b;">
                    Tanggal: {{ date('d F Y') }}<br>
                    Nomor: ST/{{ date('Ym') }}/{{ str_pad($invoice->id, 4, '0', STR_PAD_LEFT) }}
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

    <div class="title">SURAT PENAGIHAN PIUTANG</div>

    <p>Dengan hormat,</p>
    <p>Melalui surat ini, kami informasikan rincian tagihan faktur penjualan yang telah memasuki atau mendekati masa jatuh tempo pembayaran sebagai berikut:</p>

    <table class="detail">
        <thead>
            <tr>
                <th>No. Invoice</th>
                <th>Tanggal Faktur</th>
                <th>Jatuh Tempo</th>
                <th class="right">Total Tagihan</th>
                <th class="right">Sudah Dibayar</th>
                <th class="right">Sisa Tagihan (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>{{ $invoice->nomor_invoice }}</strong></td>
                <td>{{ $invoice->tanggal_invoice ? $invoice->tanggal_invoice->format('d/m/Y') : '-' }}</td>
                <td>{{ $invoice->tanggal_jatuh_tempo ? $invoice->tanggal_jatuh_tempo->format('d/m/Y') : '-' }}</td>
                <td class="right">Rp {{ number_format($invoice->total, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($invoice->sudah_dibayar, 0, ',', '.') }}</td>
                <td class="right" style="font-weight: bold; color: #dc2626;">Rp {{ number_format($invoice->sisa_piutang, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <p>Mohon agar pembayaran sisa tagihan sebesar <strong style="color: #dc2626;">Rp {{ number_format($invoice->sisa_piutang, 0, ',', '.') }}</strong> dapat segera ditransfer melalui rekening resmi kami:</p>

    <div style="background: #f8fafc; padding: 10px; border: 1px solid #cbd5e1; margin: 10px 0;">
        <strong>Rekening Pembayaran {{ $profil->nama_singkat }}:</strong><br>
        @if($profil->rekening_bca) Bank BCA: {{ $profil->rekening_bca }}<br> @endif
        @if($profil->rekening_mandiri) Bank Mandiri: {{ $profil->rekening_mandiri }} @endif
    </div>

    <p>Demikian surat penagihan ini kami sampaikan. Atas perhatian dan kerja samanya, kami ucapkan terima kasih.</p>

    <table class="sign">
        <tr>
            <td width="60%"></td>
            <td width="40%" style="text-align: center;">
                Hormat kami,<br>
                <strong>{{ strtoupper($profil->nama_perusahaan) }}</strong><br><br><br><br>
                <strong>( Bagian Keuangan / Penagihan )</strong>
            </td>
        </tr>
    </table>

</body>
</html>
