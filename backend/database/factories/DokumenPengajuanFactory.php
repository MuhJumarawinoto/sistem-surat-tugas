<?php

namespace Database\Factories;

use App\Models\DokumenPengajuan;
use App\Models\Pengajuan;
use Illuminate\Database\Eloquent\Factories\Factory;

class DokumenPengajuanFactory extends Factory
{
    protected $model = DokumenPengajuan::class;

    public function definition(): array
    {
        return [
            'pengajuan_id' => Pengajuan::factory(),
            'jenis_dokumen' => fake()->randomElement([
                'sk_pangkat', 'sk_cpns', 'skp', 'surat_lulus',
                'jadwal', 'akreditasi', 'surat_mandiri', 'surat_ijazah', 'surat_sehat',
            ]),
            'file_path' => 'dokumen/test/' . fake()->uuid() . '.pdf',
            'file_name' => fake()->word() . '.pdf',
            'file_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(102400, 5242880),
            'status_verifikasi' => 'pending',
        ];
    }
}
