<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Surat Tugas Belajar Mandiri</title>
    <style>
        @page {
            size: A4;
            margin: 20mm 20mm 15mm 25mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            color: #000;
            background: #fff;
        }

        .page {
            width: 100%;
            padding: 20mm 20mm 15mm 25mm;
        }

        /* ── HEADER ── */
        .header-table {
            width: 100%;
            border-bottom: 3px solid #000;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }

        .header-table td {
            vertical-align: middle;
        }

        .header-logo {
            width: 70px;
            text-align: center;
        }

        .header-logo img {
            width: 65px;
            height: auto;
        }

        .header-text {
            text-align: center;
            padding: 0 8px;
        }

        .header-text .instansi {
            font-size: 11pt;
            font-weight: normal;
        }

        .header-text .nama-badan {
            font-size: 14pt;
            font-weight: bold;
            line-height: 1.3;
        }

        .header-text .alamat {
            font-size: 8.5pt;
            line-height: 1.5;
        }

        /* ── JUDUL SURAT ── */
        .judul-surat {
            text-align: center;
            margin: 14px 0 10px 0;
        }

        .judul-surat .label-surat {
            font-size: 12pt;
            font-weight: bold;
        }

        .judul-surat .nomor-surat {
            font-size: 12pt;
        }

        .judul-surat .tentang {
            font-size: 11pt;
        }

        .judul-surat .perihal {
            font-size: 12pt;
            font-weight: bold;
            line-height: 1.4;
        }

        /* ── BODY / ISI SURAT ── */
        .isi-surat {
            width: 100%;
            margin-top: 10px;
        }

        .isi-surat td {
            vertical-align: top;
            padding: 2px 0;
        }

        .col-label {
            width: 70px;
            font-weight: normal;
        }

        .col-titik-dua {
            width: 18px;
            text-align: center;
        }

        .col-value {
            /* flex fill */
        }

        /* Dasar hukum numbered list */
        .dasar-list {
            width: 100%;
            border-collapse: collapse;
        }

        .dasar-list td {
            vertical-align: top;
            padding: 2px 0;
        }

        .dasar-num {
            width: 20px;
        }

        .dasar-text {
            text-align: justify;
        }

        /* Menugaskan */
        .menugaskan {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            margin: 12px 0 6px 0;
        }

        /* Kepada block */
        .kepada-table {
            width: 100%;
            border-collapse: collapse;
        }

        .kepada-table td {
            vertical-align: top;
            padding: 2px 0;
        }

        .kpd-label {
            width: 120px;
        }

        .kpd-titik {
            width: 18px;
            text-align: left;
        }

        .kpd-isi {
            /* fill */
        }

        /* Untuk & ketentuan */
        .untuk-block {
            margin-top: 6px;
            text-align: justify;
        }

        .ketentuan-list {
            margin-top: 4px;
            padding-left: 0;
        }

        .ketentuan-item {
            display: flex;
            gap: 6px;
            margin-bottom: 2px;
        }

        .ket-num {
            min-width: 18px;
        }

        /* TTD block */
        .ttd-wrapper {
            margin-top: 20px;
            width: 100%;
        }

        .ttd-table {
            width: 100%;
        }

        .ttd-table td {
            vertical-align: top;
        }

        .ttd-kiri {
            width: 40%;
            vertical-align: bottom;
        }

        .ttd-kanan {
            width: 60%;
            text-align: center;
        }

        .ttd-kanan .kota-tanggal {
            margin-bottom: 4px;
        }

        .ttd-kanan .jabatan-ttd {
            font-weight: bold;
            font-size: 11pt;
            line-height: 1.4;
        }

        .ttd-kanan .nama-ttd {
            margin-top: 60px;
            font-weight: bold;
            text-decoration: underline;
        }

        .ttd-kanan .nip-ttd {
            font-size: 11pt;
        }

        /* QR placeholder */
        .qr-placeholder {
            width: 70px;
            height: 70px;
            border: 1px solid #888;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8pt;
            color: #888;
            margin-top: 10px;
        }

        /* Footer */
        .footer-bsre {
            border-top: 1px solid #000;
            margin-top: 16px;
            padding-top: 6px;
            font-size: 7.5pt;
            text-align: center;
            color: #333;
        }

        .footer-bsre table {
            width: 100%;
        }

        .footer-bsre td {
            vertical-align: middle;
        }

        .footer-logo-bsre {
            width: 40px;
        }

        .footer-logo-bsre img {
            width: 35px;
        }

        .garis-bawah {
            border-bottom: 1px solid #000;
            margin: 4px 0;
        }

        .text-justify {
            text-align: justify;
        }

        .bold { font-weight: bold; }
        .underline { text-decoration: underline; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
<div class="page">

    {{-- ══════════════════════════════════════════
         KOP SURAT
    ══════════════════════════════════════════ --}}
    <table class="header-table" cellspacing="0" cellpadding="0">
        <tr>
            <td class="header-logo">
                {{-- Logo Kabupaten Sukabumi --}}
                @if(isset($logo_path) && file_exists($logo_path))
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logo_path)) }}" alt="Logo Sukabumi">
                @else
                    {{-- Placeholder jika logo tidak tersedia --}}
                    <div style="width:65px;height:70px;border:1px solid #ccc;display:flex;align-items:center;justify-content:center;font-size:7pt;color:#999;">LOGO</div>
                @endif
            </td>
            <td class="header-text">
                <div class="instansi">PEMERINTAH KABUPATEN SUKABUMI</div>
                <div class="nama-badan">BADAN KEPEGAWAIAN DAN PENGEMBANGAN<br>SUMBER DAYA MANUSIA</div>
                <div class="alamat">
                    Jalan Raya Kadupugur Km.10,4 Cicantayan, Sukabumi, Kode Pos 43155<br>
                    Telepon : (0266) 531872 &nbsp; Faksimil : (0266) 6545141<br>
                    Laman : www.bkpsdm.sukabumikab.go.id &nbsp; Pos-el : bkpsdm@sukabumikab.go.id
                </div>
            </td>
        </tr>
    </table>

    {{-- ══════════════════════════════════════════
         JUDUL SURAT
    ══════════════════════════════════════════ --}}
    <div class="judul-surat">
        <div class="label-surat">SURAT TUGAS</div>
        <div class="nomor-surat">NOMOR : {{ $surat->nomor_surat ?? '800.1.3.1/.../TBM/...' }}/{{ $surat->tahun ?? '....' }}</div>
        <div class="tentang">TENTANG</div>
        <div class="perihal">
            BELAJAR MANDIRI TIDAK DIBERHENTIKAN DARI JABATAN<br>
            JENJANG {{ strtoupper($surat->pengajuan->jenjang->nama_jenjang ?? '.................') }}
        </div>
    </div>

    {{-- ══════════════════════════════════════════
         DASAR HUKUM
    ══════════════════════════════════════════ --}}
    <table class="isi-surat" cellspacing="0" cellpadding="0">
        <tr>
            <td class="col-label" style="padding-top:4px;">Dasar</td>
            <td class="col-titik-dua" style="padding-top:4px;">:</td>
            <td class="col-value">
                <table class="dasar-list" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="dasar-num">1.</td>
                        <td class="dasar-text">Undang-Undang Nomor 20 Tahun 2003 tentang Sistem Pendidikan Nasional;</td>
                    </tr>
                    <tr>
                        <td class="dasar-num">2.</td>
                        <td class="dasar-text">Undang-Undang Nomor 20 Tahun 2023 tentang Aparatur Sipil Negara;</td>
                    </tr>
                    <tr>
                        <td class="dasar-num">3.</td>
                        <td class="dasar-text text-justify">Peraturan Pemerintah Nomor 17 Tahun 2020 tentang Perubahan Atas Peraturan Pemerintah Nomor 11 Tahun 2017 tentang Manajemen Pegawai Negeri Sipil;</td>
                    </tr>
                    <tr>
                        <td class="dasar-num">4.</td>
                        <td class="dasar-text text-justify">Peraturan Daerah Kabupaten Sukabumi Nomor 3 Tahun 2024 tentang Perubahan Ketiga Atas Peraturan Daerah Kabupaten Sukabumi Nomor 7 Tahun 2016 tentang Pembentukan dan Susunan Perangkat Daerah Pemerintah Kabupaten Sukabumi;</td>
                    </tr>
                    <tr>
                        <td class="dasar-num">5.</td>
                        <td class="dasar-text text-justify">Peraturan Bupati Sukabumi Nomor 2 Tahun 2022 Tentang Pedoman Tugas Belajar dan Pencantuman Gelar Akademik Bagi Pegawai Negeri Sipil Di Lingkungan Pemerintah Kabupaten Sukabumi;</td>
                    </tr>
                    {{-- Referensi ke Surat Izin Belajar --}}
                    @if($surat->suratIzinBelajar)
                    <tr>
                        <td class="dasar-num">6.</td>
                        <td class="dasar-text text-justify">
                            Surat Izin Belajar Mandiri Badan Kepegawaian dan Pengembangan Sumber Daya Manusia
                            Nomor: {{ $surat->suratIzinBelajar->nomor_surat ?? '....................' }}
                            tanggal {{ $surat->suratIzinBelajar->signed_at ? \Carbon\Carbon::parse($surat->suratIzinBelajar->signed_at)->translatedFormat('d F Y') : '...........................' }}
                            perihal Izin Belajar Mandiri
                        </td>
                    </tr>
                    @endif
                    {{-- Referensi ke Surat Tugas Dinas (jika ada) --}}
                    @if($surat->suratTugasDinas)
                    <tr>
                        <td class="dasar-num">7.</td>
                        <td class="dasar-text text-justify">
                            Surat Kepala {{ $surat->suratTugasDinas->unitKerja->nama ?? '................' }}
                            Nomor: {{ $surat->suratTugasDinas->nomor_surat ?? '..........' }}/DK/{{ $surat->suratTugasDinas->bulan ?? '...' }}/{{ $surat->suratTugasDinas->tahun ?? '....' }}
                            tanggal {{ $surat->suratTugasDinas->tanggal_ttd ? \Carbon\Carbon::parse($surat->suratTugasDinas->tanggal_ttd)->translatedFormat('d F Y') : '...........................' }}
                        </td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- ══════════════════════════════════════════
         MENUGASKAN
    ══════════════════════════════════════════ --}}
    <div class="menugaskan">MENUGASKAN :</div>

    {{-- ══════════════════════════════════════════
         KEPADA
    ══════════════════════════════════════════ --}}
    <table class="kepada-table" cellspacing="0" cellpadding="0">
        <tr>
            <td class="kpd-label">Kepada</td>
            <td class="kpd-titik">:</td>
            <td class="kpd-isi">
                <table cellspacing="0" cellpadding="0">
                    <tr>
                        <td style="width:140px;">Nama</td>
                        <td style="width:14px;">:</td>
                        <td>{{ $surat->pengajuan->user->name ?? '........................................' }}</td>
                    </tr>
                    <tr>
                        <td>NIP.</td>
                        <td>:</td>
                        <td>{{ $surat->pengajuan->user->nip ?? '......................................' }}</td>
                    </tr>
                    <tr>
                        <td>Pangkat/Gol.Ruang</td>
                        <td>:</td>
                        <td>{{ $surat->pengajuan->user->pangkat_gol ?? '.........................................' }}</td>
                    </tr>
                    <tr>
                        <td>Jabatan</td>
                        <td>:</td>
                        <td>{{ $surat->pengajuan->user->jabatan ?? '...........................' }} pada {{ $surat->pengajuan->user->unitKerja->nama ?? $surat->pengajuan->user->unit_kerja ?? '............................' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ══════════════════════════════════════════
         UNTUK
    ══════════════════════════════════════════ --}}
    <table class="kepada-table" style="margin-top:6px;" cellspacing="0" cellpadding="0">
        <tr>
            <td class="kpd-label" style="vertical-align:top;padding-top:2px;">Untuk</td>
            <td class="kpd-titik" style="vertical-align:top;padding-top:2px;">:</td>
            <td class="kpd-isi">
                <div class="text-justify">
                    Mengikuti Pendidikan Jenjang <span class="bold">{{ strtoupper($surat->pengajuan->jenjang->nama_jenjang ?? '.................') }}</span>
                    Program Studi <span class="bold">{{ $surat->pengajuan->nama_prodi ?? '.....................' }}</span> pada
                    <span class="bold">{{ $surat->pengajuan->perguruan_tinggi ?? '...............................................' }}</span>,
                    dengan ketentuan sebagai berikut:
                </div>
                <table cellspacing="0" cellpadding="2" style="margin-top:4px;width:100%;">
                    <tr>
                        <td style="width:20px;vertical-align:top;">1.</td>
                        <td>Tugas mengikuti pendidikan diberikan diluar jam kerja;</td>
                    </tr>
                    <tr>
                        <td style="vertical-align:top;">2.</td>
                        <td>Tidak mengganggu tugas-tugas kedinasan;</td>
                    </tr>
                    <tr>
                        <td style="vertical-align:top;">3.</td>
                        <td>Pendidikan yang diikuti harus sesuai norma dan kaidah akademik;</td>
                    </tr>
                    <tr>
                        <td style="vertical-align:top;">4.</td>
                        <td>Biaya Pendidikan sepenuhnya ditanggung oleh yang bersangkutan;</td>
                    </tr>
                    <tr>
                        <td style="vertical-align:top;">5.</td>
                        <td class="text-justify">Tidak menuntut penyesuaian kenaikan pangkat dan pengakuan gelar akademik kecuali formasi memungkinkan.</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ══════════════════════════════════════════
         TANDA TANGAN
    ══════════════════════════════════════════ --}}
    <div class="ttd-wrapper">
        <table class="ttd-table" cellspacing="0" cellpadding="0">
            <tr>
                <td class="ttd-kiri">
                    {{-- QR Code akan dirender di sini --}}
                    @if(isset($qrCodePath) && file_exists($qrCodePath))
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents($qrCodePath)) }}" width="70" height="70" alt="QR Code">
                    @else
                        <div class="qr-placeholder">QRCode</div>
                    @endif
                </td>
                <td class="ttd-kanan">
                    <div class="kota-tanggal">
                        Ditetapkan di {{ $surat->tempat_ttd ?? 'Sukabumi' }}<br>
                        pada tanggal {{ $surat->tanggal_surat ? \Carbon\Carbon::parse($surat->tanggal_surat)->translatedFormat('d F Y') : '.....................' }}
                    </div>
                    <div class="jabatan-ttd">
                        KEPALA BADAN KEPEGAWAIAN DAN<br>
                        PENGEMBANGAN SUMBER DAYA MANUSIA,
                    </div>
                    {{-- Tanda Tangan Elektronik --}}
                    @if($surat->status === 'signed' && isset($surat->tte_path))
                        <div style="margin-top:8px;">
                            {{-- TTE Path would be here --}}
                            {{-- <img src="{{ $surat->tte_path }}" height="50" alt="TTD"> --}}
                        </div>
                    @else
                        <div style="height:60px;"></div>
                    @endif
                    <div class="nama-ttd">{{ $surat->signed_by ?? '.......................................' }}</div>
                    <div class="nip-ttd">NIP. {{ $surat->signed_by_nip ?? '....................................' }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ══════════════════════════════════════════
         FOOTER BSrE
    ══════════════════════════════════════════ --}}
    @if($surat->status === 'signed')
    <div class="footer-bsre">
        <table cellspacing="0" cellpadding="0">
            <tr>
                <td class="footer-logo-bsre">
                    {{-- BSrE Logo --}}
                    @if(isset($logo_bsre_path) && file_exists($logo_bsre_path))
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logo_bsre_path)) }}" alt="BSrE">
                    @endif
                </td>
                <td style="padding-left:8px;text-align:left;">
                    Dokumen ini telah ditandatangani secara elektronik menggunakan Sertifikat Elektronik yang diterbitkan
                    oleh Balai Sertifikat Elektronik (BSrE) Badan Siber Sandi Negara (BSSN)
                </td>
            </tr>
        </table>
    </div>
    @endif

</div>
</body>
</html>
