<?php

namespace Tests\Unit\Models;

use App\Models\DokumenPengajuan;
use App\Models\JenjangPendidikan;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengajuanModelTest extends TestCase
{
    use RefreshDatabase;

    private Pengajuan $pengajuan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pengajuan = Pengajuan::factory()->create(['status' => 'draft']);
    }

    public function test_pengajuan_belongs_to_user(): void
    {
        $this->assertInstanceOf(User::class, $this->pengajuan->user);
    }

    public function test_pengajuan_belongs_to_jenjang(): void
    {
        $this->assertInstanceOf(JenjangPendidikan::class, $this->pengajuan->jenjang);
    }

    public function test_pengajuan_has_dokumen_relationship(): void
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $this->pengajuan->dokumen);
    }

    public function test_pengajuan_has_approval_history_relationship(): void
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $this->pengajuan->approvalHistory);
    }

    public function test_pengajuan_has_surat_tugas_relationship(): void
    {
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $this->pengajuan->suratTugas);
    }

    public function test_is_draft(): void
    {
        $this->assertTrue($this->pengajuan->isDraft());
        $this->assertFalse($this->pengajuan->isPendingAtasan());
        $this->assertFalse($this->pengajuan->isPendingAdmin());
        $this->assertFalse($this->pengajuan->isDisetujui());
        $this->assertFalse($this->pengajuan->isDitolak());
        $this->assertFalse($this->pengajuan->isSelesai());
    }

    public function test_is_pending_atasan(): void
    {
        $pengajuan = Pengajuan::factory()->pendingAtasan()->create();

        $this->assertTrue($pengajuan->isPendingAtasan());
        $this->assertFalse($pengajuan->isDraft());
    }

    public function test_is_pending_admin(): void
    {
        $pengajuan = Pengajuan::factory()->pendingAdmin()->create();

        $this->assertTrue($pengajuan->isPendingAdmin());
        $this->assertFalse($pengajuan->isDraft());
    }

    public function test_is_disetujui(): void
    {
        $pengajuan = Pengajuan::factory()->disetujui()->create();

        $this->assertTrue($pengajuan->isDisetujui());
    }

    public function test_is_ditolak(): void
    {
        $pengajuan = Pengajuan::factory()->ditolak()->create();

        $this->assertTrue($pengajuan->isDitolak());
    }

    public function test_is_selesai(): void
    {
        $pengajuan = Pengajuan::factory()->selesai()->create();

        $this->assertTrue($pengajuan->isSelesai());
    }

    public function test_get_all_documents_uploaded_returns_false_when_missing(): void
    {
        $this->assertFalse($this->pengajuan->getAllDocumentsUploaded());
    }

    public function test_get_all_documents_uploaded_returns_false_when_partial(): void
    {
        $requiredTypes = ['sk_pangkat', 'sk_cpns', 'skp', 'surat_lulus', 'jadwal', 'akreditasi', 'surat_mandiri', 'surat_ijazah'];

        foreach ($requiredTypes as $type) {
            DokumenPengajuan::factory()->create([
                'pengajuan_id' => $this->pengajuan->id,
                'jenis_dokumen' => $type,
            ]);
        }

        $this->assertFalse($this->pengajuan->fresh()->getAllDocumentsUploaded());
    }

    public function test_get_all_documents_uploaded_returns_true_when_complete(): void
    {
        $requiredTypes = [
            'sk_pangkat', 'sk_cpns', 'skp', 'surat_lulus',
            'jadwal', 'akreditasi', 'surat_mandiri', 'surat_ijazah', 'surat_sehat',
        ];

        foreach ($requiredTypes as $type) {
            DokumenPengajuan::factory()->create([
                'pengajuan_id' => $this->pengajuan->id,
                'jenis_dokumen' => $type,
            ]);
        }

        $this->assertTrue($this->pengajuan->fresh()->getAllDocumentsUploaded());
    }

    public function test_rencana_mulai_casts_to_date(): void
    {
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $this->pengajuan->rencana_mulai);
    }

    public function test_rencana_selesai_casts_to_date(): void
    {
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $this->pengajuan->rencana_selesai);
    }

    public function test_nomor_pengajuan_is_unique(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Pengajuan::factory()->create(['nomor_pengajuan' => $this->pengajuan->nomor_pengajuan]);
    }
}
