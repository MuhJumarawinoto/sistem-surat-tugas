<?php

namespace Tests\Unit\Models;

use App\Models\Pengajuan;
use App\Models\SuratTugas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuratTugasModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_surat_tugas_belongs_to_pengajuan(): void
    {
        $surat = SuratTugas::factory()->create();

        $this->assertInstanceOf(Pengajuan::class, $surat->pengajuan);
    }

    public function test_surat_tugas_belongs_to_signed_by_user(): void
    {
        $signer = User::factory()->create();
        $surat = SuratTugas::factory()->create([
            'signed_by' => $signer->id,
            'signed_at' => now(),
            'status_tte' => 'signed',
        ]);

        $this->assertInstanceOf(User::class, $surat->signedBy);
        $this->assertEquals($signer->id, $surat->signedBy->id);
    }

    public function test_is_signed_returns_true_when_signed(): void
    {
        $surat = SuratTugas::factory()->signed()->create();

        $this->assertTrue($surat->isSigned());
        $this->assertFalse($surat->isPending());
    }

    public function test_is_pending_returns_true_when_pending(): void
    {
        $surat = SuratTugas::factory()->create(['status_tte' => 'pending']);

        $this->assertTrue($surat->isPending());
        $this->assertFalse($surat->isSigned());
    }

    public function test_tanggal_terbit_casts_to_date(): void
    {
        $surat = SuratTugas::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $surat->tanggal_terbit);
    }

    public function test_signed_at_casts_to_datetime(): void
    {
        $surat = SuratTugas::factory()->signed()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $surat->signed_at);
    }

    public function test_nomor_surat_is_unique(): void
    {
        $surat = SuratTugas::factory()->create(['nomor_surat' => '800.1.3.1/1/BKPSDM/2026']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        SuratTugas::factory()->create(['nomor_surat' => '800.1.3.1/1/BKPSDM/2026']);
    }
}
