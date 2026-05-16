<?php

namespace Database\Factories;

use App\Models\Pengajuan;
use App\Models\SuratTugas;
use Illuminate\Database\Eloquent\Factories\Factory;

class SuratTugasFactory extends Factory
{
    protected $model = SuratTugas::class;

    public function definition(): array
    {
        return [
            'pengajuan_id' => Pengajuan::factory(),
            'nomor_surat' => '800.1.3.1/' . fake()->numberBetween(1, 999) . '/BKPSDM/' . date('Y'),
            'tanggal_terbit' => now()->format('Y-m-d'),
            'file_path' => 'surat-tugas/surat-tugas-test.pdf',
            'status_tte' => 'pending',
        ];
    }

    public function signed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_tte' => 'signed',
            'signed_by' => \App\Models\User::factory(),
            'signed_at' => now(),
            'tte_qr_code' => 'QR-800.1.3.1/' . fake()->numberBetween(1, 999) . '/BKPSDM/' . date('Y'),
        ]);
    }
}
