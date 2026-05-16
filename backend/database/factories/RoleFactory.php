<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'name' => fake()->jobTitle(),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
        ];
    }

    public function pemohon(): int
    {
        return Role::firstOrCreate(
            ['slug' => 'pemohon'],
            ['name' => 'Pemohon', 'description' => 'PNS Pemohon']
        )->id;
    }

    public function atasan(): int
    {
        return Role::firstOrCreate(
            ['slug' => 'atasan'],
            ['name' => 'Atasan Langsung', 'description' => 'Kepala OPD']
        )->id;
    }

    public function adminBkpsdm(): int
    {
        return Role::firstOrCreate(
            ['slug' => 'admin_bkpsdm'],
            ['name' => 'Admin BKPSDM', 'description' => 'Admin BKPSDM']
        )->id;
    }

    public function kepalaBkpsdm(): int
    {
        return Role::firstOrCreate(
            ['slug' => 'kepala_bkpsdm'],
            ['name' => 'Kepala BKPSDM', 'description' => 'Kepala BKPSDM']
        )->id;
    }
}
