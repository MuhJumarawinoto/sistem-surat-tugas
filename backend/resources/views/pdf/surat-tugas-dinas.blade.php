<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Tugas Belajar Mandiri</title>
    <style>
        /* PDF PAGE SETTINGS (domPDF uses this) */
        @page {
            margin-top: 0;
            margin-bottom: 0;
            margin-left: 0;
            margin-right: 10;
            size: A4 portrait;
        }

        /* Base styles for BOTH preview and PDF */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            color: #000000;
            background: white;
        }

        /* PREVIEW WRAPPER - Only visible in browser preview */
        .preview-wrapper {
            background: white;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            min-height: 100vh;
        }

        /* Gray background for preview only */
        .preview-wrapper.is-preview {
            background: #525659;
        }

        /* PAGE - Represents one A4 page in both preview and PDF */
        .preview-page {
            background: white;
            width: 210mm;
            min-height: 297mm; /* min-height for preview to show A4 size */
            position: relative;
            border: none;
        }

        /* Shadow for preview only */
        .preview-wrapper.is-preview .preview-page {
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        /* Page break indicator - RED LINE at bottom of each page in preview */
        .preview-wrapper .preview-page::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: repeating-linear-gradient(
                90deg,
                #ff0000 0px,
                #ff0000 8px,
                transparent 8px,
                transparent 16px
            );
            z-index: 9999;
            pointer-events: none;
        }

        /* "Next Page" indicator between pages in preview */
        .preview-wrapper .preview-page:not(:last-child)::before {
            content: '▼ HALAMAN BERIKUTNYA ▼';
            position: absolute;
            bottom: -30px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 11pt;
            color: #ff0000;
            font-weight: bold;
            white-space: nowrap;
            background: #525659;
            padding: 4px 12px 0px 0px;
            border-radius: 4px;
        }

        /* CONTENT WRAPPER - Main content area with padding */
        .content-wrapper {
           padding: 5mm 15mm 5mm 5mm;
           position: relative;
           height: 100%;
        }

        /* Watermark Background */
        .watermark-bg {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 40%;
            height: 40%;
            background-image: url('{{ $bgBase64 ?? '' }}');
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            opacity: 0.1;
            pointer-events: none;
            z-index: 0;
        }

        .content-inner {
            position: relative;
            z-index: 1;
        }

        /* KOP SURAT */
        .kop-surat {
            text-align: center;
            margin-bottom: 12px;
            border-bottom: 3px solid #000;
            padding-bottom: 8px;
        }
        .kop-surat table {
            width: 100%;
            border-collapse: collapse;
        }
        .kop-logo {
            width: 80px;
            vertical-align: top;
        }
        .kop-logo img {
            width: 75px;
            height: auto;
        }
        .kop-text {
            vertical-align: top;
            text-align: center;
        }
        .kop-nama-daerah {
            font-family: Arial, sans-serif;
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
            line-height: 1.2;
        }
        .kop-nama-dinas {
            font-family: Arial, sans-serif;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 2px 0;
            line-height: 1.3;
        }
        .kop-alamat {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            margin: 0px 0;
            line-height: 1.3;
        }

        /* JUDUL SURAT */
        .judul-section {
            text-align: center;
            margin: 2px 0;
        }
        .judul-surat {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 2px 0;
        }
        .nomor-surat {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin: 0 0 2px 0;
        }
        .tentang {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            font-weight: bold;
            margin: 0;
            line-height: 1.4;
        }

        /* DASAR HUKUM */
        .dasar-hukum {
            margin: 2px 0 0px 2px;
        }
        .dasar-hukum table {
            width: 100%;
        }
        .dasar-hukum td {
            vertical-align: top;
            padding: 2px 0px;
        }
        .dasar-hukum-label {
            width: 60px;
            font-weight: ;
        }
        .dasar-hukum-list {
            margin: 0;
            padding-left: 25px;
        }
        .dasar-hukum-list li {
            margin-bottom: 0px;
            text-align: justify;
        }

        /* MENUGASKAN */
        .menugaskan-section {
            margin: 2px 0;
        }
        .menugaskan-title {
            font-weight: ;
            text-align: center;
            margin-top: 2px;
            margin-bottom: 2px;
        }
        .data-pegawai {
            margin-bottom: 8px;
        }
        .data-pegawai table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-pegawai td {
            padding: 2px 0;
            vertical-align: top;
        }
        .data-label {
            width: 120px;
        }
        .data-label-2 {
            width: 30px;
        }

        /* KETENTUAN */
        .ketentuan-section {
            margin: 6px 0 6px 150px;
        }
        .ketentuan-list {
            margin: 0px 0 0 0;
            padding-left: 0px;
        }
        .ketentuan-list li {
            margin-bottom: 2px;
            text-align: justify;
        }

        /* TTD & QR */
        .ttd-section {
            margin-top: 18px;
        }
        .ttd-section table {
            width: 100%;
            border-collapse: collapse;
        }
        .ttd-section td {
            vertical-align: top;
        }
        .qr-td {
            width: 35%;
            text-align: center;
        }
        .qr-label {
            font-size: 10pt;
            margin-bottom: 4px;
        }
        .ttd-td {
            width: 65%;
            text-align: center;
        }
        .ttd-tempat {
            margin-bottom: 2px;
        }
        .ttd-jabatan {
            font-weight: bold;
            margin: 18px 0 2px 0;
            line-height: 1.3;
        }
        .ttd-nama {
            margin-bottom: 2px;
        }
        .ttd-nip {
            margin: 0;
        }

        /* Hide preview elements when printing/exporting to PDF */
        @media print {
            .preview-wrapper {
                background: white !important;
                padding: 0;
                gap: 0;
                min-height: auto;
            }
            .preview-wrapper .preview-page::after,
            .preview-wrapper .preview-page:not(:last-child)::before {
                display: none;
            }
            .preview-wrapper .preview-page {
                box-shadow: none;
            }
            body {
                background: white !important;
            }
        }
    </style>
