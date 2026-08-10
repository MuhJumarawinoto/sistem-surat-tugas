<?php

use App\Http\Controllers\AdminPengajuanController;
use App\Http\Controllers\Api\PgaController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DokumenController;
use App\Http\Controllers\JenisDokumenController;
use App\Http\Controllers\JenisDokumenPgaController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PDDiktiController;
use App\Http\Controllers\PDDiktiSyncController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\PegawaiSyncController;
use App\Http\Controllers\PengajuanController;
use App\Http\Controllers\SuratIzinBelajarController;
use App\Http\Controllers\SuratTugasController;
use App\Http\Controllers\SuratTugasDinasController;
use App\Http\Controllers\SuratTugasMandiriController;
use App\Http\Controllers\VerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('login');

// Master Data Routes (Public - no auth required)
Route::prefix('master')->group(function () {
    Route::get('/jenjang', [MasterController::class, 'jenjang']);
    Route::get('/unit-kerja', [MasterController::class, 'unitKerja']);
    Route::get('/status-pengajuan', [MasterController::class, 'statusPengajuan']);
    Route::get('/jenis-dokumen', [MasterController::class, 'jenisDokumen']);
    Route::get('/jenis-dokumen-pga', [MasterController::class, 'jenisDokumenPga']);
    Route::get('/akreditasi', [MasterController::class, 'akreditasi']);
    Route::get('/perguruan-tinggi', [MasterController::class, 'perguruanTinggi']);
    Route::get('/prodi', [MasterController::class, 'prodi']);
});

