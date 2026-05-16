<?php

namespace Database\Factories;

use App\Models\ApprovalHistory;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApprovalHistoryFactory extends Factory
{
    protected $model = ApprovalHistory::class;

    public function definition(): array
    {
        return [
            'pengajuan_id' => Pengajuan::factory(),
            'approver_id' => User::factory(),
            'role_approval' => fake()->randomElement(['atasan', 'admin_bkpsdm', 'kepala_bkpsdm']),
            'status' => fake()->randomElement(['setuju', 'tolak']),
            'catatan' => fake()->sentence(),
        ];
    }
}
