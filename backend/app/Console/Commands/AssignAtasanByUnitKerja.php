<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AssignAtasanByUnitKerja extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'atasan:assign-by-unit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign atasan for staff based on unit kerja';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai penugasan atasan berdasarkan unit kerja...');

        // Get admin user IDs to exclude
        $adminIds = \App\Models\Role::where('slug', 'admin_bkpsdm')
            ->first()
            ?->users()
            ->pluck('id')
            ->toArray() ?? [];

        $this->info('Admin BKPSDM IDs: ' . implode(', ', $adminIds));

        // Get all staff without proper atasan assignment
        $staff = User::where('jabatan_kategori', 'staf')
            ->where(function ($q) use ($adminIds) {
                $q->whereNull('atasan_id')
                  ->orWhereIn('atasan_id', $adminIds);
            })
            ->get();

        $this->info('Ditemukan ' . $staff->count() . ' staff yang perlu ditugasi atasan.');

        if ($staff->isEmpty()) {
            $this->warn('Tidak ada staff yang perlu diperbarui.');
            return Command::SUCCESS;
        }

        // Get potential atasan (kasi level)
        $kasiList = User::where('jabatan_kategori', 'kasi')
            ->orWhere('jabatan_kategori', 'kabid') // Also include kabid as fallback
            ->get();

        $this->info('Tersedia ' . $kasiList->count() . ' calon atasan (Eselon III/IV).');

        // Group staff by unit kerja
        $staffByUnit = $staff->groupBy('unit_kerja');

        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($staffByUnit as $unitKerja => $staffGroup) {
            $this->line("\nUnit Kerja: {$unitKerja}");
            $this->line('Staff: ' . $staffGroup->pluck('name')->join(', '));

            // Find atasan in same unit kerja (preferably kasi)
            $atasan = $kasiList->first(function ($user) use ($unitKerja) {
                return $user->unit_kerja === $unitKerja &&
                       in_array($user->jabatan_kategori, ['kasi', 'kabid']);
            });

            if (!$atasan) {
                // Try fuzzy matching for unit kerja
                $atasan = $kasiList->first(function ($user) use ($unitKerja) {
                    return stripos($user->unit_kerja, $unitKerja) !== false ||
                           stripos($unitKerja, $user->unit_kerja) !== false;
                });
            }

            if ($atasan) {
                $this->info("  → Atasan: {$atasan->name} ({$atasan->jabatan})");

                foreach ($staffGroup as $s) {
                    $s->atasan_id = $atasan->id;
                    $s->save();
                    $updatedCount++;
                }
            } else {
                $this->warn("  → Tidak ditemukan atasan untuk unit kerja ini");
                $skippedCount += $staffGroup->count();
            }
        }

        $this->newLine();
        $this->info("Selesai! {$updatedCount} staff telah diperbarui atasannya.");
        if ($skippedCount > 0) {
            $this->warn("{$skippedCount} staff dilewati (tidak ada atasan yang sesuai)");
        }

        return Command::SUCCESS;
    }
}
