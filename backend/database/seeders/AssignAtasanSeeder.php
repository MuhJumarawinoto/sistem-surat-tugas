<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssignAtasanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Assign atasan based on verification matrix rules.
     */
    public function run(): void
    {
        // Mapping jabatan_kategori ke atasan_kategori yang seharusnya
        $atasanMapping = [
            'staf' => ['kasi', 'kabid'], // Staf bisa di-atasan oleh Kasi atau Kabid
            'staf_bkpsdm' => ['kasi_bkpsdm', 'kabid_bkpsdm'],
            'kasi' => ['kabid'], // Kasi di-atasan oleh Kabid
            'kasi_bkpsdm' => ['kabid_bkpsdm'],
            'kabid' => ['kadis', 'kepala_bkpsdm'], // Kabid di-atasan oleh Kadis atau Kepala BKPSDM
            'kabid_bkpsdm' => ['kepala_bkpsdm'],
            'kadis' => ['sekda'], // Kadis di-atasan oleh Sekda
            'kepala_bkpsdm' => ['sekda'], // Kepala BKPSDM di-atasan oleh Sekda
            'sekda' => ['bupati'], // Sekda di-atasan oleh Bupati
            'bupati' => [], // Bupati tidak punya atasan
        ];

        $users = User::with('unitKerja')->whereNotNull('nip')->get();
        $assignedCount = 0;
        $skippedCount = 0;

        foreach ($users as $user) {
            // Skip if already has atasan assigned
            if ($user->atasan_id) {
                $skippedCount++;
                continue;
            }

            // Skip if no jabatan_kategori
            if (!$user->jabatan_kategori) {
                $this->command->warn("  User {$user->name} ({$user->nip}) has no jabatan_kategori");
                $skippedCount++;
                continue;
            }

            // Get possible atasan categories for this user
            $possibleAtasanCategories = $atasanMapping[$user->jabatan_kategori] ?? [];

            if (empty($possibleAtasanCategories)) {
                $this->command->warn("  No atasan mapping for {$user->jabatan_kategori}");
                $skippedCount++;
                continue;
            }

            // Find atasan - prioritize same unit kerja, then any unit
            $atasan = $this->findAtasan($user, $possibleAtasanCategories);

            if ($atasan) {
                $user->atasan_id = $atasan->id;
                $user->save();
                $assignedCount++;
                $this->command->info("  ✓ {$user->name} ({$user->jabatan_kategori}) → Atasan: {$atasan->name} ({$atasan->jabatan_kategori})");
            } else {
                $this->command->warn("  ✗ No atasan found for {$user->name} ({$user->jabatan_kategori}) in categories: " . implode(', ', $possibleAtasanCategories));
                $skippedCount++;
            }
        }

        $this->command->info('');
        $this->command->info("===========================================");
        $this->command->info("Atasan Assignment Summary:");
        $this->command->info("✓ Assigned: {$assignedCount} users");
        $this->command->info("○ Skipped: {$skippedCount} users");
        $this->command->info("===========================================");
    }

    /**
     * Find atasan for a user based on possible atasan categories
     */
    private function findAtasan(User $user, array $possibleAtasanCategories): ?User
    {
        // First try: Find atasan in same unit kerja
        if ($user->unit_kerja_id) {
            $atasan = User::where('unit_kerja_id', $user->unit_kerja_id)
                ->where('id', '!=', $user->id)
                ->whereIn('jabatan_kategori', $possibleAtasanCategories)
                ->whereNotNull('nip')
                ->where('is_active', true)
                ->first();

            if ($atasan) {
                return $atasan;
            }
        }

        // Second try: For BKPSDM staff, find any BKPSDM atasan
        if (str_ends_with($user->jabatan_kategori, '_bkpsdm') || $user->unit_kerja?->nama == 'BKPSDM') {
            $atasan = User::where('jabatan_kategori', $possibleAtasanCategories)
                ->where('id', '!=', $user->id)
                ->where(function ($q) {
                    $q->where('jabatan_kategori', 'like', '%_bkpsdm')
                      ->orWhereHas('unitKerja', function ($subQ) {
                          $subQ->where('nama', 'like', '%BKPSDM%');
                      });
                })
                ->whereNotNull('nip')
                ->where('is_active', true)
                ->first();

            if ($atasan) {
                return $atasan;
            }
        }

        // Third try: Find any atasan with the required category
        $atasan = User::whereIn('jabatan_kategori', $possibleAtasanCategories)
            ->where('id', '!=', $user->id)
            ->whereNotNull('nip')
            ->where('is_active', true)
            ->first();

        return $atasan;
    }
}