// PDDikti API (Public - no auth required)
Route::prefix('pddikti')->group(function () {
    Route::get('/universitas', [PDDiktiController::class, 'searchUniversitas']);
    Route::get('/universitas/{id}/detail', [PDDiktiController::class, 'getUniversitasDetail']);
    Route::get('/universitas/{id}/prodi', [PDDiktiController::class, 'getUniversitasProdi']);
    Route::get('/prodi', [PDDiktiController::class, 'searchProdi']);
    Route::get('/prodi/{id}', [PDDiktiController::class, 'getProdiDetail']);
    Route::get('/search', [PDDiktiController::class, 'searchAll']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::prefix('pegawai')->group(function () {
        Route::get('/', [PegawaiController::class, 'index']);
        Route::get('/roles', [PegawaiController::class, 'getRoles']);
        Route::get('/unit-kerjas', [PegawaiController::class, 'getUnitKerjas']);
        Route::get('/{id}', [PegawaiController::class, 'show']);
        Route::get('/{id}/structure', [PegawaiController::class, 'getStructure']);
        Route::put('/{id}', [PegawaiController::class, 'update']);
        Route::delete('/{id}', [PegawaiController::class, 'destroy']);
    });

    // Pegawai Sync (Admin only)
    Route::prefix('admin/pegawai-sync')->middleware('admin')->group(function () {
        Route::post('/import', [PegawaiSyncController::class, 'importFromJson']);
        Route::post('/sync-simpeg', [PegawaiSyncController::class, 'syncFromSimpeg']);
        Route::get('/test-connection', [PegawaiSyncController::class, 'testSimpegConnection']);
        Route::get('/stats', [PegawaiSyncController::class, 'getStats']);
        Route::get('/template', [PegawaiSyncController::class, 'downloadTemplate']);
    });

    // Admin Pengajuan Management (Admin only)
    Route::prefix('admin/pengajuan')->middleware('admin')->group(function () {
        Route::delete('/{id}', [AdminPengajuanController::class, 'destroy']);
        Route::post('/delete-multiple', [AdminPengajuanController::class, 'destroyMultiple']);
    });

    // Verification routes
    Route::prefix('verification')->group(function () {
        Route::get('/rules', [VerificationController::class, 'getRules']);
        Route::get('/categories', [VerificationController::class, 'getJabatanCategories']);
        Route::get('/pengajuan/{id}', [VerificationController::class, 'getVerificationInfo']);
    });

    Route::prefix('pengajuan')->group(function () {
        Route::get('/', [PengajuanController::class, 'index']);
        Route::post('/', [PengajuanController::class, 'store']);
        Route::get('/nomor', [PengajuanController::class, 'getNomor']);
        Route::get('/{id}', [PengajuanController::class, 'show']);
        Route::put('/{id}', [PengajuanController::class, 'update']);
        Route::delete('/{id}', [PengajuanController::class, 'destroy']);
        Route::post('/{id}/submit', [PengajuanController::class, 'submit']);
        Route::post('/{id}/cancel', [PengajuanController::class, 'cancel']);
        Route::post('/{id}/restore', [PengajuanController::class, 'restore']);

        Route::prefix('/{pengajuanId}/dokumen')->group(function () {
            Route::get('/', [DokumenController::class, 'index']);
            Route::post('/', [DokumenController::class, 'store']);
        });

        // Approval & Verification routes (Admin only)
        Route::post('/{id}/approve', [ApprovalController::class, 'approveAdmin'])->middleware('admin');
        Route::post('/{id}/reject', [ApprovalController::class, 'reject']);
        Route::post('/{id}/approve-atasan', [ApprovalController::class, 'approveAtasan']);
        Route::post('/{id}/verify-documents', [ApprovalController::class, 'verifyDocuments'])->middleware('admin');
        Route::post('/{id}/send-notification', [ApprovalController::class, 'sendNotification']);
        Route::post('/{id}/generate-surat', [SuratTugasController::class, 'generate']);
    });

    // Document verification route
    Route::put('/dokumen/{id}/verify', [ApprovalController::class, 'verifyDocument'])->middleware('admin');

    // PGA (Pencantuman Gelar Akademik) Routes
    Route::prefix('pga')->group(function () {
        Route::get('/', [PgaController::class, 'index']);
        Route::post('/', [PgaController::class, 'store']);
        Route::match(['get', 'post'], '/{id}', [PgaController::class, 'show'])->name('pga.show');
        Route::put('/{id}', [PgaController::class, 'update']);
        Route::delete('/{id}', [PgaController::class, 'destroy']);
        Route::post('/{id}/submit', [PgaController::class, 'submit']);
        Route::post('/{id}/restore', [PgaController::class, 'restore']);
        Route::get('/{id}/document/{type}', [PgaController::class, 'downloadDocument'])->name('pga.downloadDocument');
        Route::get('/{id}/view/{type}', [PgaController::class, 'viewDocument'])
            ->name('pga.viewDocument')
            ->withoutMiddleware('auth:sanctum');
        Route::get('/{id}/view-url/{type}', [PgaController::class, 'getDocumentViewUrl']);

        // Admin/Kepala BKPSDM only
        Route::post('/{id}/approve', [PgaController::class, 'approve']);
        Route::post('/{id}/reject', [PgaController::class, 'reject']);
    });

    // Admin PGA Management (Admin only)
    Route::prefix('admin/pga')->middleware('admin')->group(function () {
        Route::delete('/{id}', [PgaController::class, 'destroy']);
        Route::post('/delete-multiple', [PgaController::class, 'deleteMultiple']);
    });

    // Signing routes (by pengajuan_id)
    Route::prefix('pengajuan')->group(function () {
        Route::post('/{id}/sign-tte', [SuratTugasController::class, 'signByPengajuanId']);
    });

    Route::prefix('surat')->group(function () {
        Route::get('/{id}', [SuratTugasController::class, 'show']);
        Route::get('/{id}/download', [SuratTugasController::class, 'download']);
        Route::post('/{id}/sign-tte', [SuratTugasController::class, 'signTte']);
    });

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/all-messages', [NotificationController::class, 'allMessages']);
        Route::get('/unread', [NotificationController::class, 'unread']);
        Route::get('/unread-count', [NotificationController::class, 'getUnreadCount']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::patch('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    // PDDikti Sync (Admin only)
    Route::prefix('admin/pddikti-sync')->middleware('admin')->group(function () {
        Route::post('/universitas', [PDDiktiSyncController::class, 'syncUniversitas']);
        Route::post('/prodi', [PDDiktiSyncController::class, 'syncProdi']);
        Route::get('/stats', [PDDiktiSyncController::class, 'stats']);
        Route::get('/', [PDDiktiSyncController::class, 'index']);
        Route::get('/{id}', [PDDiktiSyncController::class, 'show']);
        Route::get('/{id}/prodis', [PDDiktiSyncController::class, 'prodis']);
        Route::delete('/{id}', [PDDiktiSyncController::class, 'destroy']);
    });

    // Jenis Dokumen Management (Admin only)
    Route::prefix('admin/jenis-dokumen')->middleware('admin')->group(function () {
        Route::get('/', [JenisDokumenController::class, 'index']);
        Route::post('/', [JenisDokumenController::class, 'store']);
        Route::get('/{id}', [JenisDokumenController::class, 'show']);
        Route::put('/{id}', [JenisDokumenController::class, 'update']);
        Route::delete('/{id}', [JenisDokumenController::class, 'destroy']);
    });

    // Jenis Dokumen PGA Management (Admin only)
    Route::prefix('admin/jenis-dokumen-pga')->middleware('admin')->group(function () {
        Route::get('/', [JenisDokumenPgaController::class, 'index']);
        Route::post('/', [JenisDokumenPgaController::class, 'store']);
        Route::get('/{id}', [JenisDokumenPgaController::class, 'show']);
        Route::put('/{id}', [JenisDokumenPgaController::class, 'update']);
        Route::delete('/{id}', [JenisDokumenPgaController::class, 'destroy']);
        Route::post('/update-order', [JenisDokumenPgaController::class, 'updateOrder']);
    });

    // Public routes for active jenis dokumen PGA
    Route::get('/jenis-dokumen-pga/active', [JenisDokumenPgaController::class, 'active']);
    Route::get('/jenis-dokumen-pga/required', [JenisDokumenPgaController::class, 'required']);

    // Cache Management (Admin only)
    Route::prefix('admin/cache')->middleware('admin')->group(function () {
        Route::post('/clear', [MasterController::class, 'clearCache']);
    });

    // Surat Tugas Dinas (Kepala Unit)
    Route::prefix('kepala/surat-tugas')->group(function () {
        Route::get('/', [SuratTugasDinasController::class, 'index']);
        Route::get('/pending', [SuratTugasDinasController::class, 'pending']);
        Route::post('/', [SuratTugasDinasController::class, 'store']);
        Route::get('/{id}', [SuratTugasDinasController::class, 'show']);
        Route::put('/{id}', [SuratTugasDinasController::class, 'update']);
        Route::delete('/{id}', [SuratTugasDinasController::class, 'destroy']);
        // PDF route moved to public routes (below) for token-based access
    });

    // Surat Tugas Dinas by Pengajuan (Admin/BKPSDM)
    Route::get('/surat-tugas/{pengajuanId}', [SuratTugasDinasController::class, 'getByPengajuan']);

    // Verify Surat Tugas Dinas (Public)
    Route::get('/surat-tugas/verify/{qrCode}', [SuratTugasDinasController::class, 'verify']);

    // Surat Tugas Dinas (Admin BKPSDM) - Simplified flow: create after Surat Izin signed
    Route::prefix('admin/surat-tugas')->group(function () {
        Route::get('/', [SuratTugasDinasController::class, 'index']);
        Route::get('/pending', [SuratTugasDinasController::class, 'pending']);
        Route::post('/', [SuratTugasDinasController::class, 'store']);
        Route::get('/{id}', [SuratTugasDinasController::class, 'show']);
        Route::post('/{id}/upload-tte', [SuratTugasDinasController::class, 'uploadTte']);
        // preview moved to public routes
        // pdf moved to public routes
    });

    // Surat Izin Belajar (Admin BKPSDM & Kepala BKPSDM)
    // Controller handles permission check internally
    Route::prefix('admin/surat-izin')->group(function () {
        Route::get('/', [SuratIzinBelajarController::class, 'index']);
        Route::get('/pending', [SuratIzinBelajarController::class, 'pending']);
        Route::post('/', [SuratIzinBelajarController::class, 'store']);
        Route::get('/{id}', [SuratIzinBelajarController::class, 'show']);
        Route::get('/{id}/preview', [SuratIzinBelajarController::class, 'preview']);
        Route::get('/{id}/pdf', [SuratIzinBelajarController::class, 'generatePdf']);
        Route::post('/{id}/sign', [SuratIzinBelajarController::class, 'sign']);
    });

    // Surat Izin Belajar by Pengajuan (Pemohon)
    Route::get('/pengajuan/{id}/surat-izin', [SuratIzinBelajarController::class, 'getByPengajuan']);

    // Verify Surat (Public)
    Route::get('/surat-izin/verify/{qrCode}', [SuratIzinBelajarController::class, 'verify']);

    // Surat Tugas Mandiri (Admin BKPSDM & Kepala BKPSDM)
    // Controller handles permission check internally
    Route::prefix('admin/surat-tugas-mandiri')->group(function () {
        Route::get('/', [SuratTugasMandiriController::class, 'index']);
        Route::get('/pending', [SuratTugasMandiriController::class, 'pending']);
        Route::post('/', [SuratTugasMandiriController::class, 'store']);
        Route::get('/{id}', [SuratTugasMandiriController::class, 'show']);
        Route::get('/{id}/preview', [SuratTugasMandiriController::class, 'preview']);
        Route::get('/{id}/pdf', [SuratTugasMandiriController::class, 'generatePdf']);
        Route::post('/{id}/sign', [SuratTugasMandiriController::class, 'sign']);
        Route::put('/{id}', [SuratTugasMandiriController::class, 'update']);
        Route::delete('/{id}', [SuratTugasMandiriController::class, 'destroy']);
    });

    // Surat Tugas Mandiri by Pengajuan (Pemohon)
    Route::get('/pengajuan/{id}/surat-tugas-mandiri', [SuratTugasMandiriController::class, 'getByPengajuan']);

    // Verify Surat Tugas Mandiri (Public)
    Route::get('/surat-tugas-mandiri/verify/{qrCode}', [SuratTugasMandiriController::class, 'verify']);
});

// ============================================================================
// PUBLIC ROUTES (No auth middleware - controller checks token from query)
// ============================================================================

// PDF Editor Routes (Public - controller checks token from query parameter)
Route::prefix('admin/surat-izin/editor')->group(function () {
    Route::get('/preview', [SuratIzinBelajarController::class, 'editorPreview']);
    Route::get('/pdf', [SuratIzinBelajarController::class, 'editorPdf']);
});

// Download Routes (Public - controller checks token from query parameter)
Route::get('/admin/surat-izin/{id}/download', [SuratIzinBelajarController::class, 'download']);
Route::get('/admin/surat-izin/{id}/preview', [SuratIzinBelajarController::class, 'preview']);
Route::get('/admin/surat-tugas/{id}/pdf', [SuratTugasDinasController::class, 'generatePdf']);
Route::get('/admin/surat-tugas/{id}/preview', [SuratTugasDinasController::class, 'preview']);
Route::get('/admin/surat-tugas/{id}/download-tte', [SuratTugasDinasController::class, 'downloadTte']);
Route::get('/admin/surat-tugas-mandiri/{id}/download', [SuratTugasMandiriController::class, 'download']);
Route::get('/kepala/surat-tugas/{id}/pdf', [SuratTugasDinasController::class, 'generatePdf']);
Route::get('/kepala/surat-tugas/{id}/download-tte', [SuratTugasDinasController::class, 'downloadTte']);

// ============================================================================
// SYSTEM ROUTES (For deployment without terminal access)
// ============================================================================

// Run migrations (protected with secret key)
Route::post('/system/migrate', function (Request $request) {
    $secret = config('app.migration_secret', 'sipintar_migration_2024');

    if ($request->input('secret') !== $secret) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    try {
        Artisan::call('migrate', ['--force' => true]);
        $output = Artisan::output();

        return response()->json([
            'success' => true,
            'message' => 'Migration completed successfully',
            'output' => $output,
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Migration failed',
            'error' => $e->getMessage(),
        ], 500);
    }
});
