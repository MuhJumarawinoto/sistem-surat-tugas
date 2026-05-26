<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PDFEditorController;

Route::get('/', function () {
    return view('welcome');
});

// PDF Editor - Standalone page with session auth
Route::get('/pdf-editor', [PDFEditorController::class, 'index'])->name('pdf-editor');
Route::get('/pdf-editor/preview', [PDFEditorController::class, 'preview'])->name('pdf-editor.preview');
Route::get('/pdf-editor/pdf', [PDFEditorController::class, 'generatePdf'])->name('pdf-editor.pdf');
