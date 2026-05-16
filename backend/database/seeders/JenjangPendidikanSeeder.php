<?php

namespace Database\Seeders;

use App\Models\JenjangPendidikan;
use Illuminate\Database\Seeder;

class JenjangPendidikanSeeder extends Seeder
{
    public function run(): void
    {
        $jenjang = [
            ['nama' => 'Sarjana (S1)', 'kode' => 'S1', 'urutan' => 1],
            ['nama' => 'Magister (S2)', 'kode' => 'S2', 'urutan' => 2],
            ['nama' => 'Doktor (S3)', 'kode' => 'S3', 'urutan' => 3],
            ['nama' => 'Profesi', 'kode' => 'PROF', 'urutan' => 4],
        ];

        foreach ($jenjang as $j) {
            JenjangPendidikan::create($j);
        }
    }
}
