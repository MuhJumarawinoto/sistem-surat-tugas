<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'nip' => fake()->unique()->numerify('####################'),
            'role_id' => null,
            'unit_kerja_id' => null,
            'pangkat_gol' => fake()->randomElement(['III/a', 'III/b', 'III/c', 'III/d', 'IV/a', 'IV/b']),
            'jabatan' => fake()->jobTitle(),
            'tanggal_lahir' => fake()->date(),
            'no_hp' => fake()->phoneNumber(),
            'alamat' => fake()->address(),
            'is_active' => true,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function pemohon(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::factory()->pemohon(),
        ]);
    }

    public function atasan(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::factory()->atasan(),
        ]);
    }

    public function adminBkpsdm(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::factory()->adminBkpsdm(),
        ]);
    }

    public function kepalaBkpsdm(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::factory()->kepalaBkpsdm(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
