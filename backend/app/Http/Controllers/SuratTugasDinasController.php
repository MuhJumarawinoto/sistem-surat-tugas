<?php

namespace App\Http\Controllers;

use App\Models\SuratTugasDinas;
use App\Models\Pengajuan;
use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class SuratTugasDinasController extends Controller
{
    protected QrCodeService $qrCodeService;

    public function __construct(QrCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }
    /**
     * List surat tugas dinas.
     * New simplified flow: Admin BKPSDM manages all surat tugas dinas
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user->isAdminBkpsdm() && !$user->isKepalaBkpsdm()) {
            return response()->json(['message' => 'Unauthorized. Admin and Kepala BKPSDM only.'], 403);
        }

        $query = SuratTugasDinas::with([
            'pengajuan.user',
            'pengajuan.jenjang',
            'pengajuan.suratIzinBelajar',
            'unitKerja',
            'kepalaDinas'
        ]);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by year
        if ($request->has('tahun')) {
            $query->where('tahun', $request->tahun);
        }

        $surat = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 10);

        return response()->json([
            'data' => $surat->items(),
            'meta' => [
                'current_page' => $surat->currentPage(),
                'last_page' => $surat->lastPage(),
                'per_page' => $surat->perPage(),
                'total' => $surat->total(),
            ],
        ]);
    }

    /**
     * Get pending pengajuan (signed but no surat tugas dinas yet).
     * New simplified flow: signed → Admin creates Surat Tugas Dinas with same TTE
     */
    public function pending(Request $request)
    {
        $user = auth()->user();

        if (!$user->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized. Admin BKPSDM only.'], 403);
        }

        $query = Pengajuan::with(['user', 'jenjang', 'suratIzinBelajar', 'user.unitKerja'])
            ->where('status', 'signed')
            ->whereDoesntHave('suratTugasDinas');

        $pengajuan = $query->orderBy('updated_at', 'desc')->paginate($request->per_page ?? 10);

        return response()->json([
            'data' => $pengajuan->items(),
            'meta' => [
                'current_page' => $pengajuan->currentPage(),
                'last_page' => $pengajuan->lastPage(),
                'per_page' => $pengajuan->perPage(),
                'total' => $pengajuan->total(),
            ],
        ]);
    }

    /**
     * Store new surat tugas dinas.
     * New simplified flow: Admin creates Surat Tugas Dinas with same TTE from Surat Izin Belajar
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        // Only Admin BKPSDM can create surat tugas dinas in new flow
        if (!$user->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized. Only Admin BKPSDM can create surat tugas dinas.'], 403);
        }

        $request->validate([
            'pengajuan_id' => 'required|exists:pengajuan,id',
            'nomor_surat' => 'required|string|max:50',
            'bulan' => 'required|string|max:20',
            'tahun' => 'required|string|max:4',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
            'tanggal_ttd' => 'required|date',
            'tempat_ttd' => 'nullable|string|max:100',
        ]);

        $pengajuan = Pengajuan::with(['suratIzinBelajar', 'user'])->findOrFail($request->pengajuan_id);

        // Check if pengajuan is signed (has Surat Izin Belajar)
        if ($pengajuan->status !== 'signed' || !$pengajuan->suratIzinBelajar) {
            return response()->json(['message' => 'Pengajuan must have signed Surat Izin Belajar first.'], 400);
        }

        // Check if surat tugas dinas already exists
        if ($pengajuan->hasSuratTugasDinas()) {
            return response()->json(['message' => 'Surat tugas dinas already exists for this pengajuan.'], 400);
        }

        $suratIzin = $pengajuan->suratIzinBelajar;
        $unitKerjaId = $pengajuan->user->unit_kerja_id;

        // Find the kepala unit for this unit kerja (for kepala_dinas_id)
        $kepalaUnit = User::where('unit_kerja_id', $unitKerjaId)
            ->where('is_kepala_unit', true)
            ->first();

        // If no kepala unit found, use Kepala BKPSDM as fallback
        if (!$kepalaUnit) {
            $kepalaUnit = User::whereHas('role', function ($query) {
                $query->where('slug', 'kepala_bkpsdm');
            })->first();
        }

        // Check unique nomor surat (per year for BKPSDM)
        $year = $request->tahun;
        $fullNomorSurat = "800.1.3.1/{$request->nomor_surat}/BKPSDM/{$year}";
        $exists = SuratTugasDinas::where('nomor_surat', $fullNomorSurat)->exists();

        if ($exists) {
            return response()->json(['message' => 'Nomor surat already exists for this year.'], 400);
        }

        DB::beginTransaction();
        try {
            $surat = SuratTugasDinas::create([
                'pengajuan_id' => $pengajuan->id,
                'unit_kerja_id' => $unitKerjaId,
                'kepala_dinas_id' => $kepalaUnit->id,
                'nomor_surat' => $fullNomorSurat,
                'bulan' => $request->bulan,
                'tahun' => $year,
                'tanggal_mulai' => $request->tanggal_mulai,
                'tanggal_selesai' => $request->tanggal_selesai,
                'tanggal_ttd' => $request->tanggal_ttd,
                'tempat_ttd' => $request->tempat_ttd ?? 'Sukabumi',
                'status' => 'signed',
                'signed_at' => $suratIzin->signed_at, // Use same TTE date as Surat Izin
            ]);

            // Update pengajuan status to selesai
            $pengajuan->update(['status' => 'selesai']);

            DB::commit();

            return response()->json([
                'message' => 'Surat tugas dinas created successfully.',
                'data' => $surat->load(['pengajuan.user', 'pengajuan.jenjang', 'unitKerja', 'kepalaDinas']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create surat tugas dinas: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get detail surat tugas dinas.
     */
    public function show($id)
    {
        $user = auth()->user();

        $surat = SuratTugasDinas::with([
            'pengajuan.user',
            'pengajuan.jenjang',
            'pengajuan.dokumen',
            'pengajuan.suratIzinBelajar',
            'unitKerja',
            'kepalaDinas'
        ])->findOrFail($id);

        // Check permission
        if ($user->isKepalaUnit() && $surat->unit_kerja_id !== $user->unit_kerja_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['data' => $surat]);
    }

    /**
     * Preview surat tugas dinas (HTML).
     * Public route - checks token from query parameter.
     */
    public function preview(Request $request, $id)
    {
        // Check token from query parameter (for direct access)
        if ($request->has('token')) {
            $token = $request->query('token');
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if ($personalAccessToken) {
                $user = $personalAccessToken->tokenable;
            } else {
                $user = null;
            }

            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        } else {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        }

        if (!$user->isAdminBkpsdm() && !$user->isKepalaBkpsdm()) {
            return response()->json(['message' => 'Unauthorized. Admin and Kepala BKPSDM only.'], 403);
        }

        $surat = SuratTugasDinas::with([
            'pengajuan.user',
            'pengajuan.jenjang',
            'pengajuan.suratIzinBelajar',
            'unitKerja',
            'kepalaDinas'
        ])->findOrFail($id);

        // Generate QR code for preview
        $qrCodeData = json_encode([
            'type' => 'surat_tugas_dinas',
            'id' => $surat->id,
            'nomor' => "{$surat->nomor_surat}",
            'created_at' => $surat->created_at->toIso8601String(),
        ]);
        $qrCodePath = $this->qrCodeService->generateAndSave($qrCodeData, "tugas-{$surat->id}");
        $qrCodeBase64 = 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get($qrCodePath));

        // Logo
        $logoPath = 'logo-kab-sukabumi.png';
        $logoContent = Storage::disk('public')->exists($logoPath)
            ? Storage::disk('public')->get($logoPath)
            : Storage::disk('public')->get('surat-tugas-dinas/' . $logoPath);
        $logoBase64 = 'data:image/png;base64,' . base64_encode($logoContent);

        // Background Image
        $bgPath = 'bg-pdf.png';
        $bgBase64 = null;
        if (Storage::disk('public')->exists($bgPath)) {
            $bgContent = Storage::disk('public')->get($bgPath);
            $bgBase64 = 'data:image/png;base64,' . base64_encode($bgContent);
        }

        return view('pdf.surat-tugas-dinas', [
            'surat' => $surat,
            'qrCodeBase64' => $qrCodeBase64,
            'logoBase64' => $logoBase64,
            'bgBase64' => $bgBase64,
            'isPreview' => true,
        ]);
    }

    /**
     * Update surat tugas dinas (draft only).
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();

        $surat = SuratTugasDinas::findOrFail($id);

        // Check permission
        if ($user->isKepalaUnit() && $surat->kepala_dinas_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$surat->canBeEdited()) {
            return response()->json(['message' => 'Only draft surat can be edited.'], 400);
        }

        $request->validate([
            'nomor_surat' => 'sometimes|required|string|max:50',
            'bulan' => 'sometimes|required|string|max:20',
            'tahun' => 'sometimes|required|string|max:4',
            'tanggal_mulai' => 'sometimes|required|date',
            'tanggal_selesai' => 'sometimes|required|date|after:tanggal_mulai',
            'tanggal_ttd' => 'sometimes|required|date',
            'tempat_ttd' => 'nullable|string|max:100',
        ]);

        $surat->update($request->only([
            'nomor_surat',
            'bulan',
            'tahun',
            'tanggal_mulai',
            'tanggal_selesai',
            'tanggal_ttd',
            'tempat_ttd',
        ]));

        return response()->json([
            'message' => 'Surat tugas dinas updated successfully.',
            'data' => $surat->load(['pengajuan.user', 'pengajuan.jenjang', 'unitKerja', 'kepalaDinas']),
        ]);
    }

    /**
     * Delete surat tugas dinas (draft only).
     */
    public function destroy($id)
    {
        $user = auth()->user();

        $surat = SuratTugasDinas::findOrFail($id);

        // Check permission
        if ($user->isKepalaUnit() && $surat->kepala_dinas_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$surat->canBeDeleted()) {
            return response()->json(['message' => 'Only draft surat can be deleted.'], 400);
        }

        // Update pengajuan status back to verified
        $pengajuan = $surat->pengajuan;
        $pengajuan->update(['status' => 'verified']);

        $surat->delete();

        return response()->json(['message' => 'Surat tugas dinas deleted successfully.']);
    }

    /**
     * Generate and download PDF.
     */
    public function generatePdf(Request $request, $id)
    {
        // Check token from query parameter (for direct download link)
        if ($request->has('token')) {
            $token = $request->query('token');
            // Try Sanctum token first
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if ($personalAccessToken) {
                $user = $personalAccessToken->tokenable;
            } else {
                $user = null;
            }

            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        } else {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        }

        $surat = SuratTugasDinas::with([
            'pengajuan.user',
            'pengajuan.jenjang',
            'pengajuan.suratIzinBelajar',
            'unitKerja',
            'kepalaDinas'
        ])->findOrFail($id);

        // Check permission:
        // - Admin BKPSDM and Kepala BKPSDM can download all surat
        // - Kepala Unit can only download surat from their unit kerja
        // - User can download their own surat
        $canDownload = $user->isAdminBkpsdm() || $user->isKepalaBkpsdm();

        if (!$canDownload && $user->isKepalaUnit()) {
            $canDownload = $surat->unit_kerja_id === $user->unit_kerja_id;
        }

        if (!$canDownload && $surat->pengajuan->user_id === $user->id) {
            $canDownload = true;
        }

        if (!$canDownload) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Generate QR code for verification
        $qrCodeData = json_encode([
            'type' => 'surat_tugas_dinas',
            'id' => $surat->id,
            'nomor' => "{$surat->nomor_surat}/DK/{$surat->bulan}/{$surat->tahun}",
            'created_at' => $surat->created_at->toIso8601String(),
        ]);
        $qrCodePath = $this->qrCodeService->generateAndSave($qrCodeData, "tugas-{$surat->id}");

        // Convert QR code to base64 for PDF embedding
        $qrCodeBase64 = 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get($qrCodePath));

        // Logo path - use storage disk instead of public path
        $logoPath = 'logo-kab-sukabumi.png';
        $logoContent = Storage::disk('public')->exists($logoPath)
            ? Storage::disk('public')->get($logoPath)
            : Storage::disk('public')->get('surat-tugas-dinas/' . $logoPath);
        $logoBase64 = 'data:image/png;base64,' . base64_encode($logoContent);

        // Background Image
        $bgPath = 'bg-pdf.png';
        $bgBase64 = null;
        if (Storage::disk('public')->exists($bgPath)) {
            $bgContent = Storage::disk('public')->get($bgPath);
            $bgBase64 = 'data:image/png;base64,' . base64_encode($bgContent);
        }

        $pdf = Pdf::loadView('pdf.surat-tugas-dinas', [
            'surat' => $surat,
            'qrCodeBase64' => $qrCodeBase64,
            'logoBase64' => $logoBase64,
            'bgBase64' => $bgBase64,
            'isPreview' => false,
        ]);

        $filename = "Surat_Tugas_Belajar_{$surat->pengajuan->user->nip}_{$surat->tahun}.pdf";

        // Store file
        $filePath = "surat-tugas-dinas/{$filename}";
        Storage::disk('public')->put($filePath, $pdf->output());

        // Update file path and QR code
        $surat->update(['file_path' => $filePath]);

        return $pdf->download($filename);
    }

    /**
     * Get surat tugas dinas by pengajuan_id.
     */
    public function getByPengajuan($pengajuanId)
    {
        $user = auth()->user();

        $pengajuan = Pengajuan::findOrFail($pengajuanId);

        // Check permission - admin can see all, user can only see own
        if (!$user->isAdminBkpsdm() && $pengajuan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $surat = $pengajuan->suratTugasDinas;

        if (!$surat) {
            return response()->json(['message' => 'Surat tugas dinas not found'], 404);
        }

        return response()->json(['data' => $surat]);
    }

    /**
     * Verify surat authenticity using QR code.
     */
    public function verify($qrCode)
    {
        $surat = null;

        // Try to decode QR code as JSON first
        $qrData = json_decode($qrCode, true);

        if ($qrData && isset($qrData['type']) && $qrData['type'] === 'surat_tugas_dinas' && isset($qrData['id'])) {
            // Find by ID from QR code data
            $surat = SuratTugasDinas::where('id', $qrData['id'])
                ->with(['pengajuan.user', 'pengajuan.jenjang', 'unitKerja', 'kepalaDinas'])
                ->first();
        } else {
            // Try to parse full format: nomor/DK/bulan/tahun or nomor/bulan/tahun
            if (preg_match('/^(\d+)\/DK\/([A-Za-z]+)\/(\d{4})$/', $qrCode, $matches)) {
                // Full format: 001/DK/Mei/2026
                $nomor = $matches[1];
                $bulan = $matches[2];
                $tahun = $matches[3];

                $surat = SuratTugasDinas::where('nomor_surat', $nomor)
                    ->where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->with(['pengajuan.user', 'pengajuan.jenjang', 'unitKerja', 'kepalaDinas'])
                    ->first();
            } elseif (preg_match('/^(\d+)\/([A-Za-z]+)\/(\d{4})$/', $qrCode, $matches)) {
                // Format without DK: 001/Mei/2026
                $nomor = $matches[1];
                $bulan = $matches[2];
                $tahun = $matches[3];

                $surat = SuratTugasDinas::where('nomor_surat', $nomor)
                    ->where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->with(['pengajuan.user', 'pengajuan.jenjang', 'unitKerja', 'kepalaDinas'])
                    ->first();
            } else {
                // Legacy: try to find by nomor surat only
                $surat = SuratTugasDinas::where('nomor_surat', $qrCode)
                    ->with(['pengajuan.user', 'pengajuan.jenjang', 'unitKerja', 'kepalaDinas'])
                    ->first();
            }
        }

        if (!$surat) {
            return response()->json(['message' => 'Surat not found or invalid.'], 404);
        }

        return response()->json([
            'message' => 'Surat is valid.',
            'data' => [
                'id' => $surat->id,
                'nomor_surat' => "{$surat->nomor_surat}/DK/{$surat->bulan}/{$surat->tahun}",
                'tanggal_ttd' => $surat->tanggal_ttd,
                'kepala_dinas' => [
                    'nama' => $surat->kepalaDinas->name,
                    'nip' => $surat->kepalaDinas->nip,
                ],
                'pengajuan' => [
                    'nama' => $surat->pengajuan->user->name,
                    'nip' => $surat->pengajuan->user->nip,
                    'jenjang' => $surat->pengajuan->jenjang->nama_jenjang,
                    'prodi' => $surat->pengajuan->nama_prodi,
                    'perguruan_tinggi' => $surat->pengajuan->perguruan_tinggi,
                ],
                'is_valid' => $surat->status === 'signed',
            ],
        ]);
    }
}
