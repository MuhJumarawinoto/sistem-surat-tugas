<?php

namespace Database\Seeders;

use App\Models\UnitKerja;
use Illuminate\Database\Seeder;

class UnitKerjaSeeder extends Seeder
{
    public function run(): void
    {
        $unitKerja = [
            ['kode' => '01', 'nama' => 'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia', 'singkatan' => 'BKPSDM', 'eselon' => 'IIb'],
            ['kode' => '02', 'nama' => 'Dinas Pendidikan', 'singkatan' => 'DISDIK', 'eselon' => 'IIb'],
            ['kode' => '03', 'nama' => 'Dinas Kesehatan', 'singkatan' => 'DINKES', 'eselon' => 'IIb'],
            ['kode' => '04', 'nama' => 'Dinas Pekerjaan Umum', 'singkatan' => 'DPU', 'eselon' => 'IIb'],
            ['kode' => '05', 'nama' => 'Dinas Perhubungan', 'singkatan' => 'DISHUB', 'eselon' => 'IIb'],
            ['kode' => '06', 'nama' => 'Satuan Polisi Pamong Praja', 'singkatan' => 'SATPOL PP', 'eselon' => 'IIb'],
            ['kode' => '07', 'nama' => 'Badan Perencanaan Pembangunan Daerah', 'singkatan' => 'BAPPEDA', 'eselon' => 'IIb'],
            ['kode' => '08', 'nama' => 'Badan Pengelola Keuangan dan Aset Daerah', 'singkatan' => 'BPKAD', 'eselon' => 'IIb'],
            ['kode' => '09', 'nama' => 'Kecamatan Cisaat', 'singkatan' => 'KEC. CSAAT', 'eselon' => 'IIIa'],
            ['kode' => '10', 'nama' => 'Kecamatan Sukabumi', 'singkatan' => 'KEC. SKB', 'eselon' => 'IIIa'],
        ];

        foreach ($unitKerja as $unit) {
            UnitKerja::create($unit);
        }
    }
}
