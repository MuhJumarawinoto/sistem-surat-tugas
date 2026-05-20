<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PengajuanController;
use App\Http\Controllers\DokumenController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\SuratTugasController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\PDDiktiController;
use App\Http\Controllers\PDDiktiSyncController;

Route::post('/login', [AuthController::class, 'login']);

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
        Route::put('/{id}', [PegawaiController::class, 'update']);
        Route::delete('/{id}', [PegawaiController::class, 'destroy']);
    });

    Route::prefix('pengajuan')->group(function () {
        Route::get('/', [PengajuanController::class, 'index']);
        Route::post('/', [PengajuanController::class, 'store']);
        Route::get('/nomor', [PengajuanController::class, 'getNomor']);
        Route::get('/{id}', [PengajuanController::class, 'show']);
        Route::put('/{id}', [PengajuanController::class, 'update']);
        Route::delete('/{id}', [PengajuanController::class, 'destroy']);
        Route::post('/{id}/submit', [PengajuanController::class, 'submit']);

        Route::prefix('/{pengajuanId}/dokumen')->group(function () {
            Route::get('/', [DokumenController::class, 'index']);
            Route::post('/', [DokumenController::class, 'store']);
        });

        Route::post('/{id}/approve-atasan', [ApprovalController::class, 'approveAtasan']);
        Route::post('/{id}/approve-admin', [ApprovalController::class, 'approveAdmin']);
        Route::post('/{id}/reject', [ApprovalController::class, 'reject']);
        Route::post('/{id}/verify-documents', [ApprovalController::class, 'verifyDocuments']);
        Route::post('/{id}/send-notification', [ApprovalController::class, 'sendNotification']);
        Route::post('/{id}/generate-surat', [SuratTugasController::class, 'generate']);
    });

    Route::prefix('surat')->group(function () {
        Route::get('/{id}', [SuratTugasController::class, 'show']);
        Route::get('/{id}/download', [SuratTugasController::class, 'download']);
        Route::post('/{id}/sign-tte', [SuratTugasController::class, 'signTte']);
    });

    Route::prefix('master')->group(function () {
        Route::get('/jenjang', [MasterController::class, 'jenjang']);
        Route::get('/unit-kerja', [MasterController::class, 'unitKerja']);
        Route::get('/status-pengajuan', [MasterController::class, 'statusPengajuan']);
        Route::get('/jenis-dokumen', [MasterController::class, 'jenisDokumen']);
        Route::get('/akreditasi', [MasterController::class, 'akreditasi']);
    });

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
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
});
