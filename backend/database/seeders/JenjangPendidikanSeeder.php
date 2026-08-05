<?php

namespace Database\Seeders;

use App\Models\JenjangPendidikan;
use Illuminate\Database\Seeder;

class JenjangPendidikanSeeder extends Seeder
{
    public function run(): void
    {
        $jenjang = [
            ['nama' => 'Diploma I (D1)', 'kode' => 'D1', 'urutan' => 1],
            ['nama' => 'Diploma II (D2)', 'kode' => 'D2', 'urutan' => 2],
            ['nama' => 'Diploma III (D3)', 'kode' => 'D3', 'urutan' => 3],
            ['nama' => 'Diploma IV (D4/Sarjana Terapan)', 'kode' => 'D4', 'urutan' => 4],
            ['nama' => 'Sarjana (S1)', 'kode' => 'S1', 'urutan' => 5],
            ['nama' => 'Magister (S2)', 'kode' => 'S2', 'urutan' => 6],
            ['nama' => 'Doktor (S3)', 'kode' => 'S3', 'urutan' => 7],
            ['nama' => 'Profesi', 'kode' => 'PROF', 'urutan' => 8],
        ];

        foreach ($jenjang as $j) {
            JenjangPendidikan::updateOrCreate(
                ['kode' => $j['kode']],
                [
                    'nama' => $j['nama'],
                    'urutan' => $j['urutan'],
                    'is_active' => true,
                ]
            );
        }
    }
}
