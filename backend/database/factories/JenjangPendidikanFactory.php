<?php

namespace Database\Factories;

use App\Models\JenjangPendidikan;
use Illuminate\Database\Eloquent\Factories\Factory;

class JenjangPendidikanFactory extends Factory
{
    protected $model = JenjangPendidikan::class;

    public function definition(): array
    {
        static $urutan = 0;

        return [
            'nama' => fake()->randomElement(['S1', 'S2', 'S3', 'Profesi']),
            'kode' => fake()->unique()->bothify('?#'),
            'urutan' => ++$urutan,
            'is_active' => true,
        ];
    }
}
