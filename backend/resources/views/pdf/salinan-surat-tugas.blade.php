<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Tugas Belajar Mandiri</title>
    <style>
        @page {
            margin: 20mm 25mm 20mm 25mm;
            size: A4 portrait;
        }

        /* Preview Mode - A4 Page Styling */
        .preview-wrapper {
            background: #525659;
            padding: 20px;
            display: flex;
            justify-content: center;
            min-height: 100vh;
        }

        .a4-page {
            background: white;
            width: 210mm;
            min-height: 297mm;
            padding: 20mm 25mm 20mm 25mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            position: relative;
            /* Allow content to flow naturally */
            height: auto;
        }

        /* Page break indicator in preview mode */
        .page-break {
            page-break-before: always;
            break-before: page;
        }

        body {
            font-family: Times New Roman, Arial, serif;
            font-size: 11pt;
            line-height: 1.3;
            margin: 0;
            padding: 0;
            color: #000000;
            background: #525659;
        }
        .kop-container {
            text-align: center;
            margin-bottom: 10mm;
        }
        .kop-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
        }
        .kop-logo {
            width: 70px;
            height: 70px;
        }
        .kop-logo img {
            width: 65px;
            height: auto;
        }
        .kop-text {
            flex: 1;
        }
        .kop-line-top {
            border-top: 3pt solid #000000;
        }
        .kop-line-bottom {
            border-bottom: 1pt solid #000000;
            padding-bottom: 10px;
        }
        .kop-nama-daerah {
            font-family: Arial, sans-serif;
            font-size: 15pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 2px 0;
        }
        .kop-nama-dinas {
            font-family: Arial, sans-serif;
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 2px 0;
        }
        .kop-alamat {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin: 2px 0;
        }
        .judul-surat {
            text-align: center;
            margin: 10mm 0 5mm 0;
        }
        .judul-surat h2 {
            font-family: Times New Roman, Arial, sans-serif;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }
        .tentang {
            text-align: center;
            font-weight: bold;
            margin-bottom: 5mm;
        }
        .nomor-surat {
            text-align: center;
            font-family: Times New Roman, Arial, sans-serif;
            font-size: 11pt;
            margin-bottom: 5mm;
        }
        .content {
            text-align: justify;
            margin-bottom: 5mm;
        }
        .content p {
            margin: 0 0 3mm 0;
        }
        .dasar-hukum {
            margin-bottom: 5mm;
        }
        .dasar-hukum ol {
            margin: 0;
            padding-left: 25px;
        }
        .dasar-hukum li {
            margin-bottom: 1mm;
            line-height: 1.3;
        }
        .menugaskan {
            margin-left: 10mm;
            margin-bottom: 5mm;
        }
        .menugaskan-title {
            font-weight: bold;
            margin-bottom: 2mm;
        }
        .data-pegawai {
            margin-left: 15mm;
            margin-bottom: 5mm;
        }
        .data-pegawai table {
            border-collapse: collapse;
        }
        .data-pegawai td {
            padding: 2mm 0;
            vertical-align: top;
        }
        .data-pegawai td:first-child {
            font-family: Times New Roman, Arial, sans-serif;
            width: 150px;
        }
        .data-pegawai td:nth-child(2) {
            width: 5px;
        }
        .tujuan-pendidikan {
            margin-bottom: 5mm;
        }
        .tujuan-pendidikan-title {
            font-weight: bold;
            margin-bottom: 2mm;
        }
        .tujuan-table {
            margin-left: 15mm;
        }
        .ketentuan {
            margin-left: 10mm;
            margin-bottom: 5mm;
            text-align: justify;
        }
        .ketentuan-title {
            font-weight: bold;
            margin-bottom: 3mm;
        }
        .ketentuan ol {
            margin: 0;
            padding-left: 20px;
        }
        .ketentuan li {
            margin-bottom: 2mm;
            line-height: 1.3;
        }
        .sanksi {
            margin-left: 10mm;
            margin-bottom: 5mm;
            text-align: justify;
        }
        .penutup {
            text-align: justify;
            margin-bottom: 15mm;
        }
        .ttd-container {
            text-align: center;
            margin-top: 10mm;
        }
        .ttd-tempat {
            margin-bottom: 2mm;
            font-size: 10pt;
        }
        .ttd-jabatan {
            margin-bottom: 2mm;
            font-weight: bold;
            font-size: 10pt;
        }
        .ttd-nama {
            margin-bottom: 2mm;
            font-weight: bold;
            font-size: 10pt;
            text-decoration: underline;
        }
        .ttd-nip {
            margin: 0;
            font-size: 10pt;
        }
        .tembusan {
            margin-top: 10mm;
            font-size: 10pt;
        }
        .tembusan p {
            margin: 2mm 0;
        }
        .tembusan-title {
            font-weight: bold;
        }
        .qr-section {
            margin-top: 10mm;
            text-align: center;
        }
        .qr-label {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            color: #666;
            margin-bottom: 2mm;
        }
        .qr-code {
            width: 70px;
            height: 70px;
            margin: 0 auto;
            display: block;
        }

        /* Page Break Styling for PDF Generation */
        .page-break {
            page-break-before: always;
            break-before: page;
        }

        .avoid-page-break {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Print Mode - Hide preview wrapper styling */
        @media print {
            .preview-wrapper {
                background: none;
                padding: 0;
            }
            .a4-page {
                box-shadow: none;
                width: auto;
                min-height: auto;
            }
            body {
                background: white;
            }
            /* Ensure page breaks work correctly */
            .page-break {
                page-break-before: always;
                break-before: page;
            }
        }
    </style>
</head>
<body>
    <div class="preview-wrapper">
        <div class="a4-page">
    <!-- KOP SURAT -->
    <div class="kop-container">
        <div class="kop-line-bottom">
            <div class="kop-header">
                {{-- Logo Kabupaten --}}
                @if(isset($logoBase64))
                    <div class="kop-logo">
                        <img src="{{ $logoBase64 }}" alt="Logo Kabupaten">
                    </div>
                @endif
                <div class="kop-text">
                    <div class="kop-nama-daerah">PEMERINTAH KABUPATEN SUKABUMI</div>
                    <div class="kop-nama-dinas">BADAN KEPEGAWAIAN DAN PENGEMBANGAN SUMBER DAYA MANUSIA</div>
                    <div class="kop-alamat">
                        Jl. Raya Sukabumi - Cianjur Km. 12 Sukabumi 42113<br>
                        Telp: (0266) 222562, Email: bkpsdm@sukabumikab.go.id
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JUDUL SURAT -->
     <div style="margin: 0; padding: 0; transform: translateY(-30px); text-align: center;">
        <h2 style="margin: 0; padding: 0; font-family: 'Arial', Times, serif;">SURAT TUGAS</h2>
    </div>

    <div style="margin: 0; padding: 0; transform: translateY(-30px); text-align: center;">
        Nomor: {{ $surat->nomor_surat }}
    </div>

    <div class="tentang">
        TENTANG<br>
        BELAJAR MANDIRI TIDAK DIBERHENTIKAN DARI JABATAN<br>
        JENJANG PENDIDIKAN {{ $surat->pengajuan->jenjang->nama_jenjang ?? 'S1' }}
    </div>

    <!-- DASAR HUKUM -->
    <div class="dasar-hukum">
        <p>Dasar:</p>
        <ol>
            <li>Undang-Undang Nomor 20 Tahun 2003 tentang Sistem Pendidikan Nasional;</li>
            <li>Undang-Undang Nomor 20 Tahun 2023 tentang Aparatur Sipil Negara;</li>
            <li>Peraturan Pemerintah Republik Indonesia Nomor 17 Tahun 2020 tentang Manajemen Pegawai Negeri Sipil;</li>
            <li>Peraturan Daerah Kabupaten Sukabumi Nomor 3 Tahun 2024 tentang Pembentukan dan Susunan Perangkat Daerah;</li>
            <li>Peraturan Bupati Sukabumi Nomor 2 Tahun 2022 tentang Pedoman Pelaksanaan Tugas Belajar Mandiri Bagi Pegawai Negeri Sipil di Lingkungan Pemerintah Kabupaten Sukabumi;</li>
            <li>Surat Izin Belajar Mandiri Nomor: {{ $surat->pengajuan->suratIzinBelajar->nomor_surat ?? '-' }} tanggal {{ $surat->pengajuan->suratIzinBelajar->signed_at ? \Carbon\Carbon::parse($surat->pengajuan->suratIzinBelajar->signed_at)->locale('id')->translatedFormat('d F Y') : '-' }} Perihal Izin Belajar Mandiri.</li>
        </ol>
    </div>

    <!-- MENUGASKAN -->
    <div class="menugaskan">
        <p class="menugaskan-title">MENUGASKAN:</p>
        <div class="data-pegawai">
            <table>
                <tr>
                    <td>Kepada</td>
                    <td>:</td>
                    <td><strong>{{ $surat->pengajuan->user->name }}</strong></td>
                </tr>
                <tr>
                    <td>NIP</td>
                    <td>:</td>
                    <td>{{ $surat->pengajuan->user->nip }}</td>
                </tr>
                <tr>
                    <td>Pangkat/Golongan</td>
                    <td>:</td>
                    <td>{{ $surat->pengajuan->user->pangkat_gol ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Jabatan</td>
                    <td>:</td>
                    <td>{{ $surat->pengajuan->user->jabatan ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Unit Kerja</td>
                    <td>:</td>
                    <td>{{ $surat->unitKerja->nama ?? '-' }}</td>
                </tr>
            </table>
        </div>
        <p>Untuk mengikuti pendidikan Tugas Belajar Mandiri dengan ketentuan sebagai berikut:</p>
    </div>

    <!-- TUJUAN PENDIDIKAN -->
    <div class="tujuan-pendidikan">
        <p class="tujuan-pendidikan-title">A. Tujuan Pendidikan:</p>
        <div class="tujuan-table">
            <table>
                <tr>
                    <td>Bidang Studi</td>
                    <td>:</td>
                    <td><strong>{{ $surat->pengajuan->nama_prodi }}</strong></td>
                </tr>
                <tr>
                    <td>Perguruan Tinggi</td>
                    <td>:</td>
                    <td><strong>{{ $surat->pengajuan->perguruan_tinggi }}</strong></td>
                </tr>
                <tr>
                    <td>Jenjang Pendidikan</td>
                    <td>:</td>
                    <td>{{ $surat->pengajuan->jenjang->nama_jenjang ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Masa Studi</td>
                    <td>:</td>
                    <td>
                        {{ \Carbon\Carbon::parse($surat->tanggal_mulai)->locale('id')->translatedFormat('d F Y') }}
                        s.d.
                        {{ \Carbon\Carbon::parse($surat->tanggal_selesai)->locale('id')->translatedFormat('d F Y') }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- KETENTUAN -->
    <div class="ketentuan">
        <p class="ketentuan-title">B. Ketentuan:</p>
        <ol>
            <li>Tugas mengikuti pendidikan tersebut diberikan di luar jam kerja;</li>
            <li>Tidak mengganggu tugas-tugas kedinasan;</li>
            <li>Pendidikan yang diikuti harus sesuai dengan norma dan kaidah akademik yang berlaku;</li>
            <li>Biaya pendidikan sepenuhnya ditanggung oleh yang bersangkutan;</li>
            <li>Tidak menuntut penyesuaian kenaikan pangkat dan pengakuan gelar akademik kecuali formasi yang tersedia memungkinkan.</li>
        </ol>
    </div>

    <!-- SANKSI -->
    <div class="sanksi">
        <p>Sanksi:</p>
        <p style="margin-left: 20px; text-indent: -20px;">
            Apabila yang bersangkutan tidak melaksanakan tugas pendidikan sebagaimana mestinya atau tidak kembali bertugas setelah selesai menjalani tugas pendidikan, maka yang bersangkutan wajib mengembalikan biaya yang telah dikeluarkan oleh pemerintah daerah selama mengikuti pendidikan tersebut.
        </p>
    </div>

    <!-- PENUTUP -->
    <div class="penutup">
        <p>
            Demikian Surat Tugas ini dibuat untuk dilaksanakan dengan penuh tanggung jawab.
        </p>
    </div>

    <!-- TANDA TANGAN -->
    <div class="ttd-container">
        <div class="ttd-tempat">
            {{ $surat->tempat_ttd ?? 'Sukabumi' }}, {{ \Carbon\Carbon::parse($surat->tanggal_ttd)->locale('id')->translatedFormat('d F Y') }}
        </div>
        <div class="ttd-jabatan">Kepala Badan Kepegawaian dan Pengembangan Sumber Daya Manusia</div>
        <div class="ttd-nip">{{ $surat->kepalaDinas->name ?? 'Kepala BKPSDM' }}</div>
        <div class="ttd-nip">NIP. {{ $surat->kepalaDinas->nip ?? '-' }}</div>
    </div>

    <!-- TEMBUSAN -->
    <div class="tembusan">
        <p class="tembusan-title">Tembusan:</p>
        <p>1. Bupati Sukabumi;</p>
        <p>2. Sekretaris Daerah Kabupaten Sukabumi;</p>
        <p>3. Kepala Dinas {{ $surat->unitKerja->nama ?? 'Teranga' }};</p>
        <p>4. Yang bersangkutai.</p>
    </div>

    <!-- QR CODE -->
    @if(isset($qrCodeBase64) && $qrCodeBase64)
    <div class="qr-section">
        <p class="qr-label">Scan QR Code untuk verifikasi keaslian surat</p>
        <img src="{{ $qrCodeBase64 }}" alt="QR Code" class="qr-code">
    </div>
    @endif
        </div><!-- End a4-page -->
    </div><!-- End preview-wrapper -->
</body>
</html>
