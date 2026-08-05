<?php

namespace Database\Factories;

use App\Models\JenjangPendidikan;
use App\Models\PgaPengajuan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PgaPengajuan>
 */
class PgaPengajuanFactory extends Factory
{
    protected $model = PgaPengajuan::class;

    public function definition(): array
    {
        $year = fake()->numberBetween(2015, 2025);
        $jenjang = JenjangPendidikan::inRandomOrder()->first() ?? JenjangPendidikan::factory();

        return [
            'nomor_pengajuan' => 'PGA-'.date('Ym').str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'user_id' => User::factory(),
            'jenjang_pendidikan_id' => $jenjang,
            'gelar_akademik' => fake()->randomElement(['S.Kom', 'S.T.', 'S.H.', 'S.E.', 'S.Pd', 'M.Kom', 'M.T.', 'M.H.', 'M.E.', 'M.Pd', 'Dr.', 'Ir.', 'Dra.']),
            'nama_prodi' => fake()->words(3, true),
            'perguruan_tinggi' => 'Universitas '.fake()->city(),
            'lokasi_pt' => fake()->city().', '.fake()->state(),
            'nomor_ijazah' => fake()->bothify('????-####-#####'),
            'tanggal_ijazah' => fake()->dateTimeBetween('-5 years', '-1 years')->format('Y-m-d'),
            'tahun_lulus' => $year,
            'status' => 'draft',
            'ijazah_file' => 'documents/ijazah/'.fake()->uuid().'.pdf',
            'transkrip_file' => 'documents/transkrip/'.fake()->uuid().'.pdf',
            'sk_kum_file' => 'documents/sk_kum/'.fake()->uuid().'.pdf',
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function approvedAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved_admin',
            'tanggal_approve_admin' => now()->subDay(),
        ]);
    }

    public function selesai(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'selesai',
            'tanggal_approve_admin' => now()->subDays(2),
            'tanggal_selesai' => now()->subDay(),
        ]);
    }

    public function ditolak(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ditolak',
            'catatan_tolak' => fake()->sentence(),
        ]);
    }
}
