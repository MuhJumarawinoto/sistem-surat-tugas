<?php

namespace Database\Factories;

use App\Models\UnitKerja;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitKerjaFactory extends Factory
{
    protected $model = UnitKerja::class;

    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->numerify('UK###'),
            'nama' => fake()->company(),
            'singkatan' => fake()->lexify('???'),
            'eselon' => fake()->randomElement(['I', 'II', 'III']),
            'alamat' => fake()->address(),
            'is_active' => true,
        ];
    }
}
