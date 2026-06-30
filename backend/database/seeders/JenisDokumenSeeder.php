<?php

namespace Database\Seeders;

use App\Models\JenisDokumen;
use Illuminate\Database\Seeder;

class JenisDokumenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dokumen = [
            [
                'kode' => 'sk_pangkat',
                'nama' => 'SK Pangkat Terakhir legalisir',
                'deskripsi' => 'SK Pangkat/Golongan terakhir yang sudah legalisir',
                'is_wajib' => true,
                'urutan' => 1,
            ],
            [
                'kode' => 'sk_cpns',
                'nama' => 'SK CPNS legalisir',
                'deskripsi' => 'SK CPNS pertama kali diangkat',
                'is_wajib' => true,
                'urutan' => 2,
            ],
            [
                'kode' => 'skp',
                'nama' => 'SKP 2 Tahun Terakhir',
                'deskripsi' => 'SKP 2 tahun terakhir (tahun berjalan dan tahun sebelumnya)',
                'is_wajib' => true,
                'urutan' => 3,
            ],
            [
                'kode' => 'surat_lulus',
                'nama' => 'Surat Keterangan Lulus/Diterima dari PT',
                'deskripsi' => 'Surat Keterangan Lulus (SKL) atau Surat Diterima',
                'is_wajib' => true,
                'urutan' => 4,
            ],
            [
                'kode' => 'jadwal',
                'nama' => 'Jadwal Perkuliahan',
                'deskripsi' => 'Jadwal kuliah semester yang akan diikuti',
                'is_wajib' => true,
                'urutan' => 5,
            ],
            [
                'kode' => 'akreditasi',
                'nama' => 'Sertifikat Akreditasi Prodi (min C)',
                'deskripsi' => 'Sertifikat akreditasi program studi, minimal akreditasi C',
                'is_wajib' => true,
                'urutan' => 6,
            ],
            [
                'kode' => 'surat_mandiri',
                'nama' => 'Surat Pernyataan Biaya Mandiri',
                'deskripsi' => 'Surat pernyataan bermaterai 10.000 bahwa biaya kuliah mandiri',
                'is_wajib' => true,
                'urutan' => 7,
            ],
            [
                'kode' => 'surat_ijazah',
                'nama' => 'Surat Pernyataan Tidak Menuntut Ijazah',
                'deskripsi' => 'Surat pernyataan bermaterai 10.000 tidak menuntut penyerahan ijazah',
                'is_wajib' => true,
                'urutan' => 8,
            ],
            [
                'kode' => 'surat_sehat',
                'nama' => 'Surat Keterangan Sehat',
                'deskripsi' => 'Surat keterangan sehat dari dokter/Puskesmas/RS',
                'is_wajib' => true,
                'urutan' => 9,
            ],
        ];

        foreach ($dokumen as $doc) {
            JenisDokumen::updateOrCreate(
                ['kode' => $doc['kode']],
                $doc
            );
        }
    }
}
