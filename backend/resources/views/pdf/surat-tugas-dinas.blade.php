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
        body {
            font-family: Arial, 'Times New Roman', serif;
            font-size: 11pt;
            line-height: 1.3;
            margin: 0;
            padding: 0;
            color: #000000;
        }
        .kop-container {
            text-align: center;
            margin-bottom: 10mm;
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
            margin: 10mm 0 8mm 0;
        }
        .judul-surat h2 {
            font-family: Arial, sans-serif;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
        }
        .nomor-surat {
            text-align: center;
            font-family: Arial, sans-serif;
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 10mm;
        }
        .content {
            text-align: justify;
            margin-bottom: 8mm;
        }
        .content p {
            margin: 0 0 3mm 0;
        }
        .data-pegawai {
            margin-left: 15mm;
            margin-bottom: 8mm;
        }
        .data-pegawai table {
            border-collapse: collapse;
        }
        .data-pegawai td {
            padding: 2mm 0;
            vertical-align: top;
        }
        .data-pegawai td:first-child {
            font-family: Arial, sans-serif;
            width: 150px;
        }
        .data-pegawai td:nth-child(2) {
            width: 5px;
        }
        .ketentuan {
            margin-left: 15mm;
            margin-bottom: 8mm;
            text-align: justify;
        }
        .ketentuan-title {
            font-family: Arial, sans-serif;
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
            font-family: Arial, sans-serif;
            font-weight: bold;
            font-size: 10pt;
        }
        .ttd-nama {
            margin-bottom: 2mm;
            font-family: Arial, sans-serif;
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
            font-family: Arial, sans-serif;
            font-weight: bold;
        }
        .separator {
            border-top: 2pt solid #000000;
            margin: 8mm 0;
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
    </style>
</head>
<body>
    <!-- KOP SURAT -->
    <div class="kop-container">
        <div class="kop-line-top"></div>
        <div class="kop-line-bottom">
            <div class="kop-nama-daerah">PEMERINTAH KABUPATEN SUKABUMI</div>
            <div class="kop-nama-dinas">{{ $surat->unitKerja->nama ?? 'DINAS' }}</div>
            @if($surat->unitKerja->alamat || $surat->unitKerja->telepon)
            <div class="kop-alamat">
                @if($surat->unitKerja->alamat){{ $surat->unitKerja->alamat }}@endif
                @if($surat->unitKerja->telepon) Telp: {{ $surat->unitKerja->telepon }}@endif
                @if($surat->unitKerja->email) Email: {{ $surat->unitKerja->email }}@endif
            </div>
            @endif
        </div>
    </div>

    <!-- JUDUL SURAT -->
    <div class="judul-surat">
        <h2>SURAT TUGAS BELAJAR MANDIRI</h2>
    </div>

    <div class="nomor-surat">
        Nomor: {{ $surat->nomor_surat }}/DK/{{ $surat->bulan }}/{{ $surat->tahun }}
    </div>

    <!-- ISI SURAT -->
    <div class="content">
        <p>
            Dalam rangka pengembangan kompetensi sumber daya manusia melalui jalur pendidikan,
            Kepala Dinas {{ $surat->unitKerja->nama }} dengan ini memberikan Tugas Belajar Mandiri
            (TIDAK DIBERHENTIKAN dari jabatan) kepada pegawai:
        </p>
    </div>

    <!-- DATA PEGAWAI -->
    <div class="data-pegawai">
        <table>
            <tr>
                <td>Nama/NIP</td>
                <td>:</td>
                <td><strong>{{ $surat->pengajuan->user->name }}</strong> / {{ $surat->pengajuan->user->nip }}</td>
            </tr>
            <tr>
                <td>Pangkat/Golongan</td>
                <td>:</td>
                <td>{{ $surat->pengajuan->user->pangkat_gol }}</td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td>:</td>
                <td>{{ $surat->pengajuan->user->jabatan }}</td>
            </tr>
            <tr>
                <td>Unit Organisasi</td>
                <td>:</td>
                <td>Dinas {{ $surat->unitKerja->nama }}</td>
            </tr>
        </table>
    </div>

    <!-- PROGRAM STUDI -->
    <div class="content">
        <p>Untuk melaksanakan:</p>
        <div class="data-pegawai">
            <table>
                <tr>
                    <td>Program Studi</td>
                    <td>:</td>
                    <td><strong>{{ $surat->pengajuan->nama_prodi }}</strong></td>
                </tr>
                <tr>
                    <td>Perguruan Tinggi</td>
                    <td>:</td>
                    <td><strong>{{ $surat->pengajuan->perguruan_tinggi }}</strong>, {{ $surat->pengajuan->lokasi_pt }}</td>
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

    <div class="separator"></div>

    <!-- KETENTUAN -->
    <div class="ketentuan">
        <p class="ketentuan-title">Ketentuan:</p>
        <ol>
            <li>Pelaksanaan pendidikan dilaksanakan di luar jam kerja.</li>
            <li>Tidak mengganggu tugas-tugas kedinasan.</li>
            <li>Wajib melaporkan perkembangan studi setiap semester.</li>
            <li>Setelah selesai studi, wajib kembali bertugas di lingkungan Pemerintah Daerah.</li>
        </ol>
    </div>

    <!-- PENUTUP -->
    <div class="penutup">
        <p>
            Demikian Surat Tugas Belajar Mandiri ini dibuat untuk dilaksanakan dengan penuh
            tanggung jawab.
        </p>
    </div>

    <!-- TANDA TANGAN -->
    <div class="ttd-container">
        <div class="ttd-tempat">
            {{ $surat->tempat_ttd ?? 'Sukabumi' }}, {{ \Carbon\Carbon::parse($surat->tanggal_ttd)->locale('id')->translatedFormat('d F Y') }}
        </div>
        <div class="ttd-jabatan">Kepala Dinas {{ $surat->unitKerja->nama }}</div>
        <div class="ttd-nama">{{ $surat->kepalaDinas->name }}</div>
        <div class="ttd-nip">NIP. {{ $surat->kepalaDinas->nip }}</div>
    </div>

    <!-- TEMBUSAN -->
    <div class="tembusan">
        <p class="tembusan-title">Tembusan:</p>
        <p>1. Pimpinan OPD terkait</p>
        <p>2. Unit yang membawahi urusan kepegawaian</p>
    </div>

    <!-- QR CODE -->
    @if(isset($qrCodePath) && $qrCodePath)
    <div class="qr-section">
        <p class="qr-label">Scan QR Code untuk verifikasi keaslian surat</p>
        <img src="{{ asset('storage/' . $qrCodePath) }}" alt="QR Code" class="qr-code">
    </div>
    @endif
</body>
</html>
