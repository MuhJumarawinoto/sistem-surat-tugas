<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Pemohon',
                'slug' => 'pemohon',
                'description' => 'PNS yang ingin mengajukan izin belajar',
            ],
            [
                'name' => 'Atasan Langsung',
                'slug' => 'atasan',
                'description' => 'Kepala OPD/Atasan langsung PNS',
            ],
            [
                'name' => 'Bidang',
                'slug' => 'bidang',
                'description' => 'Staf Bidang yang dapat melihat dan memverifikasi pengajuan di unit kerja nya',
            ],
            [
                'name' => 'Admin BKPSDM',
                'slug' => 'admin_bkpsdm',
                'description' => 'Admin di BKPSDM yang memproses pengajuan',
            ],
            [
                'name' => 'Kepala BKPSDM',
                'slug' => 'kepala_bkpsdm',
                'description' => 'Kepala BKPSDM yang menandatangani surat',
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