</head>
<body>
    <!-- PREVIEW WRAPPER: Only for browser preview -->
    <div class="preview-wrapper {{ $isPreview ?? false ? 'is-preview' : '' }}">

        <!-- PAGE 1 -->
        <div class="preview-page">

            <!-- CONTENT WRAPPER -->
            <div class="content-wrapper">

                {{-- Watermark --}}
                @if(isset($bgBase64) && $bgBase64)
                    <div class="watermark-bg"></div>
                @endif

                <div class="content-inner">

                {{-- KOP SURAT --}}
                <div class="kop-surat">
                    <table>
                        <tr>
                            <td class="kop-logo">
                                @if(isset($logoBase64))
                                    <img src="{{ $logoBase64 }}" alt="Logo" style="width: 100px; height: auto; margin-top:10px;">
                                @endif
                            </td>
                            <td class="kop-text">
                                <div class="" style="font-family: Arial, sans-serif; font-size:15px;">PEMERINTAH KABUPATEN SUKABUMI</div>
                                <div class="kop-nama-dinas" style="font-size:25px;">
                                    BADAN KEPEGAWAIAN DAN PENGEMBANGAN<br>
                                    SUMBER DAYA MANUSIA
                                </div>
                                <div class="kop-alamat">
                                    Jalan Raya Kadupugur Km.10,4 Cicantayan, Sukabumi, Kode Pos 43155<br>
                                    Telepon: (0266) 531872 &nbsp; Faksimil: (0266) 6545141
                                </div>
                                <div class="kop-alamat">
                                    Laman: www.bkpsdm.sukabumikab.go.id &nbsp; Email: bkpsdm@sukabumikab.go.id
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                {{-- JUDUL SURAT --}}
                <div class="judul-section">
                    <div class="judul-surat">SURAT TUGAS</div>
                    <div class="nomor-surat">Nomor: {{ $surat->nomor_surat }}</div>
                    <div class="tentang">
                        TENTANG<br>
                        BELAJAR MANDIRI TIDAK DIBERHENTIKAN DARI JABATAN<br>
                        JENJANG PENDIDIKAN {{ $surat->pengajuan->jenjang->nama_jenjang ?? 'S1' }}
                    </div>
                </div>

                {{-- DASAR HUKUM --}}
                <div class="dasar-hukum">
                    <table>
                        <tr>
                            <td class="dasar-hukum-label">Dasar:</td>
                            <td>
                                <ol class="dasar-hukum-list">
                                    <li>Undang-Undang Nomor 20 Tahun 2003 tentang Sistem Pendidikan Nasional;</li>
                                    <li>Undang-Undang Nomor 20 Tahun 2023 tentang Aparatur Sipil Negara;</li>
                                    <li>Peraturan Pemerintah Republik Indonesia Nomor 17 Tahun 2020 tentang Manajemen Pegawai Negeri Sipil;</li>
                                    <li>Peraturan Daerah Kabupaten Sukabumi Nomor 3 Tahun 2024 tentang Pembentukan dan Susunan Perangkat Daerah;</li>
                                    <li>Peraturan Bupati Sukabumi Nomor 2 Tahun 2022 tentang Pedoman Pelaksanaan Tugas Belajar Mandiri Bagi Pegawai Negeri Sipil Di Lingkungan Pemerintah Kabupaten Sukabumi;</li>
                                    <li>Surat Izin Belajar Mandiri Nomor: {{ $surat->pengajuan->suratIzinBelajar->nomor_surat ?? '-' }} tanggal {{ $surat->pengajuan->suratIzinBelajar->signed_at ? \Carbon\Carbon::parse($surat->pengajuan->suratIzinBelajar->signed_at)->locale('id')->translatedFormat('d F Y') : '-' }} Perihal Izin Belajar Mandiri.</li>
                                </ol>
                            </td>
                        </tr>
                    </table>
                </div>

                {{-- MENUGASKAN --}}
                <div class="menugaskan-section">
                    <div class="menugaskan-title">MENUGASKAN:</div>

                    <div class="data-pegawai">
                        <table>
                            <tr>
                                <td class="data-label"></td>
                                <td class="data-label-2">Kepada</td>
                                <td>:</td>
                                <td><strong>{{ $surat->pengajuan->user->name }}</strong></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>NIP</td>
                                <td>:</td>
                                <td>{{ $surat->pengajuan->user->nip }}</td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>Pangkat/Golongan</td>
                                <td>:</td>
                                <td>{{ $surat->pengajuan->user->pangkat_gol ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>Jabatan</td>
                                <td>:</td>
                                <td>{{ $surat->pengajuan->user->jabatan ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>

                    <table style="width: 100%;">
                        <tr>
                            <td style="width: 60px; vertical-align: top;">Untuk</td>
                            <td>
                                Mengikuti Pendidikan {{ $surat->pengajuan->jenjang->nama_jenjang ?? '-' }} Program Studi {{ $surat->pengajuan->nama_prodi }} pada {{ $surat->pengajuan->perguruan_tinggi }}, dengan ketentuan sebagai berikut:
                            </td>
                        </tr>
                    </table>

                    <div class="ketentuan-section">
                        <ol class="ketentuan-list">
                            <li>Tugas mengikuti pendidikan diberikan di luar jam kerja;</li>
                            <li>Tidak mengganggu tugas-tugas kedinasan;</li>
                            <li>Pendidikan yang diikuti harus sesuai dengan norma dan kaidah akademik yang berlaku;</li>
                            <li>Biaya pendidikan sepenuhnya ditanggung oleh yang bersangkutan;</li>
                            <li>Tidak menuntut penyesuaian kenaikan pangkat dan pengakuan gelar akademik kecuali formasi yang tersedia memungkinkan.</li>
                        </ol>
                    </div>

                    <div style="margin-top: 8px; font-style: italic;">
                        Demikian Surat Tugas ini dibuat untuk dilaksanakan dengan penuh tanggung jawab.
                    </div>
                </div>

                {{-- TTD & QR CODE --}}
                <div class="ttd-section">
                    <table>
                        <tr>
                            <td class="qr-td">
                                <div class="qr-label">Verifikasi QR Code</div>
                                @if(isset($qrCodeBase64) && $qrCodeBase64)
                                    <img src="{{ $qrCodeBase64 }}" alt="QR Code" style="width: 80px; height: 80px;">
                                @endif
                            </td>
                            <td class="ttd-td">
                                <div class="ttd-tempat">
                                    Ditetapkan di {{ $surat->tempat_ttd ?? 'Sukabumi' }}<br>
                                    pada tanggal {{ \Carbon\Carbon::parse($surat->tanggal_ttd)->locale('id')->translatedFormat('d F Y') }}
                                </div>
                                <div class="ttd-jabatan">
                                    KEPALA BADAN KEPEGAWAIAN DAN<br>
                                    PENGEMBANGAN SUMBER DAYA MANUSIA
                                </div>
                                <div class="ttd-nama">{{ $surat->kepalaDinas->name ?? 'Kepala BKPSDM' }}</div>
                                <div class="ttd-nip">NIP. {{ $surat->kepalaDinas->nip ?? '-' }}</div>
                            </td>
                        </tr>
                    </table>
                </div>

                </div><!-- End content-inner -->
            </div><!-- End content-wrapper -->

        </div><!-- End preview-page PAGE 1 -->

    </div><!-- End preview-wrapper -->
</body>
</html>
