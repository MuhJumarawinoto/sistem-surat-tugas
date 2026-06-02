<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat Tugas Belajar Mandiri</title>
    <style>
        body {
            font-family: Times New Roman, serif;
            font-size: 12pt;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header-line {
            border-top: 3px solid #000;
            border-bottom: 1px solid #000;
            padding: 10px 0;
        }
        .header-title {
            font-weight: bold;
            font-size: 14pt;
        }
        .content {
            margin-top: 30px;
        }
        .content-title {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 20px;
        }
        .section {
            margin-bottom: 15px;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 5px 0;
            vertical-align: top;
        }
        .info-label {
            width: 150px;
        }
        .numbering {
            margin-left: 25px;
        }
        .signature {
            margin-top: 50px;
            text-align: right;
            padding-right: 50px;
        }
        .signature-space {
            height: 80px;
        }
        @page {
            margin: 20mm 20mm 20mm 20mm;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-line">
            <div class="header-title">PEMERINTAH KABUPATEN SUKABUMI</div>
            <div class="header-title">BADAN KEPEGAWAIAN DAN PENGEMBANGAN SUMBER DAYA MANUSIA</div>
            <div style="font-size: 10pt; margin-top: 5px;">Jalan Raya Sukabumi No. 12 Telp. (0266) 123456</div>
        </div>
    </div>

    <div class="content">
        <div class="content-title">
            SURAT TUGAS<br>
            NOMOR: {{ $nomor_surat }}
        </div>

        <div class="section">
            <table class="info-table">
                <tr>
                    <td colspan="3">Yang bertanda tangan di bawah ini Kepala Badan Kepegawaian dan Pengembangan Sumber Daya Manusia Kabupaten Sukabumi, menugaskan:</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <table class="info-table">
                <tr>
                    <td class="info-label">Nama</td>
                    <td>: {{ $pengajuan->user->name }}</td>
                </tr>
                <tr>
                    <td class="info-label">NIP</td>
                    <td>: {{ $pengajuan->user->nip ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Pangkat/Golongan</td>
                    <td>: {{ $pengajuan->user->pangkat_gol ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Jabatan</td>
                    <td>: {{ $pengajuan->user->jabatan ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Unit Kerja</td>
                    <td>: {{ $pengajuan->user->unitKerja->nama ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <p>Untuk mengikuti pendidikan:</p>
            <table class="info-table" style="margin-top: 10px;">
                <tr>
                    <td class="info-label">Jenjang</td>
                    <td>: {{ $pengajuan->jenjang->nama_jenjang ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="info-label">Program Studi</td>
                    <td>: {{ $pengajuan->nama_prodi }}</td>
                </tr>
                <tr>
                    <td class="info-label">Perguruan Tinggi</td>
                    <td>: {{ $pengajuan->perguruan_tinggi }}</td>
                </tr>
            </table>
        </div>

        <div class="section" style="margin-top: 20px;">
            <p><strong>Ketentuan:</strong></p>
            <div class="numbering">
                <p>1. Tugas mengikuti pendidikan diberikan di luar jam kerja;</p>
                <p>2. Tidak mengganggu tugas-tugas kedinasan;</p>
                <p>3. Pendidikan yang diikuti harus sesuai norma dan kaidah akademik;</p>
                <p>4. Biaya pendidikan sepenuhnya ditanggung oleh yang bersangkutan; dan</p>
                <p>5. Tidak menuntut penyesuaian kenaikan pangkat dan pengakuan gelar akademik kecuali formasi memungkinkan.</p>
            </div>
        </div>

        <div class="section" style="margin-top: 20px;">
            <p>Demikian surat tugas ini dibuat untuk dilaksanakan dengan penuh tanggung jawab.</p>
        </div>

        <div class="signature">
            <p>Ditetapkan di: Sukabumi</p>
            <p>Pada Tanggal: {{ $tanggal_terbit }}</p>
            <div class="signature-space"></div>
            <p><strong>Kepala BKPSDM</strong></p>
            <p>NIP. ................................</p>
        </div>
    </div>
</body>
</html>
