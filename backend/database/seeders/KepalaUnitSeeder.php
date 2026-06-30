<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class KepalaUnitSeeder extends Seeder
{
    public function run(): void
    {
        // Hapus kepala unit yang sudah ada
        User::where('is_kepala_unit', true)->delete();

        // Data unit kerja dengan username simpel
        $unitKerjas = [
            // BKPSDM Internal
            ['id' => 1, 'username' => 'set-bkpsdm', 'nama' => 'Sekretariat BKPSDM'],
            ['id' => 2, 'username' => 'sub-bag-umum', 'nama' => 'Sub Bagian Umum'],
            ['id' => 3, 'username' => 'sub-bag-keuangan', 'nama' => 'Sub Bagian Keuangan'],
            ['id' => 4, 'username' => 'bidang-ppp', 'nama' => 'Bidang PPP'],
            ['id' => 5, 'username' => 'bidang-kdh', 'nama' => 'Bidang KDH'],
            ['id' => 6, 'username' => 'bidang-psdm', 'nama' => 'Bidang PSDM'],
            ['id' => 7, 'username' => 'bidang-mkp', 'nama' => 'Bidang MKP'],
            ['id' => 8, 'username' => 'subkor-kinerja', 'nama' => 'Subkor Kinerja'],
            ['id' => 9, 'username' => 'subkor-kompetensi', 'nama' => 'Subkor Kompetensi'],
            ['id' => 10, 'username' => 'subkor-penghargaan', 'nama' => 'Subkor Penghargaan'],
            ['id' => 11, 'username' => 'subkor-mutasi', 'nama' => 'Subkor Mutasi'],
            ['id' => 12, 'username' => 'subkor-perencanaan', 'nama' => 'Subkor Perencanaan'],
            // OPD
            ['id' => 13, 'username' => 'bkpsdm', 'nama' => 'Badan Kepegawaian SDM'],
            ['id' => 14, 'username' => 'disdik', 'nama' => 'Dinas Pendidikan'],
            ['id' => 15, 'username' => 'dinkes', 'nama' => 'Dinas Kesehatan'],
            ['id' => 16, 'username' => 'dpu', 'nama' => 'Dinas Pekerjaan Umum'],
            ['id' => 17, 'username' => 'dishub', 'nama' => 'Dinas Perhubungan'],
            ['id' => 18, 'username' => 'satpolpp', 'nama' => 'Satpol PP'],
            ['id' => 19, 'username' => 'bappeda', 'nama' => 'Bappeda'],
            ['id' => 20, 'username' => 'bpkad', 'nama' => 'BPKAD'],
            ['id' => 21, 'username' => 'kec-cisaat', 'nama' => 'Kecamatan Cisaat'],
            ['id' => 22, 'username' => 'kec-sukabumi', 'nama' => 'Kecamatan Sukabumi'],
        ];

        $nipBase = 198000000000000000;

        foreach ($unitKerjas as $index => $unit) {
            $email = "{$unit['username']}@sipintar.go.id";
            $nip = $nipBase + ($index + 1) * 1000000 + $unit['id'];

            User::create([
                'name' => $unit['nama'],
                'email' => $email,
                'password' => Hash::make('password'),
                'nip' => (string) $nip,
                'role_id' => 2, // kepala/atasan
                'unit_kerja_id' => $unit['id'],
                'is_kepala_unit' => true,
                'pangkat_gol' => 'Pembina - IV/a',
                'jabatan' => "Kepala {$unit['nama']}",
                'is_active' => true,
            ]);

            $this->command->info("Created: {$unit['username']} -> {$email}");
        }

        $this->command->info('Total kepala unit accounts created: '.count($unitKerjas));
        $this->command->info('Password for all accounts: password');
    }
}
