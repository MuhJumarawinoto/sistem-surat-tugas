<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PengajuanController;
use App\Http\Controllers\DokumenController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\SuratTugasController;
use App\Http\Controllers\MasterController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

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
});
