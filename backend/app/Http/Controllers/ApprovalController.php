<?php

namespace App\Http\Controllers;

use App\Models\ApprovalHistory;
use App\Models\DokumenPengajuan;
use App\Models\Notification;
use App\Models\Pengajuan;
use App\Models\SuratIzinBelajar;
use App\Models\SuratTugasDinas;
use App\Models\SuratTugasMandiri;
use App\Models\User;
use App\Services\BarcodeService;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{
    protected QrCodeService $qrCodeService;

    protected BarcodeService $barcodeService;

    public function __construct(QrCodeService $qrCodeService, BarcodeService $barcodeService)
    {
        $this->qrCodeService = $qrCodeService;
        $this->barcodeService = $barcodeService;
    }

    public function approveAtasan(Request $request, string $id)
    {
        $pengajuan = Pengajuan::with('user')->findOrFail($id);

        if (! $pengajuan->isPendingAtasan()) {
            return response()->json(['message' => 'Pengajuan is not pending atasan approval'], 400);
        }

        $pengajuan->update([
            'status' => 'pending_admin',
            'tanggal_approve_atasan' => now(),
        ]);

        ApprovalHistory::create([
            'pengajuan_id' => $id,
            'approver_id' => $request->user()->id,
            'role_approval' => 'atasan',
            'status' => 'setuju',
            'catatan' => $request->catatan,
        ]);

        // Send notification to pemohon
        $message = 'Pengajuan Anda telah disetujui oleh atasan';
        if ($request->catatan) {
            $message .= '. Catatan: '.$request->catatan;
        }
        Notification::createForUser(
            $pengajuan->user_id,
            'success',
            'Pengajuan Disetujui Atasan',
            $message,
            $pengajuan->id
        );

        return response()->json($pengajuan->load(['user', 'jenjang', 'approvalHistory']));
    }

    public function approveAdmin(Request $request, string $id)
    {
        $pengajuan = Pengajuan::with('user')->findOrFail($id);

        // Allow approving both pending_admin and verified status
        if (! $pengajuan->isPendingAdmin() && $pengajuan->status !== 'verified') {
            return response()->json(['message' => 'Pengajuan is not pending admin approval'], 400);
        }

        $request->validate([
            'catatan' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // If status is pending_admin, verify first
            if ($pengajuan->status === 'pending_admin') {
                $pengajuan->update([
                    'status' => 'verified',
                    'tanggal_approve_admin' => now(),
                ]);

                ApprovalHistory::create([
                    'pengajuan_id' => $id,
                    'approver_id' => $request->user()->id,
                    'role_approval' => 'admin_bkpsdm',
                    'status' => 'setuju',
                    'catatan' => $request->catatan,
                ]);
            }
            // If already verified, skip verification steps (re-trigger Surat Izin creation)

            // Generate nomor surat izin belajar
            $year = date('Y');
            $lastNomor = SuratIzinBelajar::where('tahun', $year)->orderBy('id', 'desc')->first();

            // Extract sequence number from format: 800.1.3.1/{sequence}/BKPSDM/{year}
            if ($lastNomor) {
                $parts = explode('/', $lastNomor->nomor_surat);
                if (count($parts) >= 2 && is_numeric($parts[1])) {
                    $nextNomor = (int) $parts[1] + 1;
                } else {
                    $nextNomor = 1;
                }
            } else {
                $nextNomor = 1;
            }
            $nomorSurat = "800.1.3.1/{$nextNomor}/BKPSDM/{$year}";

            // Generate QR code for verification
            $qrCodeData = json_encode([
                'type' => 'surat_izin_belajar',
                'id' => 0, // Will be updated after save
                'nomor' => $nomorSurat,
                'signed_at' => now()->toIso8601String(),
            ]);

            // Get Kepala BKPSDM for signature
            $kepalaBkpsdm = User::whereHas('role', function ($query) {
                $query->where('slug', 'kepala_bkpsdm');
            })->first();

            if (! $kepalaBkpsdm) {
                throw new \Exception('Kepala BKPSDM tidak ditemukan');
            }

            // Create Surat Izin Belajar dengan status signed
            $suratIzin = SuratIzinBelajar::create([
                'pengajuan_id' => $pengajuan->id,
                'nomor_surat' => $nomorSurat,
                'tahun' => $year,
                'qr_code' => $qrCodeData,
                'status' => 'signed',
                'signed_at' => now(),
                'signed_by' => $kepalaBkpsdm->name,
                'signed_by_nip' => $kepalaBkpsdm->nip,
            ]);

            // Update QR code with actual ID
            $qrCodeData = json_encode([
                'type' => 'surat_izin_belajar',
                'id' => $suratIzin->id,
                'nomor' => $nomorSurat,
                'signed_at' => now()->toIso8601String(),
            ]);
            $suratIzin->update(['qr_code' => $qrCodeData]);

            // Generate nomor surat tugas mandiri
            $lastNomorTugas = SuratTugasMandiri::where('tahun', $year)->orderBy('id', 'desc')->first();
            if ($lastNomorTugas) {
                $parts = explode('/', $lastNomorTugas->nomor_surat);
                if (count($parts) >= 2 && is_numeric($parts[1])) {
                    $nextNomorTugas = (int) $parts[1] + 1;
                } else {
                    $nextNomorTugas = 1;
                }
            } else {
                $nextNomorTugas = 1;
            }
            $nomorSuratTugas = "800.1.3.2/{$nextNomorTugas}/BKPSDM/{$year}";

            // Generate QR code for Surat Tugas Mandiri
            $qrCodeDataTugas = json_encode([
                'type' => 'surat_tugas_mandiri',
                'id' => 0,
                'nomor' => $nomorSuratTugas,
                'created_at' => now()->toIso8601String(),
            ]);

            // Create Surat Tugas Mandiri
            $suratTugas = SuratTugasMandiri::create([
                'pengajuan_id' => $pengajuan->id,
                'surat_izin_belajar_id' => $suratIzin->id,
                'nomor_surat' => $nomorSuratTugas,
                'tahun' => $year,
                'tanggal_surat' => now(),
                'qr_code' => $qrCodeDataTugas,
                'status' => 'signed',
                'signed_at' => now(),
                'signed_by' => $kepalaBkpsdm->name,
                'signed_by_nip' => $kepalaBkpsdm->nip,
            ]);

            // Update QR code with actual ID
            $qrCodeDataTugas = json_encode([
                'type' => 'surat_tugas_mandiri',
                'id' => $suratTugas->id,
                'nomor' => $nomorSuratTugas,
                'created_at' => now()->toIso8601String(),
            ]);
            $suratTugas->update(['qr_code' => $qrCodeDataTugas]);

            // Generate nomor surat tugas dinas
            $unitKerjaId = $pengajuan->user->unit_kerja_id;
            $lastNomorDinas = SuratTugasDinas::where('unit_kerja_id', $unitKerjaId)
                ->where('tahun', $year)
                ->orderBy('id', 'desc')
                ->first();

            if ($lastNomorDinas) {
                $parts = explode('/', $lastNomorDinas->nomor_surat);
                if (count($parts) >= 2 && is_numeric($parts[0])) {
                    $nextNomorDinas = (int) $parts[0] + 1;
                } else {
                    $nextNomorDinas = 1;
                }
            } else {
                $nextNomorDinas = 1;
            }

            $bulanIndo = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $bulanNama = $bulanIndo[intval(date('m')) - 1];
            $nomorSuratDinas = "{$nextNomorDinas}/DK/{$bulanNama}/{$year}";

            // Get kepala unit for unit kerja
            $kepalaUnit = User::where('unit_kerja_id', $unitKerjaId)
                ->where('is_kepala_unit', true)
                ->first();

            // Fallback to Kepala BKPSDM if no kepala unit
            if (! $kepalaUnit) {
                $kepalaUnit = $kepalaBkpsdm;
            }

            // Create Surat Tugas Dinas
            $suratDinas = SuratTugasDinas::create([
                'pengajuan_id' => $pengajuan->id,
                'unit_kerja_id' => $unitKerjaId,
                'kepala_dinas_id' => $kepalaUnit->id,
                'nomor_surat' => $nomorSuratDinas,
                'bulan' => $bulanNama,
                'tahun' => $year,
                'tanggal_mulai' => $pengajuan->tanggal_mulai ?: now(),
                'tanggal_selesai' => $pengajuan->tanggal_selesai ?: now()->addYears(2),
                'tanggal_ttd' => now(),
                'tempat_ttd' => 'Sukabumi',
                'status' => 'signed',
                'signed_at' => now(),
            ]);

            // Update pengajuan status to signed
            $pengajuan->update(['status' => 'signed']);

            // Send notification to pemohon
            $message = "Selamat! Dokumen pengajuan Anda telah disetujui.\n\n";
            $message .= "📄 Surat Izin Belajar: {$nomorSurat}\n";
            $message .= "📄 Surat Tugas Mandiri: {$nomorSuratTugas}\n";
            $message .= "📄 Surat Tugas Dinas: {$nomorSuratDinas}\n\n";
            $message .= 'Ketiga surat telah terbit dan siap diunduh.';
            if ($request->catatan) {
                $message .= "\n\nCatatan: {$request->catatan}";
            }
            Notification::createForUser(
                $pengajuan->user_id,
                'success',
                'Pengajuan Disetujui - Surat Telah Terbit',
                $message,
                $pengajuan->id
            );

            DB::commit();

            return response()->json([
                'message' => 'Pengajuan disetujui. Surat Izin Belajar, Surat Tugas Mandiri, dan Surat Tugas Dinas telah terbit.',
                'data' => $pengajuan->load(['user', 'jenjang', 'approvalHistory']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Failed to approve pengajuan: '.$e->getMessage()], 500);
        }
    }

    public function reject(Request $request, string $id)
    {
        $pengajuan = Pengajuan::findOrFail($id);

        $user = $request->user();
        $roleApproval = match (true) {
            $user->isAtasan() && $pengajuan->isPendingAtasan() => 'atasan',
            $user->isAdminBkpsdm() && $pengajuan->isPendingAdmin() => 'admin_bkpsdm',
            default => null,
        };

        if (! $roleApproval) {
            return response()->json(['message' => 'Cannot reject this pengajuan'], 400);
        }

        $request->validate([
            'catatan' => 'required|string',
        ]);

        $pengajuan->update([
            'status' => 'ditolak',
            'catatan_tolak' => $request->catatan,
        ]);

        ApprovalHistory::create([
            'pengajuan_id' => $id,
            'approver_id' => $user->id,
            'role_approval' => $roleApproval,
            'status' => 'tolak',
            'catatan' => $request->catatan,
        ]);

        // Send notification to pemohon
        $roleName = $roleApproval === 'atasan' ? 'Atasan' : 'Admin BKPSDM';
        Notification::createForUser(
            $pengajuan->user_id,
            'error',
            "Pengajuan Ditolak oleh $roleName",
            "Pengajuan Anda ditolak. Alasan: {$request->catatan}",
            $pengajuan->id
        );

        return response()->json($pengajuan->load(['user', 'jenjang', 'approvalHistory']));
    }

    public function verifyDocuments(Request $request, string $id)
    {
        $request->validate([
            'dokumen_id' => 'required|exists:dokumen_pengajuan,id',
            'status' => 'required|in:lengkap,tidak_lengkap',
            'catatan' => 'nullable|string',
        ]);

        $pengajuan = Pengajuan::with('user')->findOrFail($id);

        if (! $pengajuan->isPendingAdmin()) {
            return response()->json(['message' => 'Pengajuan is not pending admin verification'], 400);
        }

        $dokumen = $pengajuan->dokumen()->findOrFail($request->dokumen_id);

        $dokumen->update([
            'status_verifikasi' => $request->status,
            'catatan' => $request->catatan,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        // Send notification if document is marked as incomplete with notes
        if ($request->status === 'tidak_lengkap' && $request->catatan) {
            Notification::createForUser(
                $pengajuan->user_id,
                'warning',
                'Dokumen Perlu Diperbaiki',
                "Dokumen {$dokumen->file_name} ditandai sebagai tidak lengkap. Catatan: {$request->catatan}",
                $pengajuan->id
            );
        }

        return response()->json($dokumen);
    }

    /**
     * Verify a single document (used by frontend modal)
     */
    public function verifyDocument(Request $request, string $dokumenId)
    {
        $request->validate([
            'status' => 'required|in:lengkap,tidak_lengkap',
            'catatan' => 'nullable|string',
        ]);

        $dokumen = DokumenPengajuan::with('pengajuan.user')->findOrFail($dokumenId);

        $pengajuan = $dokumen->pengajuan;
        if (! $pengajuan->isPendingAdmin()) {
            return response()->json(['message' => 'Pengajuan is not pending admin verification'], 400);
        }

        $oldStatus = $dokumen->status_verifikasi;
        $dokumen->update([
            'status_verifikasi' => $request->status,
            'catatan' => $request->catatan,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        // Send notification whenever there's a note (regardless of status)
        // Or if marked as incomplete
        if ($request->catatan) {
            $type = $request->status === 'tidak_lengkap' ? 'warning' : 'info';
            $title = $request->status === 'lengkap' ? 'Catatan Verifikasi Dokumen' : 'Dokumen Perlu Diperbaiki';
            $message = $request->status === 'lengkap'
                ? "Dokumen {$dokumen->file_name} telah diverifikasi. Catatan: {$request->catatan}"
                : "Dokumen {$dokumen->file_name} ditandai sebagai tidak lengkap. Catatan: {$request->catatan}";

            Notification::createForUser(
                $pengajuan->user_id,
                $type,
                $title,
                $message,
                $pengajuan->id
            );
        } elseif ($request->status === 'tidak_lengkap' && $oldStatus !== 'tidak_lengkap') {
            Notification::createForUser(
                $pengajuan->user_id,
                'warning',
                'Dokumen Perlu Diperbaiki',
                "Dokumen {$dokumen->file_name} ditandai sebagai tidak lengkap. Silakan perbaiki dan upload ulang.",
                $pengajuan->id
            );
        }

        return response()->json($dokumen);
    }

    public function sendNotification(Request $request, string $id)
    {
        $pengajuan = Pengajuan::with('user')->findOrFail($id);

        $request->validate([
            'message' => 'required|string',
            'type' => 'nullable|in:info,warning,success,error',
        ]);

        $type = $request->type ?? 'info';
        $user = $request->user();
        $senderRole = match (true) {
            $user->isAdminBkpsdm() => 'Admin BKPSDM',
            $user->isKepalaBkpsdm() => 'Kepala BKPSDM',
            $user->isAtasan() => 'Atasan',
            default => 'Sistem',
        };

        Notification::createForUser(
            $pengajuan->user_id,
            $type,
            "Pesan dari $senderRole",
            $request->message,
            $pengajuan->id
        );

        return response()->json(['message' => 'Notification sent successfully']);
    }
}
