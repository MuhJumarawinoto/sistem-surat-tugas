<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin BKPSDM',
                'email' => 'admin@bkpsdm.go.id',
                'password' => Hash::make('password'),
                'nip' => '198001012010011001',
                'role_id' => 4, // admin_bkpsdm
                'unit_kerja_id' => 1,
                'pangkat_gol' => 'Penata Tk.I - III/d',
                'jabatan' => 'Kepala Subbidang Pendidikan',
            ],
            [
                'name' => 'Kepala BKPSDM',
                'email' => 'kepala@bkpsdm.go.id',
                'password' => Hash::make('password'),
                'nip' => '197506152005011002',
                'role_id' => 5, // kepala_bkpsdm
                'unit_kerja_id' => 1,
                'pangkat_gol' => 'Pembina Utama - IV/e',
                'jabatan' => 'Kepala BKPSDM',
            ],
            [
                'name' => 'Staf Bidang BKPSDM',
                'email' => 'bidang@bkpsdm.go.id',
                'password' => Hash::make('password'),
                'nip' => '198805202011011003',
                'role_id' => 3, // bidang
                'unit_kerja_id' => 1,
                'pangkat_gol' => 'Penata - III/c',
                'jabatan' => 'Staf Bidang Pendidikan',
            ],
            [
                'name' => 'Drajat Sukmana',
                'email' => 'drajat@disdik.go.id',
                'password' => Hash::make('password'),
                'nip' => '198505102015011001',
                'role_id' => 1, // pemohon
                'unit_kerja_id' => 2,
                'pangkat_gol' => 'Penata Muda - III/a',
                'jabatan' => 'Guru ASN',
            ],
            [
                'name' => 'Kepala Dinas Pendidikan',
                'email' => 'kadisdik@disdik.go.id',
                'password' => Hash::make('password'),
                'nip' => '197005122000121001',
                'role_id' => 2, // atasan
                'unit_kerja_id' => 2,
                'pangkat_gol' => 'Pembina - IV/a',
                'jabatan' => 'Kepala Dinas Pendidikan',
            ],
            [
                'name' => 'Siti Aminah',
                'email' => 'siti@disdik.go.id',
                'password' => Hash::make('password'),
                'nip' => '199008152019022001',
                'role_id' => 1, // pemohon
                'unit_kerja_id' => 2,
                'pangkat_gol' => 'Penata Muda - III/a',
                'jabatan' => 'Guru ASN',
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
