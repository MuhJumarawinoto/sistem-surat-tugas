<?php

namespace Tests\Unit\Models;

use App\Models\DokumenPengajuan;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DokumenPengajuanModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_dokumen_belongs_to_pengajuan(): void
    {
        $dokumen = DokumenPengajuan::factory()->create();

        $this->assertInstanceOf(Pengajuan::class, $dokumen->pengajuan);
    }

    public function test_dokumen_belongs_to_verified_by_user(): void
    {
        $verifier = User::factory()->create();
        $dokumen = DokumenPengajuan::factory()->create([
            'verified_by' => $verifier->id,
            'verified_at' => now(),
        ]);

        $this->assertInstanceOf(User::class, $dokumen->verifiedBy);
        $this->assertEquals($verifier->id, $dokumen->verifiedBy->id);
    }

    public function test_jenis_dokumen_label_accessor(): void
    {
        $labels = [
            'sk_pangkat' => 'SK Pangkat Terakhir',
            'sk_cpns' => 'SK CPNS',
            'skp' => 'SKP 2 Tahun Terakhir',
            'surat_lulus' => 'Surat Keterangan Lulus/Diterima',
            'jadwal' => 'Jadwal Perkuliahan',
            'akreditasi' => 'Sertifikat Akreditasi Prodi',
            'surat_mandiri' => 'Surat Pernyataan Biaya Mandiri',
            'surat_ijazah' => 'Surat Pernyataan Tidak Menuntut Ijazah',
            'surat_sehat' => 'Surat Keterangan Sehat',
        ];

        foreach ($labels as $type => $expectedLabel) {
            $dokumen = DokumenPengajuan::factory()->create(['jenis_dokumen' => $type]);
            $this->assertEquals($expectedLabel, $dokumen->jenis_dokumen_label);
        }
    }

    public function test_jenis_dokumen_label_unknown_returns_default(): void
    {
        $dokumen = DokumenPengajuan::factory()->create(['jenis_dokumen' => 'sk_pangkat']);

        $this->assertIsString($dokumen->jenis_dokumen_label);
    }

    public function test_file_size_in_mb_accessor(): void
    {
        $dokumen = DokumenPengajuan::factory()->create(['file_size' => 1572864]);

        $this->assertEquals(1.5, $dokumen->file_size_in_mb);
    }

    public function test_verified_at_casts_to_datetime(): void
    {
        $dokumen = DokumenPengajuan::factory()->create([
            'verified_at' => '2026-05-17 10:30:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $dokumen->verified_at);
    }

    public function test_verified_at_can_be_null(): void
    {
        $dokumen = DokumenPengajuan::factory()->create(['verified_at' => null]);

        $this->assertNull($dokumen->verified_at);
    }
}
