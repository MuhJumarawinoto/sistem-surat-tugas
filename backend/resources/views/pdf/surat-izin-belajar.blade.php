<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat Izin Belajar Mandiri</title>
    <style>
        @page {
            margin: 15mm 15mm 15mm 15mm;
            size: A4 portrait;
        }
        body {
            font-family: Arial, 'Times New Roman', serif;
            font-size: 10pt;
            line-height: 1.2;
            margin: 0;
            padding: 0;
            color: #000000;
        }
        .header {
            text-align: center;
            margin-bottom: 8mm;
        }
        .header-line-top {
            border-top: 3pt solid #000000;
        }
        .header-line-bottom {
            border-bottom: 1pt solid #000000;
            padding-bottom: 10px;
        }
        .header-title {
            font-family: Arial, sans-serif;
            font-weight: bold;
            font-size: 15pt;
            text-transform: uppercase;
            margin: 0;
        }
        .header-subtitle {
            font-family: Arial, sans-serif;
            font-weight: bold;
            font-size: 14pt;
            text-transform: uppercase;
            margin: 3px 0;
        }
        .header-contact {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin: 2px 0;
        }
        .content {
            margin-top: 6mm;
        }
        .content-title {
            text-align: center;
            font-family: Arial, sans-serif;
            font-weight: bold;
            font-size: 12pt;
            text-transform: uppercase;
            margin-bottom: 5mm;
        }
        .content-subtitle {
            text-align: center;
            font-weight: normal;
            font-size: 10pt;
            margin-bottom: 4mm;
        }
        .nomor-surat {
            text-align: center;
            font-family: Arial, sans-serif;
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 6mm;
        }
        .section {
            margin-bottom: 5mm;
        }
        .section-title {
            font-family: Arial, sans-serif;
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 3mm;
        }
        .section p {
            margin: 0 0 3mm 0;
            text-align: justify;
        }
        .info-table {
            width: 100%;
            margin-left: 15mm;
            margin-bottom: 8mm;
        }
        .info-table td {
            padding: 2mm 0;
            vertical-align: top;
        }
        .info-label {
            font-family: Arial, sans-serif;
            width: 150px;
        }
        .numbering {
            margin-left: 15mm;
            text-align: justify;
        }
        .numbering p {
            margin: 0 0 3mm 0;
            line-height: 1.3;
        }
        .signature {
            margin-top: 15mm;
            margin-right: 10mm;
            text-align: right;
        }
        .signature p {
            margin: 0 0 2mm 0;
            font-size: 9pt;
        }
        .signature-space {
            height: 20mm;
        }
        .signature-name {
            font-family: Arial, sans-serif;
            font-weight: bold;
        }
        .qr-section {
            margin-top: 10mm;
            text-align: center;
        }
        .qr-label {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            color: #666;
            margin-bottom: 2mm;
        }
        .qr-code {
            width: 80px;
            height: 80px;
            margin: 0 auto;
            display: block;
        }
        .barcode-section {
            margin-top: 3mm;
            text-align: center;
        }
        .barcode-label {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            color: #666;
            margin-bottom: 2mm;
        }
        .barcode-img {
            max-width: 200px;
            height: 50px;
            margin: 0 auto;
            display: block;
        }
        .separator {
            border-top: 2pt solid #000000;
            margin: 5mm 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-line-top"></div>
        <div class="header-line-bottom">
            <div class="header-title">PEMERINTAH KABUPATEN SUKABUMI</div>
            <div class="header-subtitle">BADAN KEPEGAWAIAN DAN PENGEMBANGAN SUMBER DAYA MANUSIA</div>
            <div class="header-contact">Jalan Raya Kadupugur Km.10,4 Cicantayan, Sukabumi, Kode Pos 43155</div>
            <div class="header-contact">Telepon: (0266) 531872 Faksimil: (0266) 6545141</div>
        </div>
    </div>

    <div class="content">
        <div class="content-title">
            SURAT IZIN BELAJAR MANDIRI
        </div>
        <div class="content-subtitle">
            TIDAK DIBERHENTIKAN DARI JABATAN
        </div>

        <div class="nomor-surat">
            Nomor: {{ $surat->nomor_surat }}
        </div>

        <div class="section">
            <p>Yang bertanda tangan di bawah ini Kepala Badan Kepegawaian dan Pengembangan Sumber Daya Manusia Kabupaten Sukabumi, memberikan izin belajar mandiri kepada:</p>
            <table class="info-table">
                <tr>
                    <td class="info-label">Nama</td>
                    <td>: <strong>{{ $surat->pengajuan->user->name ?? '-' }}</strong></td>
                </tr>
                <tr>
                    <td class="info-label">NIP</td>
                    <td>: {{ $surat->pengajuan->user->nip ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Pangkat/Golongan</td>
                    <td>: {{ $surat->pengajuan->user->pangkat_gol ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Jabatan</td>
                    <td>: {{ $surat->pengajuan->user->jabatan ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Unit Kerja</td>
                    <td>: {{ $surat->pengajuan->user->unitKerja->nama ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <p>Untuk mengikuti pendidikan:</p>
            <table class="info-table">
                <tr>
                    <td class="info-label">Jenjang</td>
                    <td>: {{ $surat->pengajuan->jenjang->nama_jenjang ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Program Studi</td>
                    <td>: {{ $surat->pengajuan->nama_prodi }}</td>
                </tr>
                <tr>
                    <td class="info-label">Perguruan Tinggi</td>
                    <td>: {{ $surat->pengajuan->perguruan_tinggi }}</td>
                </tr>
            </table>
        </div>

        <div class="separator"></div>

        <div class="section">
            <p class="section-title">Dasar:</p>
            <div class="numbering">
                <p>1. Undang-Undang Nomor 20 Tahun 2003 tentang Sistem Pendidikan Nasional;</p>
                <p>2. Undang-Undang Nomor 20 Tahun 2023 tentang Aparatur Sipil Negara;</p>
                <p>3. Peraturan Pemerintah Nomor 17 Tahun 2020 tentang Manajemen Pegawai Negeri Sipil;</p>
                <p>4. Peraturan Daerah Kabupaten Sukabumi Nomor 3 Tahun 2024 tentang Pembentukan dan Susunan Perangkat Daerah;</p>
                <p>5. Peraturan Bupati Sukabumi Nomor 2 Tahun 2022 tentang Pedoman Tugas Belajar; dan</p>
                <p>6. Surat Tugas {{ $surat->suratTugasDinas->full_nomor_surat }} tanggal {{ \Carbon\Carbon::parse($surat->suratTugasDinas->tanggal_ttd)->locale('id')->translatedFormat('d F Y') }}.</p>
            </div>
        </div>

        <div class="section">
            <p class="section-title">Ketentuan:</p>
            <div class="numbering">
                <p>1. Tugas mengikuti pendidikan diberikan di luar jam kerja;</p>
                <p>2. Tidak mengganggu tugas-tugas kedinasan;</p>
                <p>3. Pendidikan yang diikuti harus sesuai norma dan kaidah akademik;</p>
                <p>4. Biaya pendidikan sepenuhnya ditanggung oleh yang bersangkutan; dan</p>
                <p>5. Tidak menuntut penyesuaian kenaikan pangkat dan pengakuan gelar akademik kecuali formasi memungkinkan.</p>
            </div>
        </div>

        <div class="section">
            <p>Demikian surat izin belajar mandiri ini dibuat untuk dipergunakan sebagaimana mestinya.</p>
        </div>

        <div class="signature">
            <p>Ditetapkan di: Sukabumi</p>
            <p>Pada Tanggal: <strong>{{ \Carbon\Carbon::parse($surat->signed_at ?? $surat->created_at)->locale('id')->translatedFormat('d F Y') }}</strong></p>
            <div class="signature-space"></div>
            <p class="signature-name"><strong>KEPALA BADAN KEPEGAWAIAN DAN</strong></p>
            <p class="signature-name"><strong>PENGEMBANGAN SUMBER DAYA MANUSIA</strong></p>
            <p class="signature-name"><strong>KABUPATEN SUKABUMI</strong></p>
            <p style="margin-top: 3mm;">{{ $surat->signed_by ?? '................................' }}</p>
            <p>NIP. {{ $surat->signed_by_nip ?? '................................' }}</p>
        </div>
    </div>

    {{-- QR Code Section --}}
    @if(isset($qrCodeBase64) && $qrCodeBase64)
    <div class="qr-section">
        <p class="qr-label">Scan QR Code untuk verifikasi keaslian surat</p>
        <img src="{{ $qrCodeBase64 }}" alt="QR Code" style="width: 80px; height: 80px; display: block; margin: 0 auto;" />
    </div>
    @endif

    {{-- Barcode Section --}}
    @if(isset($barcodeBase64) && $barcodeBase64)
    <div class="barcode-section">
        <p class="barcode-label">Nomor Surat</p>
        <img src="{{ $barcodeBase64 }}" alt="Barcode" style="max-width: 200px; height: 50px; display: block; margin: 0 auto;" />
        <p style="font-family: Arial, sans-serif; font-size: 8pt; color: #666; margin-top: 2mm; text-align: center;">{{ $surat->nomor_surat }}</p>
    </div>
    @endif
</body>
</html>
