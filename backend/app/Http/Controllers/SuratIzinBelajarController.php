<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\SuratIzinBelajar;
use App\Models\SuratTugasDinas;
use App\Models\SuratTugasMandiri;
use App\Models\Pengajuan;
use App\Services\QrCodeService;
use App\Services\BarcodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class SuratIzinBelajarController extends Controller
{
    protected QrCodeService $qrCodeService;
    protected BarcodeService $barcodeService;

    public function __construct(QrCodeService $qrCodeService, BarcodeService $barcodeService)
    {
        $this->qrCodeService = $qrCodeService;
        $this->barcodeService = $barcodeService;
    }

    /**
     * Authenticate user via token from query parameter
     */
    protected function authenticateViaToken(Request $request): ?\App\Models\User
    {
        $token = $request->query('token');

        if (!$token) {
            return null;
        }

        // Use Sanctum token
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        if ($personalAccessToken) {
            return $personalAccessToken->tokenable;
        }

        return null;
    }

    /**
     * List surat izin belajar for admin.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user->isAdminBkpsdm() && !$user->isKepalaBkpsdm()) {
            return response()->json(['message' => 'Unauthorized. Admin and Kepala BKPSDM only.'], 403);
        }

        $query = SuratIzinBelajar::with([
            'pengajuan.user',
            'pengajuan.jenjang',
            'suratTugasDinas.unitKerja',
            'suratTugasDinas.kepalaDinas',
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
     * Get pending pengajuan (has surat dinas but no surat izin yet).
     */
    public function pending(Request $request)
    {
        $user = auth()->user();

        if (!$user->isAdminBkpsdm() && !$user->isKepalaBkpsdm()) {
            return response()->json(['message' => 'Unauthorized. Admin only.'], 403);
        }

        $query = Pengajuan::with([
            'user',
            'jenjang',
            'suratTugasDinas' => function ($q) {
                $q->with(['unitKerja', 'kepalaDinas']);
            },
        ])
            ->where('status', 'surat_dinas')
            ->whereHas('suratTugasDinas')
            ->whereDoesntHave('suratIzinBelajar');

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
     * Store (generate) new surat izin belajar.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user->isAdminBkpsdm() && !$user->isKepalaBkpsdm()) {
            return response()->json(['message' => 'Unauthorized. Admin only.'], 403);
        }

        $request->validate([
            'pengajuan_id' => 'required|exists:pengajuan,id',
        ]);

        $pengajuan = Pengajuan::with(['suratTugasDinas', 'user', 'jenjang'])->findOrFail($request->pengajuan_id);

        // Check if pengajuan has surat tugas dinas
        if (!$pengajuan->hasSuratTugasDinas()) {
            return response()->json(['message' => 'Pengajuan must have surat tugas dinas first.'], 400);
        }

        // Check if surat izin already exists
        if ($pengajuan->hasSuratIzinBelajar()) {
            return response()->json(['message' => 'Surat izin belajar already exists for this pengajuan.'], 400);
        }

        $suratTugasDinas = $pengajuan->suratTugasDinas;

        DB::beginTransaction();
        try {
            // Generate nomor surat izin belajar
            $year = date('Y');
            $lastNomor = SuratIzinBelajar::where('tahun', $year)->orderBy('id', 'desc')->first();
            $nextNomor = $lastNomor ? ((int) filter_var($lastNomor->nomor_surat, FILTER_SANITIZE_NUMBER_INT) + 1) : 1;
            $nomorSurat = "800.1.3.1/{$nextNomor}/BKPSDM/{$year}";

            $suratIzin = SuratIzinBelajar::create([
                'pengajuan_id' => $pengajuan->id,
                'surat_tugas_dinas_id' => $suratTugasDinas->id,
                'nomor_surat' => $nomorSurat,
                'tahun' => $year,
                'status' => 'draft',
            ]);

            // Generate nomor surat tugas mandiri (otomatis bersamaan)
            $lastNomorTugas = SuratTugasMandiri::where('tahun', $year)->orderBy('id', 'desc')->first();
            $nextNomorTugas = $lastNomorTugas ? str_pad((int) filter_var($lastNomorTugas->nomor_surat, FILTER_SANITIZE_NUMBER_INT) + 1, 3, '0', STR_PAD_LEFT) : '001';

            $suratTugasMandiri = SuratTugasMandiri::create([
                'pengajuan_id' => $pengajuan->id,
                'surat_izin_belajar_id' => $suratIzin->id,
                'surat_tugas_dinas_id' => $suratTugasDinas->id,
                'nomor_surat' => $nextNomorTugas,
                'tahun' => $year,
                'tanggal_surat' => now()->toDateString(),
                'tempat_ttd' => 'Sukabumi',
                'status' => 'draft',
            ]);

            // Update pengajuan status
            $pengajuan->update(['status' => 'surat_izin']);

            DB::commit();

            return response()->json([
                'message' => 'Surat izin belajar dan surat tugas mandiri generated successfully.',
                'data' => [
                    'surat_izin' => $suratIzin->load(['pengajuan.user', 'pengajuan.jenjang', 'suratTugasDinas']),
                    'surat_tugas_mandiri' => $suratTugasMandiri,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to generate surat: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get detail surat izin belajar.
     */
    public function show($id)
    {
        $user = auth()->user();

        if (!$user->isAdminBkpsdm() && !$user->isKepalaBkpsdm()) {
            return response()->json(['message' => 'Unauthorized. Admin only.'], 403);
        }

        $surat = SuratIzinBelajar::with([
            'pengajuan.user',
            'pengajuan.jenjang',
            'pengajuan.dokumen',
            'suratTugasDinas.unitKerja',
            'suratTugasDinas.kepalaDinas',
        ])->findOrFail($id);

        return response()->json(['data' => $surat]);
    }

    /**
     * Preview surat izin belajar (HTML).
     */
    public function preview($id)
    {
        $user = auth()->user();

        if (!$user->isAdminBkpsdm() && !$user->isKepalaBkpsdm()) {
            return response()->json(['message' => 'Unauthorized. Admin only.'], 403);
        }

        $surat = SuratIzinBelajar::with([
            'pengajuan.user.unitKerja',
            'pengajuan.jenjang',
            'suratTugasDinas.unitKerja',
            'suratTugasDinas.kepalaDinas',
        ])->findOrFail($id);

        return view('pdf.surat-izin-belajar', ['surat' => $surat]);
    }

    /**
     * Generate and download PDF.
     */
    public function generatePdf($id)
    {
        $user = auth()->user();

        if (!$user->isAdminBkpsdm() && !$user->isKepalaBkpsdm()) {
            return response()->json(['message' => 'Unauthorized. Admin only.'], 403);
        }

        $surat = SuratIzinBelajar::with([
            'pengajuan.user.unitKerja',
            'pengajuan.jenjang',
            'suratTugasDinas.unitKerja',
            'suratTugasDinas.kepalaDinas',
        ])->findOrFail($id);

        // Generate QR code for verification
        $qrCodeData = json_encode([
            'type' => 'surat_izin_belajar',
            'id' => $surat->id,
            'nomor' => $surat->nomor_surat,
            'signed_at' => $surat->signed_at ?? now()->toIso8601String(),
        ]);
        $qrCodePath = $this->qrCodeService->generateAndSave($qrCodeData, "izin-{$surat->id}");

        // Generate barcode for surat identification
        $barcodePath = $this->barcodeService->generateForSurat($surat->nomor_surat, 'izin', $surat->id);

        // Convert images to base64 for embedding in PDF
        $qrCodeBase64 = 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get($qrCodePath));
        $barcodeBase64 = 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get($barcodePath));

        $pdf = Pdf::loadView('pdf.surat-izin-belajar', [
            'surat' => $surat,
            'qrCodeBase64' => $qrCodeBase64,
            'barcodeBase64' => $barcodeBase64,
        ]);

        $filename = "Surat_Izin_Belajar_{$surat->pengajuan->user->nip}_{$surat->tahun}.pdf";

        // Store file
        $filePath = "surat-izin-belajar/{$filename}";
        Storage::disk('public')->put($filePath, $pdf->output());

        // Update file path
        $surat->update(['file_path' => $filePath]);

        return $pdf->download($filename);
    }

    /**
     * Sign surat izin belajar with TTE.
     */
    public function sign(Request $request, $id)
    {
        $user = auth()->user();

        // Only kepala BKPSDM can sign
        if (!$user->isKepalaBkpsdm() && !$user->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized. Only Kepala BKPSDM can sign.'], 403);
        }

        $surat = SuratIzinBelajar::with([
            'pengajuan.user.unitKerja',
            'pengajuan.jenjang',
            'suratTugasDinas.unitKerja',
            'suratTugasDinas.kepalaDinas',
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
                'type' => 'surat_izin_belajar',
                'id' => $surat->id,
                'nomor' => $surat->nomor_surat,
                'signed_at' => now()->toIso8601String(),
            ]);
            $qrCodePath = $this->qrCodeService->generateAndSave($qrCodeData, "izin-{$surat->id}");

            // Generate barcode for surat identification
            $barcodePath = $this->barcodeService->generateForSurat($surat->nomor_surat, 'izin', $surat->id);

            // Convert images to base64 for embedding in PDF
            $qrCodeBase64 = 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get($qrCodePath));
            $barcodeBase64 = 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get($barcodePath));

            // Generate PDF first before signing
            $pdf = Pdf::loadView('pdf.surat-izin-belajar', [
                'surat' => $surat,
                'qrCodeBase64' => $qrCodeBase64,
                'barcodeBase64' => $barcodeBase64,
            ]);

            $filename = "Surat_Izin_Belajar_{$surat->pengajuan->user->nip}_{$surat->tahun}.pdf";
            $filePath = "surat-izin-belajar/{$filename}";

            // Store file
            Storage::disk('public')->put($filePath, $pdf->output());

            // Update surat with TTE data and file path
            $surat->update([
                'file_path' => $filePath,
                'tte_path' => $request->tte_path ?? $filePath, // Use same file if TTE not provided
                'qr_code' => $qrCodeData,
                'status' => 'signed',
                'signed_at' => now(),
                'signed_by' => $user->name,
                'signed_by_nip' => $user->nip,
            ]);

            // Update pengajuan status to selesai (process completed)
            $pengajuan = $surat->pengajuan;
            $pengajuan->update(['status' => 'selesai']);

            // Send notification to pemohon
            Notification::createForUser(
                $pengajuan->user_id,
                'success',
                'Surat Izin Belajar Telah Terbit',
                "Surat Izin Belajar Anda dengan nomor {$surat->nomor_surat} telah ditandatangani. Silakan download surat di menu Riwayat Pengajuan.",
                $pengajuan->id
            );

            DB::commit();

            return response()->json([
                'message' => 'Surat izin belajar signed successfully.',
                'data' => $surat->load(['pengajuan.user', 'pengajuan.jenjang', 'suratTugasDinas']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to sign surat izin belajar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Download signed surat izin belajar.
     */
    public function download(Request $request, $id)
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

        $surat = SuratIzinBelajar::with([
            'pengajuan.user.unitKerja',
            'pengajuan.jenjang',
            'suratTugasDinas.unitKerja',
            'suratTugasDinas.kepalaDinas',
        ])->findOrFail($id);

        // Check permission - admin/kepala can download all, user can only download own
        if (!$user->isAdminBkpsdm() && !$user->isKepalaBkpsdm() && $surat->pengajuan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$surat->isSigned()) {
            return response()->json(['message' => 'Surat has not been signed yet.'], 400);
        }

        // Generate PDF on-the-fly if file doesn't exist
        if (!$surat->file_path || !Storage::disk('public')->exists($surat->file_path)) {
            // Generate QR code
            $qrCodeBase64 = null;
            if ($surat->qr_code) {
                $qrCodeData = is_string($surat->qr_code) ? $surat->qr_code : json_encode($surat->qr_code);
                $qrCodePath = $this->qrCodeService->generateAndSave($qrCodeData, "izin-{$surat->id}");
                $qrCodeBase64 = 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get($qrCodePath));
            }

            // Generate barcode
            $barcodePath = $this->barcodeService->generateForSurat($surat->nomor_surat, 'izin', $surat->id);
            $barcodeBase64 = 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get($barcodePath));

            $pdf = Pdf::loadView('pdf.surat-izin-belajar', [
                'surat' => $surat,
                'qrCodeBase64' => $qrCodeBase64,
                'barcodeBase64' => $barcodeBase64,
            ]);

            $filename = "Surat_Izin_Belajar_{$surat->pengajuan->user->nip}_{$surat->tahun}.pdf";

            // Update file path for future downloads
            $filePath = "surat-izin-belajar/{$filename}";
            Storage::disk('public')->put($filePath, $pdf->output());
            $surat->update(['file_path' => $filePath]);

            return $pdf->download($filename);
        }

        return Storage::disk('public')->download($surat->file_path);
    }

    /**
     * Get surat izin belajar by pengajuan_id.
     */
    public function getByPengajuan($pengajuanId)
    {
        $user = auth()->user();

        $pengajuan = Pengajuan::findOrFail($pengajuanId);

        // Check permission - admin/kepala can see all, user can only see own
        if (!$user->isAdminBkpsdm() && !$user->isKepalaBkpsdm() && $pengajuan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $surat = $pengajuan->suratIzinBelajar()->with(['suratTugasDinas'])->first();

        if (!$surat) {
            return response()->json(['message' => 'Surat izin belajar not found'], 404);
        }

        return response()->json(['data' => $surat]);
    }

    /**
     * PDF Editor - preview with custom data for editing.
     */
    public function editorPreview(Request $request)
    {
        // Authenticate using token from query parameter
        $user = $this->authenticateViaToken($request);

        if (!$user) {
            return response()->json(['message' => 'Unauthorized. Admin only.'], 401);
        }

        if (!$user->isAdminBkpsdm() && !$user->isKepalaBkpsdm()) {
            return response()->json(['message' => 'Forbidden. Admin only.'], 403);
        }

        // Get pengajuan or use default data for preview
        $pengajuanId = $request->pengajuan_id;
        $pengajuan = $pengajuanId ? Pengajuan::with([
            'user.unitKerja',
            'jenjang',
            'suratTugasDinas.unitKerja',
            'suratTugasDinas.kepalaDinas',
        ])->findOrFail($pengajuanId) : null;

        // Create dummy surat object for preview
        $surat = new \stdClass();
        $surat->id = 0;
        $surat->nomor_surat = $request->nomor_surat ?? '800.1.3.1/001/BKPSDM/' . date('Y');
        $surat->tahun = $request->tahun ?? date('Y');
        $surat->tanggal_surat = $request->tanggal_surat ?? date('d F Y');
        $surat->tempat_ttd = $request->tempat_ttd ?? 'Sukabumi';
        $surat->status = 'preview';

        // Use pengajuan data if available, otherwise use defaults
        if ($pengajuan) {
            $surat->pengajuan = $pengajuan;
            $surat->suratTugasDinas = $pengajuan->suratTugasDinas;
        } else {
            // Default dummy data
            $surat->pengajuan = new \stdClass();
            $surat->pengajuan->user = new \stdClass();
            $surat->pengajuan->user->name = $request->nama ?? 'Nama Pegawai';
            $surat->pengajuan->user->nip = $request->nip ?? '198001012010011001';
            $surat->pengajuan->user->pangkat_gol = $request->pangkat ?? 'Pembina (IV/a)';
            $surat->pengajuan->user->jabatan = $request->jabatan ?? 'Jabatan Pegawai';
            $surat->pengajuan->user->unitKerja = new \stdClass();
            $surat->pengajuan->user->unitKerja->nama = $request->unit_kerja ?? 'Dinas Pemerintahan';

            $surat->pengajuan->jenjang = new \stdClass();
            $surat->pengajuan->jenjang->nama = $request->jenjang ?? 'Magister (S2)';

            $surat->pengajuan->nama_prodi = $request->nama_prodi ?? 'Magister Administrasi Publik';
            $surat->pengajuan->perguruan_tinggi = $request->perguruan_tinggi ?? 'Universitas Indonesia';
            $surat->pengajuan->lokasi_pt = $request->lokasi_pt ?? 'Depok, Jawa Barat';

            $surat->suratTugasDinas = new \stdClass();
            $surat->suratTugasDinas->nomor_surat = $request->nomor_surat_dinas ?? '001/DK/Mei/' . date('Y');
            $surat->suratTugasDinas->tanggal_mulai = $request->tanggal_mulai ?? date('Y-m-d');
            $surat->suratTugasDinas->tanggal_selesai = $request->tanggal_selesai ?? date('Y-m-d', strtotime('+2 years'));
            $surat->suratTugasDinas->unitKerja = new \stdClass();
            $surat->suratTugasDinas->unitKerja->nama = $request->dinas ?? 'Dinas Pendidikan';
            $surat->suratTugasDinas->kepalaDinas = new \stdClass();
            $surat->suratTugasDinas->kepalaDinas->nama = $request->nama_kepala_dinas ?? 'Nama Kepala Dinas';
            $surat->suratTugasDinas->kepalaDinas->nip = $request->nip_kepala_dinas ?? '197001011995031001';
        }

        // Override data with request values for live preview
        if ($request->nama) $surat->pengajuan->user->name = $request->nama;
        if ($request->nip) $surat->pengajuan->user->nip = $request->nip;
        if ($request->pangkat) $surat->pengajuan->user->pangkat_gol = $request->pangkat;
        if ($request->jabatan) $surat->pengajuan->user->jabatan = $request->jabatan;
        if ($request->unit_kerja) $surat->pengajuan->user->unitKerja->nama = $request->unit_kerja;
        if ($request->jenjang) $surat->pengajuan->jenjang->nama = $request->jenjang;
        if ($request->nama_prodi) $surat->pengajuan->nama_prodi = $request->nama_prodi;
        if ($request->perguruan_tinggi) $surat->pengajuan->perguruan_tinggi = $request->perguruan_tinggi;
        if ($request->lokasi_pt) $surat->pengajuan->lokasi_pt = $request->lokasi_pt;
        if ($request->tanggal_mulai) $surat->suratTugasDinas->tanggal_mulai = $request->tanggal_mulai;
        if ($request->tanggal_selesai) $surat->suratTugasDinas->tanggal_selesai = $request->tanggal_selesai;
        if ($request->nomor_surat_dinas) $surat->suratTugasDinas->nomor_surat = $request->nomor_surat_dinas;
        if ($request->dinas) $surat->suratTugasDinas->unitKerja->nama = $request->dinas;
        if ($request->nama_kepala_dinas) $surat->suratTugasDinas->kepalaDinas->nama = $request->nama_kepala_dinas;
        if ($request->nip_kepala_dinas) $surat->suratTugasDinas->kepalaDinas->nip = $request->nip_kepala_dinas;

        // Get kepala BKPSDM data
        $kepalaBkpsdm = \App\Models\User::where('role_id', 4)->first();
        if ($kepalaBkpsdm) {
            $surat->kepala_bkpsdm = $kepalaBkpsdm;
        }

        return view('pdf.surat-izin-belajar', [
            'surat' => $surat,
            'preview' => true,
        ]);
    }

    /**
     * Generate PDF from editor.
     */
    public function editorPdf(Request $request)
    {
        // Check token from query parameter (for PDF generation in new tab)
        if ($request->has('token')) {
            $token = $request->query('token');
            // Try Sanctum token first
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if ($personalAccessToken) {
                $user = $personalAccessToken->tokenable;
            } else {
                $user = null;
            }

            if (!$user || (!$user->isAdminBkpsdm() && !$user->isKepalaBkpsdm())) {
                return response()->json(['message' => 'Unauthorized. Admin only.'], 403);
            }
        } else {
            $user = auth()->user();
            if (!$user->isAdminBkpsdm() && !$user->isKepalaBkpsdm()) {
                return response()->json(['message' => 'Unauthorized. Admin only.'], 403);
            }
        }

        // Reuse editorPreview logic
        $response = $this->editorPreview($request);
        $html = $response->getContent();

        $pdf = Pdf::loadHTML($html);

        $filename = "Preview_Surat_Izin_Belajar_" . date('YmdHis') . ".pdf";

        return $pdf->stream($filename);
    }

    /**
     * Verify surat authenticity using QR code.
     */
    public function verify($qrCode)
    {
        $surat = null;

        // Try to decode QR code as JSON first
        $qrData = json_decode($qrCode, true);

        if ($qrData && isset($qrData['type']) && $qrData['type'] === 'surat_izin_belajar' && isset($qrData['id'])) {
            // Find by ID from QR code data
            $surat = SuratIzinBelajar::where('id', $qrData['id'])
                ->with(['pengajuan.user', 'pengajuan.jenjang', 'suratTugasDinas'])
                ->first();
        } else {
            // Legacy: try to find by exact QR code string first
            $surat = SuratIzinBelajar::where('qr_code', $qrCode)
                ->with(['pengajuan.user', 'pengajuan.jenjang', 'suratTugasDinas'])
                ->first();

            // If not found, try by nomor_surat with format parsing
            if (!$surat) {
                // Try to parse full format: 800.1.3.1/001/BKPSDM/2026 or 001/BKPSDM/2026
                if (preg_match('/^[\d.]+\/(\d+)\/[A-Z]+\/(\d{4})$/', $qrCode, $matches)) {
                    // Full format: 800.1.3.1/001/BKPSDM/2026
                    $nomor = $matches[1];
                    $tahun = $matches[2];

                    $surat = SuratIzinBelajar::where('nomor_surat', 'like', "%{$nomor}%")
                        ->where('tahun', $tahun)
                        ->with(['pengajuan.user', 'pengajuan.jenjang', 'suratTugasDinas'])
                        ->first();
                } elseif (preg_match('/^(\d+)\/[A-Z]+\/(\d{4})$/', $qrCode, $matches)) {
                    // Format without prefix: 001/BKPSDM/2026
                    $nomor = $matches[1];
                    $tahun = $matches[2];

                    $surat = SuratIzinBelajar::where('nomor_surat', 'like', "%{$nomor}%")
                        ->where('tahun', $tahun)
                        ->with(['pengajuan.user', 'pengajuan.jenjang', 'suratTugasDinas'])
                        ->first();
                } else {
                    // Last resort: try by exact nomor_surat
                    $surat = SuratIzinBelajar::where('nomor_surat', $qrCode)
                        ->with(['pengajuan.user', 'pengajuan.jenjang', 'suratTugasDinas'])
                        ->first();
                }
            }
        }

        if (!$surat) {
            return response()->json(['message' => 'Surat not found or invalid.'], 404);
        }

        if (!$surat->isSigned()) {
            return response()->json(['message' => 'Surat has not been signed yet.'], 400);
        }

        return response()->json([
            'message' => 'Surat is valid.',
            'data' => [
                'id' => $surat->id,
                'nomor_surat' => $surat->nomor_surat,
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
                'is_valid' => $surat->isSigned(),
            ],
        ]);
    }
}
