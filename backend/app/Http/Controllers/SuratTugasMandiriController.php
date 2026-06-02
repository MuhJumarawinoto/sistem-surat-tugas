<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Pengajuan;
use App\Models\SuratIzinBelajar;
use App\Models\SuratTugasDinas;
use App\Models\SuratTugasMandiri;
use App\Services\QrCodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SuratTugasMandiriController extends Controller
{
    protected $qrCodeService;

    public function __construct(QrCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * List all surat tugas mandiri (Admin only)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = SuratTugasMandiri::with([
            'pengajuan.user',
            'pengajuan.jenjang',
            'suratIzinBelajar',
            'suratTugasDinas',
        ])->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $surat = $query->paginate(10);

        return response()->json($surat);
    }

    /**
     * List pengajuan that need surat tugas mandiri (selesai but no surat yet)
     */
    public function pending(Request $request)
    {
        $user = $request->user();

        if (!$user->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Get pengajuan with status 'selesai' that don't have surat tugas mandiri yet
        $pengajuan = Pengajuan::with(['user', 'jenjang', 'suratIzinBelajar'])
            ->where('status', 'selesai')
            ->whereDoesntHave('suratTugasMandiri')
            ->orderBy('updated_at', 'desc')
            ->paginate(10);

        return response()->json($pengajuan);
    }

    /**
     * Get surat tugas mandiri by pengajuan
     */
    public function getByPengajuan(Request $request, $pengajuanId)
    {
        $user = $request->user();

        $surat = SuratTugasMandiri::with(['suratIzinBelajar', 'suratTugasDinas'])
            ->where('pengajuan_id', $pengajuanId)
            ->first();

        if (!$surat) {
            return response()->json(['message' => 'Surat not found'], 404);
        }

        // Pemohon can only view their own surat
        if ($user->isPemohon() && $surat->pengajuan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($surat);
    }

    /**
     * Get detail surat tugas mandiri
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $surat = SuratTugasMandiri::with([
            'pengajuan.user',
            'pengajuan.jenjang',
            'suratIzinBelajar',
            'suratTugasDinas.unitKerja',
            'suratTugasDinas.kepalaDinas',
        ])->findOrFail($id);

        // Permission check
        if ($user->isPemohon() && $surat->pengajuan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($surat);
    }

    /**
     * Create new surat tugas mandiri
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'pengajuan_id' => 'required|exists:pengajuan,id',
            'nomor_surat' => 'required|string',
            'tahun' => 'required|string|size:4',
            'tanggal_surat' => 'required|date',
            'tempat_ttd' => 'nullable|string',
        ]);

        $pengajuan = Pengajuan::with(['user', 'jenjang', 'suratIzinBelajar', 'suratTugasDinas'])
            ->findOrFail($request->pengajuan_id);

        // Check if pengajuan status is 'selesai'
        if ($pengajuan->status !== 'selesai') {
            return response()->json(['message' => 'Pengajuan must be completed (selesai) first'], 400);
        }

        // Check if surat izin belajar exists
        if (!$pengajuan->suratIzinBelajar || $pengajuan->suratIzinBelajar->isEmpty()) {
            return response()->json(['message' => 'Surat Izin Belajar must be created first'], 400);
        }

        $suratIzinBelajar = $pengajuan->suratIzinBelajar->first();

        DB::beginTransaction();
        try {
            $surat = SuratTugasMandiri::create([
                'pengajuan_id' => $pengajuan->id,
                'surat_izin_belajar_id' => $suratIzinBelajar->id,
                'surat_tugas_dinas_id' => $pengajuan->suratTugasDinas->first()?->id,
                'nomor_surat' => $request->nomor_surat,
                'tahun' => $request->tahun,
                'tanggal_surat' => $request->tanggal_surat,
                'tempat_ttd' => $request->tempat_ttd ?? 'Sukabumi',
                'status' => 'draft',
            ]);

            // Send notification to pemohon
            Notification::createForUser(
                $pengajuan->user_id,
                'info',
                'Surat Tugas Belajar Mandiri Dibuat',
                "Surat Tugas Belajar Mandiri Anda telah dibuat. Nomor: {$surat->nomor_surat}/TBM/{$surat->tahun}",
                $pengajuan->id
            );

            DB::commit();

            return response()->json($surat->load(['pengajuan.user', 'suratIzinBelajar']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create surat: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update surat tugas mandiri (draft only)
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $surat = SuratTugasMandiri::findOrFail($id);

        if ($surat->status !== 'draft') {
            return response()->json(['message' => 'Can only update draft surat'], 400);
        }

        $request->validate([
            'nomor_surat' => 'sometimes|required|string',
            'tahun' => 'sometimes|required|string|size:4',
            'tanggal_surat' => 'sometimes|required|date',
            'tempat_ttd' => 'nullable|string',
        ]);

        $surat->update($request->only(['nomor_surat', 'tahun', 'tanggal_surat', 'tempat_ttd']));

        return response()->json($surat);
    }

    /**
     * Delete surat tugas mandiri (draft only)
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $surat = SuratTugasMandiri::findOrFail($id);

        if ($surat->status !== 'draft') {
            return response()->json(['message' => 'Can only delete draft surat'], 400);
        }

        $surat->delete();

        return response()->json(['message' => 'Surat deleted successfully']);
    }

    /**
     * Generate PDF preview
     */
    public function preview(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $surat = SuratTugasMandiri::with([
            'pengajuan.user.unitKerja',
            'pengajuan.jenjang',
            'suratIzinBelajar',
            'suratTugasDinas.unitKerja',
            'suratTugasDinas.kepalaDinas',
        ])->findOrFail($id);

        // Path untuk logo (gunakan storage link)
        $logoPath = Storage::disk('public')->path('logo-kab-sukabumi.png');
        $logoBsrePath = Storage::disk('public')->path('logo-bsre.png');

        return view('pdf.surat-tugas-mandiri', [
            'surat' => $surat,
            'logo_path' => $logoPath,
            'logo_bsre_path' => $logoBsrePath,
            'preview' => true,
        ]);
    }

    /**
     * Generate and download PDF
     */
    public function generatePdf(Request $request, $id)
    {
        // Check token from query parameter (for direct download link)
        if ($request->has('token')) {
            $token = $request->query('token');
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if (!$personalAccessToken) {
                return response()->json(['message' => 'Invalid token'], 401);
            }
            $user = $personalAccessToken->tokenable;
        } else {
            $user = $request->user();
        }

        $surat = SuratTugasMandiri::with([
            'pengajuan.user.unitKerja',
            'pengajuan.jenjang',
            'suratIzinBelajar',
            'suratTugasDinas.unitKerja',
            'suratTugasDinas.kepalaDinas',
        ])->findOrFail($id);

        // Pemohon can only download their own surat
        if ($user->isPemohon() && $surat->pengajuan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Path untuk logo (gunakan storage link)
        $logoPath = Storage::disk('public')->path('logo-kab-sukabumi.png');
        $logoBsrePath = Storage::disk('public')->path('logo-bsre.png');

        // Generate QR code for verification if signed
        $qrCodePath = null;
        if ($surat->isSigned()) {
            $qrCodeData = json_encode([
                'type' => 'surat_tugas_mandiri',
                'id' => $surat->id,
                'nomor' => $surat->nomor_surat,
                'signed_at' => $surat->signed_at ? $surat->signed_at->toIso8601String() : now()->toIso8601String(),
            ]);
            $qrCodePath = $this->qrCodeService->generateAndSave($qrCodeData, "tbm-{$surat->id}");
        }

        $pdf = Pdf::loadView('pdf.surat-tugas-mandiri', [
            'surat' => $surat,
            'qrCodePath' => $qrCodePath,
            'logo_path' => $logoPath,
            'logo_bsre_path' => $logoBsrePath,
        ]);

        $filename = "Surat_Tugas_Belajar_Mandiri_{$surat->pengajuan->user->nip}_{$surat->tahun}.pdf";

        return $pdf->stream($filename);
    }

    /**
     * Sign surat with TTE
     */
    public function sign(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->isKepalaBkpsdm()) {
            return response()->json(['message' => 'Only Kepala BKPSDM can sign'], 403);
        }

        $surat = SuratTugasMandiri::with([
            'pengajuan.user',
            'pengajuan.jenjang',
            'suratIzinBelajar',
            'suratTugasDinas',
        ])->findOrFail($id);

        if (!$surat->canBeSigned()) {
            return response()->json(['message' => 'Surat has already been signed or cannot be signed.'], 400);
        }

        $request->validate([
            'tte_path' => 'nullable|string',
            'qr_code' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Generate QR code for verification
            $qrCodeData = json_encode([
                'type' => 'surat_tugas_mandiri',
                'id' => $surat->id,
                'nomor' => $surat->nomor_surat,
                'signed_at' => now()->toIso8601String(),
            ]);
            $qrCodePath = $this->qrCodeService->generateAndSave($qrCodeData, "tbm-{$surat->id}");

            // Path untuk logo
            $logoPath = public_path('images/logo-kab-sukabumi.png');
            $logoBsrePath = public_path('images/logo-bsre.png');

            // Generate PDF first before signing
            $pdf = Pdf::loadView('pdf.surat-tugas-mandiri', [
                'surat' => $surat,
                'qrCodePath' => $qrCodePath,
                'logo_path' => $logoPath,
                'logo_bsre_path' => $logoBsrePath,
            ]);

            $filename = "Surat_Tugas_Belajar_Mandiri_{$surat->pengajuan->user->nip}_{$surat->tahun}.pdf";
            $filePath = "surat-tugas-mandiri/{$filename}";

            // Store file
            Storage::disk('public')->put($filePath, $pdf->output());

            // Update surat with TTE data
            $surat->update([
                'file_path' => $filePath,
                'tte_path' => $request->tte_path ?? $filePath,
                'qr_code' => $qrCodeData,
                'status' => 'signed',
                'signed_at' => now(),
                'signed_by' => $user->name,
                'signed_by_nip' => $user->nip,
            ]);

            // Send notification to pemohon
            Notification::createForUser(
                $surat->pengajuan->user_id,
                'success',
                'Surat Tugas Belajar Mandiri Telah Ditandatangani',
                "Surat Tugas Belajar Mandiri Anda dengan nomor {$surat->nomor_surat} telah ditandatangani. Silakan download surat di menu Riwayat Pengajuan.",
                $surat->pengajuan->id
            );

            DB::commit();

            return response()->json([
                'message' => 'Surat Tugas Belajar Mandiri signed successfully.',
                'data' => $surat->load(['pengajuan.user', 'pengajuan.jenjang', 'suratIzinBelajar']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to sign surat: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Download signed surat
     */
    public function download(Request $request, $id)
    {
        // Check token from query parameter (for direct download link)
        if ($request->has('token')) {
            $token = $request->query('token');
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if (!$personalAccessToken) {
                return response()->json(['message' => 'Invalid token'], 401);
            }
            $user = $personalAccessToken->tokenable;
        } else {
            $user = $request->user();
        }

        $surat = SuratTugasMandiri::with('pengajuan')->findOrFail($id);

        if (!$surat->isSigned()) {
            return response()->json(['message' => 'Surat has not been signed yet.'], 400);
        }

        // Pemohon can only download their own surat
        if ($user->isPemohon() && $surat->pengajuan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $filePath = storage_path('app/public/' . $surat->file_path);

        if (!file_exists($filePath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return response()->download($filePath, "Surat_Tugas_Belajar_Mandiri_{$surat->tahun}.pdf");
    }

    /**
     * Verify surat authenticity via QR code
     */
    public function verify(Request $request, $qrCode)
    {
        $surat = null;

        // Try to decode QR code as JSON first
        $qrData = json_decode($qrCode, true);

        if ($qrData && isset($qrData['type']) && $qrData['type'] === 'surat_tugas_mandiri' && isset($qrData['id'])) {
            // Find by ID from QR code data
            $surat = SuratTugasMandiri::with(['pengajuan.user', 'pengajuan.jenjang', 'suratTugasDinas'])
                ->where('id', $qrData['id'])
                ->first();
        } else {
            // Try to parse full format: 800.1.3.1/001/TBM/2026 or 001/TBM/2026
            if (preg_match('/^[\d.]+\/(\d+)\/TBM\/(\d{4})$/', $qrCode, $matches)) {
                // Full format: 800.1.3.1/001/TBM/2026
                $nomor = $matches[1];
                $tahun = $matches[2];

                $surat = SuratTugasMandiri::with(['pengajuan.user', 'pengajuan.jenjang', 'suratTugasDinas'])
                    ->where('nomor_surat', 'like', "%{$nomor}%")
                    ->where('tahun', $tahun)
                    ->first();
            } elseif (preg_match('/^(\d+)\/TBM\/(\d{4})$/', $qrCode, $matches)) {
                // Format without prefix: 001/TBM/2026
                $nomor = $matches[1];
                $tahun = $matches[2];

                $surat = SuratTugasMandiri::with(['pengajuan.user', 'pengajuan.jenjang', 'suratTugasDinas'])
                    ->where('nomor_surat', 'like', "%{$nomor}%")
                    ->where('tahun', $tahun)
                    ->first();
            } else {
                // Legacy: try to find by ID or nomor surat
                $surat = SuratTugasMandiri::with(['pengajuan.user', 'pengajuan.jenjang', 'suratTugasDinas'])
                    ->where('id', $qrCode)
                    ->first();
            }
        }

        if (!$surat) {
            return response()->json([
                'message' => 'Surat tidak ditemukan atau tidak valid',
            ], 404);
        }

        if (!$surat->isSigned()) {
            return response()->json([
                'message' => 'Surat belum ditandatangani',
                'data' => [
                    'nomor_surat' => $surat->nomor_surat,
                    'is_valid' => false,
                ],
            ], 400);
        }

        return response()->json([
            'message' => 'Surat is valid.',
            'data' => [
                'id' => $surat->id,
                'nomor_surat' => $surat->nomor_surat,
                'tahun' => $surat->tahun,
                'tanggal_ttd' => $surat->signed_at,
                'kepala_dinas' => [
                    'nama' => $surat->signed_by,
                    'nip' => $surat->signed_by_nip,
                ],
                'pengajuan' => [
                    'nama' => $surat->pengajuan->user->name,
                    'nip' => $surat->pengajuan->user->nip,
                    'jenjang' => $surat->pengajuan->jenjang->nama_jenjang ?? '-',
                    'prodi' => $surat->pengajuan->nama_prodi,
                    'perguruan_tinggi' => $surat->pengajuan->perguruan_tinggi,
                ],
                'is_valid' => $surat->status === 'signed',
            ],
        ]);
    }
}
