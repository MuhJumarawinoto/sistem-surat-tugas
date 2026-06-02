<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Editor - Surat Izin Belajar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50">
    <div x-data="pdfEditor()" x-cloak>
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="/dashboard" class="text-gray-600 hover:text-gray-900">
                        <i class="ri-arrow-left-line text-xl"></i>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">PDF Editor</h1>
                        <p class="text-sm text-gray-500">Edit dan Preview Surat Izin Belajar</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="resetForm" class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 flex items-center gap-2">
                        <i class="ri-refresh-line"></i>
                        Reset
                    </button>
                    <button @click="generatePdf" :disabled="loadingPdf" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2 disabled:opacity-50">
                        <i class="ri-file-pdf-line" :class="loadingPdf ? 'animate-spin' : ''"></i>
                        <span x-text="loadingPdf ? 'Generating...' : 'Download PDF'"></span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto p-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Form Editor -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b bg-gray-50">
                        <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                            <i class="ri-edit-2-line text-blue-600"></i>
                            Edit Data Surat
                        </h2>
                    </div>
                    <div class="p-6 space-y-6 max-h-[calc(100vh-250px)] overflow-y-auto">
                        <!-- Data Surat -->
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2 pb-2 border-b">
                                <i class="ri-file-text-line text-blue-600"></i>
                                Data Surat
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Nomor Surat</label>
                                    <input type="text" x-model="form.nomor_surat" @input="updatePreview" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Tahun</label>
                                    <input type="text" x-model="form.tahun" @input="updatePreview" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Surat</label>
                                    <input type="text" x-model="form.tanggal_surat" @input="updatePreview" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Tempat TTD</label>
                                    <input type="text" x-model="form.tempat_ttd" @input="updatePreview" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- Data Pegawai -->
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2 pb-2 border-b">
                                <i class="ri-user-line text-blue-600"></i>
                                Data Pegawai
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Nama Pegawai</label>
                                    <input type="text" x-model="form.nama" @input="updatePreview" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">NIP</label>
                                    <input type="text" x-model="form.nip" @input="updatePreview" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Pangkat/Golongan</label>
                                    <input type="text" x-model="form.pangkat" @input="updatePreview" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Jabatan</label>
                                    <input type="text" x-model="form.jabatan" @input="updatePreview" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Unit Kerja</label>
                                    <input type="text" x-model="form.unit_kerja" @input="updatePreview" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- Data Pendidikan -->
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2 pb-2 border-b">
                                <i class="ri-graduation-cap-line text-blue-600"></i>
                                Data Pendidikan
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Jenjang</label>
                                    <input type="text" x-model="form.jenjang" @input="updatePreview" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Program Studi</label>
                                    <input type="text" x-model="form.nama_prodi" @input="updatePreview" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Perguruan Tinggi</label>
                                    <input type="text" x-model="form.perguruan_tinggi" @input="updatePreview" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Lokasi</label>
                                    <input type="text" x-model="form.lokasi_pt" @input="updatePreview" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- Data Surat Tugas Dinas -->
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2 pb-2 border-b">
                                <i class="ri-file-list-line text-blue-600"></i>
                                Data Surat Tugas Dinas
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Nomor Surat Dinas</label>
                                    <input type="text" x-model="form.nomor_surat_dinas" @input="updatePreview" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                                    <input type="date" x-model="form.tanggal_mulai" @input="updatePreview" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                                    <input type="date" x-model="form.tanggal_selesai" @input="updatePreview" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Dinas</label>
                                    <input type="text" x-model="form.dinas" @input="updatePreview" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Nama Kepala Dinas</label>
                                    <input type="text" x-model="form.nama_kepala_dinas" @input="updatePreview" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">NIP Kepala Dinas</label>
                                    <input type="text" x-model="form.nip_kepala_dinas" @input="updatePreview" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preview -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b bg-gray-50">
                        <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                            <i class="ri-eye-line text-blue-600"></i>
                            Live Preview
                        </h2>
                    </div>
                    <div class="p-0">
                        <iframe :src="previewUrl" class="w-full h-[calc(100vh-250px)] border-0"></iframe>
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <i class="ri-information-line text-blue-600 text-xl"></i>
                    <div>
                        <p class="text-sm font-medium text-blue-900">Cara Menggunakan PDF Editor</p>
                        <ul class="text-sm text-blue-700 mt-2 space-y-1">
                            <li>• Edit data di form sebelah kiri, preview akan otomatis terupdate</li>
                            <li>• Klik "Download PDF" untuk mengunduh file PDF</li>
                            <li>• Gunakan data ini sebagai referensi untuk mengedit template Blade</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        function pdfEditor() {
            return {
                form: {
                    nomor_surat: '800.1.3.1/001/BKPSDM/' + new Date().getFullYear(),
                    tahun: new Date().getFullYear().toString(),
                    tanggal_surat: new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }),
                    tempat_ttd: 'Sukabumi',
                    nama: 'Drajat Sukmana',
                    nip: '198505102015011001',
                    pangkat: 'Pembina (IV/a)',
                    jabatan: 'Kepala Seki',
                    unit_kerja: 'Sekretariat Badan Kepegawaian dan Pengembangan SDM',
                    jenjang: 'Magister (S2)',
                    nama_prodi: 'Magister Informatika',
                    perguruan_tinggi: 'Universitas BSI',
                    lokasi_pt: 'Kabupaten Sukabumi, Jawa Barat',
                    nomor_surat_dinas: '001/DK/Mei/' + new Date().getFullYear(),
                    tanggal_mulai: '2026-09-01',
                    tanggal_selesai: '2028-09-01',
                    dinas: 'Dinas Pendidikan',
                    nama_kepala_dinas: 'Kepala Dinas Pendidikan',
                    nip_kepala_dinas: '197001011995031001',
                },
                loadingPdf: false,
                previewUrl: '',

                init() {
                    this.updatePreview();
                },

                updatePreview() {
                    const params = new URLSearchParams();
                    Object.keys(this.form).forEach(key => {
                        if (this.form[key]) {
                            params.append(key, this.form[key]);
                        }
                    });
                    this.previewUrl = '/pdf-editor/preview?' + params.toString() + '&t=' + Date.now();
                },

                generatePdf() {
                    this.loadingPdf = true;
                    const params = new URLSearchParams();
                    Object.keys(this.form).forEach(key => {
                        if (this.form[key]) {
                            params.append(key, this.form[key]);
                        }
                    });
                    const url = '/pdf-editor/pdf?' + params.toString();
                    window.open(url, '_blank');
                    setTimeout(() => {
                        this.loadingPdf = false;
                    }, 1000);
                },

                resetForm() {
                    this.form = {
                        nomor_surat: '800.1.3.1/001/BKPSDM/' + new Date().getFullYear(),
                        tahun: new Date().getFullYear().toString(),
                        tanggal_surat: new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }),
                        tempat_ttd: 'Sukabumi',
                        nama: 'Drajat Sukmana',
                        nip: '198505102015011001',
                        pangkat: 'Pembina (IV/a)',
                        jabatan: 'Kepala Seki',
                        unit_kerja: 'Sekretariat Badan Kepegawaian dan Pengembangan SDM',
                        jenjang: 'Magister (S2)',
                        nama_prodi: 'Magister Informatika',
                        perguruan_tinggi: 'Universitas BSI',
                        lokasi_pt: 'Kabupaten Sukabumi, Jawa Barat',
                        nomor_surat_dinas: '001/DK/Mei/' + new Date().getFullYear(),
                        tanggal_mulai: '2026-09-01',
                        tanggal_selesai: '2028-09-01',
                        dinas: 'Dinas Pendidikan',
                        nama_kepala_dinas: 'Kepala Dinas Pendidikan',
                        nip_kepala_dinas: '197001011995031001',
                    };
                    this.updatePreview();
                }
            }
        }
    </script>
</body>
</html>
