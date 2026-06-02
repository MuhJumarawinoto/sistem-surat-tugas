<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssignAtasanForStaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Menugasi atasan untuk staff...');

        // Get all staff
        $staff = User::where('jabatan_kategori', 'staf')->get();

        $this->command->info('Ditemukan ' . $staff->count() . ' staff.');

        // Get first kasi user as default atasan
        $kasi = User::where('jabatan_kategori', 'kasi')->first();

        if (!$kasi) {
            $this->command->warn('Tidak ditemukan user dengan kategori kasi. Membuat default atasan...');

            // Get admin user as fallback
            $admin = User::whereHas('role', function ($q) {
                $q->where('slug', 'admin_bkpsdm');
            })->first();

            if (!$admin) {
                $this->command->error('Tidak ditemukan admin user. Gagal melakukan seeding.');
                return;
            }

            // Update admin to be kasi for now
            $admin->jabatan_kategori = 'kasi';
            $admin->save();

            $kasi = $admin;
        }

        $this->command->info("Atasan yang ditugaskan: {$kasi->name} ({$kasi->jabatan})");

        $updated = 0;
        foreach ($staff as $s) {
            // Skip if already has this atasan
            if ($s->atasan_id === $kasi->id) {
                continue;
            }

            $s->atasan_id = $kasi->id;
            $s->save();
            $updated++;
        }

        $this->command->info("Selesai! {$updated} staff telah diperbarui.");
    }
}
