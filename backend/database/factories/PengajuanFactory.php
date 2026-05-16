<?php

namespace Database\Factories;

use App\Models\JenjangPendidikan;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PengajuanFactory extends Factory
{
    protected $model = Pengajuan::class;

    public function definition(): array
    {
        return [
            'nomor_pengajuan' => 'IBL/' . date('Y') . '/' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'user_id' => User::factory(),
            'jenjang_id' => JenjangPendidikan::factory(),
            'nama_prodi' => fake()->words(3, true),
            'perguruan_tinggi' => 'Universitas ' . fake()->city(),
            'akreditasi_prodi' => fake()->randomElement(['A', 'B', 'C', 'Unggul']),
            'lokasi_pt' => fake()->city(),
            'rencana_mulai' => now()->addMonths(1)->format('Y-m-d'),
            'rencana_selesai' => now()->addYears(2)->format('Y-m-d'),
            'status' => 'draft',
        ];
    }

    public function pendingAtasan(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_atasan',
            'tanggal_submit_atasan' => now(),
        ]);
    }

    public function pendingAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_admin',
            'tanggal_submit_atasan' => now()->subDays(2),
            'tanggal_approve_atasan' => now()->subDay(),
        ]);
    }

    public function disetujui(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'disetujui',
            'tanggal_submit_atasan' => now()->subDays(3),
            'tanggal_approve_atasan' => now()->subDays(2),
            'tanggal_approve_admin' => now()->subDay(),
        ]);
    }

    public function ditolak(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ditolak',
            'catatan_tolak' => fake()->sentence(),
        ]);
    }

    public function selesai(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'selesai',
        ]);
    }
}
