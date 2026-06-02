<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SetJabatanKategoriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Auto-assign jabatan_kategori based on jabatan patterns.
     */
    public function run(): void
    {
        // Pattern mapping for jabatan -> jabatan_kategori
        $patterns = [
            // Bupati level
            ['pattern' => '/bupati/i', 'kategori' => 'bupati', 'priority' => 10],
            ['pattern' => '/wakil.*bupati/i', 'kategori' => 'bupati', 'priority' => 10],

            // Sekda level
            ['pattern' => '/sekretaris.*daerah/i', 'kategori' => 'sekda', 'priority' => 9],
            ['pattern' => '/sekda/i', 'kategori' => 'sekda', 'priority' => 9],

            // Kepala Dinas level
            ['pattern' => '/kepala.*bkpsdm/i', 'kategori' => 'kepala_bkpsdm', 'priority' => 8],
            ['pattern' => '/kepala.*dinas/i', 'kategori' => 'kadis', 'priority' => 7],
            ['pattern' => '/kepala.*badan/i', 'kategori' => 'kadis', 'priority' => 7],
            ['pattern' => '/kepalan/i', 'kategori' => 'kadis', 'priority' => 7],

            // Kepala Bidang level
            ['pattern' => '/kepala.*bidang.*bkpsdm/i', 'kategori' => 'kabid_bkpsdm', 'priority' => 6],
            ['pattern' => '/kabid.*bkpsdm/i', 'kategori' => 'kabid_bkpsdm', 'priority' => 6],
            ['pattern' => '/kepala.*bidang/i', 'kategori' => 'kabid', 'priority' => 5],
            ['pattern' => '/kabid/i', 'kategori' => 'kabid', 'priority' => 5],

            // Sekretaris level
            ['pattern' => '/sekretaris.*bkpsdm/i', 'kategori' => 'kabid_bkpsdm', 'priority' => 6],
            ['pattern' => '/sekretaris/i', 'kategori' => 'kabid', 'priority' => 5],

            // Kepala Seksi level
            ['pattern' => '/kepala.*seksi.*bkpsdm/i', 'kategori' => 'kasi_bkpsdm', 'priority' => 4],
            ['pattern' => '/kasi.*bkpsdm/i', 'kategori' => 'kasi_bkpsdm', 'priority' => 4],
            ['pattern' => '/kasubbag.*bkpsdm/i', 'kategori' => 'kasi_bkpsdm', 'priority' => 4],
            ['pattern' => '/kepala.*seksi/i', 'kategori' => 'kasi', 'priority' => 3],
            ['pattern' => '/kepala.*bagian/i', 'kategori' => 'kasi', 'priority' => 3],
            ['pattern' => '/kasi/i', 'kategori' => 'kasi', 'priority' => 3],
            ['pattern' => '/kasubbag/i', 'kategori' => 'kasi', 'priority' => 3],

            // Staf level (default)
            ['pattern' => '/staf.*bkpsdm/i', 'kategori' => 'staf_bkpsdm', 'priority' => 1],
            ['pattern' => '/pelaksana.*bkpsdm/i', 'kategori' => 'staf_bkpsdm', 'priority' => 1],
            ['pattern' => '/staf/i', 'kategori' => 'staf', 'priority' => 1],
            ['pattern' => '/pelaksana/i', 'kategori' => 'staf', 'priority' => 1],
            ['pattern' => '/staff/i', 'kategori' => 'staf', 'priority' => 1],
        ];

        $users = User::whereNotNull('nip')->get();
        $updatedCount = 0;
        $skippedCount = 0;

        $this->command->info('Setting jabatan_kategori for users...');
        $this->command->info('');

        foreach ($users as $user) {
            // Skip if already has kategori
            if ($user->jabatan_kategori) {
                $skippedCount++;
                continue;
            }

            // Skip if no jabatan
            if (!$user->jabatan) {
                $this->command->warn("  - {$user->name}: No jabatan field");
                $skippedCount++;
                continue;
            }

            // Find matching category (highest priority first)
            $matchedKategori = null;
            $highestPriority = 0;

            foreach ($patterns as $pattern) {
                if (preg_match($pattern['pattern'], $user->jabatan) && $pattern['priority'] > $highestPriority) {
                    $matchedKategori = $pattern['kategori'];
                    $highestPriority = $pattern['priority'];
                }
            }

            if ($matchedKategori) {
                $user->jabatan_kategori = $matchedKategori;
                $user->save();
                $updatedCount++;
                $this->command->info("  ✓ {$user->name}: '{$user->jabatan}' → {$matchedKategori}");
            } else {
                // Default to staf if no match
                $user->jabatan_kategori = 'staf';
                $user->save();
                $updatedCount++;
                $this->command->comment("  ○ {$user->name}: '{$user->jabatan}' → staf (default)");
            }
        }

        $this->command->info('');
        $this->command->info("===========================================");
        $this->command->info("Jabatan Kategori Assignment Summary:");
        $this->command->info("✓ Updated: {$updatedCount} users");
        $this->command->info("○ Skipped: {$skippedCount} users");
        $this->command->info("===========================================");
    }
}
