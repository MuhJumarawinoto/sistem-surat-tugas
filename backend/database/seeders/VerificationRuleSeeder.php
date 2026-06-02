<?php

namespace Database\Seeders;

use App\Models\VerificationRule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VerificationRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rules = [
            [
                'kode' => 'staf',
                'nama_jabatan' => 'Staf / Pelaksana',
                'atasan_level' => 'kasi',
                'signer_s1' => 'Kepala BKPSDM',
                'signer_s2' => 'Sekretaris Daerah',
                'signer_s3' => 'Bupati',
                'urutan' => 1,
            ],
            [
                'kode' => 'kasi',
                'nama_jabatan' => 'Kepala Seksi / Kasubbag',
                'atasan_level' => 'kabid',
                'signer_s1' => 'Kepala BKPSDM',
                'signer_s2' => 'Sekretaris Daerah',
                'signer_s3' => 'Bupati',
                'urutan' => 2,
            ],
            [
                'kode' => 'kabid',
                'nama_jabatan' => 'Kepala Bidang / Sekretaris',
                'atasan_level' => 'kadis',
                'signer_s1' => 'Kepala BKPSDM',
                'signer_s2' => 'Sekretaris Daerah',
                'signer_s3' => 'Bupati',
                'urutan' => 3,
            ],
            [
                'kode' => 'kadis',
                'nama_jabatan' => 'Kepala Dinas / Kepala Badan (selain BKPSDM)',
                'atasan_level' => 'sekda',
                'signer_s1' => 'Kepala BKPSDM',
                'signer_s2' => 'Sekretaris Daerah',
                'signer_s3' => 'Bupati',
                'urutan' => 4,
            ],
            [
                'kode' => 'kepala_bkpsdm',
                'nama_jabatan' => 'Kepala BKPSDM',
                'atasan_level' => 'sekda',
                'signer_s1' => 'Kepala BKPSDM',
                'signer_s2' => 'Sekretaris Daerah',
                'signer_s3' => 'Bupati',
                'urutan' => 5,
            ],
            [
                'kode' => 'staf_bkpsdm',
                'nama_jabatan' => 'Staf BKPSDM',
                'atasan_level' => 'kasi_bkpsdm',
                'signer_s1' => 'Kepala BKPSDM',
                'signer_s2' => 'Sekretaris Daerah',
                'signer_s3' => 'Bupati',
                'urutan' => 1,
            ],
            [
                'kode' => 'kasi_bkpsdm',
                'nama_jabatan' => 'Kepala Seksi / Kasubbag di BKPSDM',
                'atasan_level' => 'kabid_bkpsdm',
                'signer_s1' => 'Kepala BKPSDM',
                'signer_s2' => 'Sekretaris Daerah',
                'signer_s3' => 'Bupati',
                'urutan' => 2,
            ],
            [
                'kode' => 'kabid_bkpsdm',
                'nama_jabatan' => 'Kepala Bidang di BKPSDM',
                'atasan_level' => 'kepala_bkpsdm',
                'signer_s1' => 'Kepala BKPSDM',
                'signer_s2' => 'Sekretaris Daerah',
                'signer_s3' => 'Bupati',
                'urutan' => 3,
            ],
            [
                'kode' => 'sekda',
                'nama_jabatan' => 'Sekretaris Daerah (Sekda)',
                'atasan_level' => 'bupati',
                'signer_s1' => 'Kepala BKPSDM',
                'signer_s2' => 'Sekretaris Daerah',
                'signer_s3' => 'Bupati',
                'urutan' => 6,
            ],
            [
                'kode' => 'bupati',
                'nama_jabatan' => 'Bupati / Wakil Bupati',
                'atasan_level' => null,
                'signer_s1' => 'Sekretaris Daerah',
                'signer_s2' => 'Bupati',
                'signer_s3' => 'Bupati',
                'urutan' => 7,
            ],
        ];

        foreach ($rules as $rule) {
            VerificationRule::create($rule);
        }
    }
}
