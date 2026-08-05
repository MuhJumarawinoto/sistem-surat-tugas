<?php

namespace Database\Seeders;

use App\Models\JenisDokumenPga;
use Illuminate\Database\Seeder;

class JenisDokumenPgaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dokumen = [
            [
                'kode' => 'ijazah_file',
                'nama' => 'Ijazah',
                'deskripsi' => 'Ijazah legalisir',
                'is_wajib' => true,
                'urutan' => 1,
                'format_nama' => 'IJAZAH_NIP',
                'catatan' => 'File ijazah legalisir (PDF/JPG/PNG, maks 5MB)',
                'is_active' => true,
            ],
            [
                'kode' => 'transkrip_file',
                'nama' => 'Transkrip Nilai',
                'deskripsi' => 'Transkrip nilai lengkap',
                'is_wajib' => true,
                'urutan' => 2,
                'format_nama' => 'TRANSKRIP_NIP',
                'catatan' => 'File transkrip nilai lengkap (PDF/JPG/PNG, maks 5MB)',
                'is_active' => true,
            ],
            [
                'kode' => 'sk_kum_file',
                'nama' => 'SK Kenaikan Pangkat/Golongan Terakhir',
                'deskripsi' => 'Surat Keputusan Kenaikan Pangkat/Golongan Terakhir',
                'is_wajib' => true,
                'urutan' => 3,
                'format_nama' => 'SK_KUM_NIP',
                'catatan' => 'File SK Kum legalisir (PDF/JPG/PNG, maks 5MB)',
                'is_active' => true,
            ],
        ];

        foreach ($dokumen as $doc) {
            JenisDokumenPga::updateOrCreate(
                ['kode' => $doc['kode']],
                $doc
            );
        }
    }
}
