<?php

require __DIR__ . '/vendor/autoload.php';

use Barryvdh\DomPDF\Facade\Pdf;

echo "=== Generate PDF dengan QR Code dan Barcode ===" . PHP_EOL;

// Check GD extension
echo "GD Extension: " . (extension_loaded('gd') ? 'YES ✓' : 'NO ✗') . PHP_EOL;

if (!extension_loaded('gd')) {
    echo "ERROR: GD Extension belum aktif!" . PHP_EOL;
    echo "Silakan aktifkan GD di php.ini Laragon terlebih dahulu." . PHP_EOL;
    exit(1);
}

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SuratIzinBelajar;

$surat = SuratIzinBelajar::with(['pengajuan.user', 'pengajuan.jenjang', 'suratTugasDinas'])->find(1);

if (!$surat) {
    echo "ERROR: Surat tidak ditemukan!" . PHP_EOL;
    exit(1);
}

echo "Surat ID: " . $surat->id . PHP_EOL;
echo "Nomor: " . $surat->nomor_surat . PHP_EOL;

// Generate QR code
$qrService = app('App\Services\QrCodeService');
$qrCodeData = json_encode([
    'type' => 'surat_izin_belajar',
    'id' => $surat->id,
    'nomor' => $surat->nomor_surat,
    'signed_at' => $surat->signed_at ?? now()->toIso8601String(),
]);
$qrCodePath = $qrService->generateAndSave($qrCodeData, 'izin-' . $surat->id);
echo "QR Code Path: " . $qrCodePath . PHP_EOL;

// Generate barcode
$barcodeService = app('App\Services\BarcodeService');
$barcodePath = $barcodeService->generateForSurat($surat->nomor_surat, 'izin', $surat->id);
echo "Barcode Path: " . $barcodePath . PHP_EOL;

// Convert images to base64 for embedding in PDF
$qrCodeBase64 = 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get($qrCodePath));
$barcodeBase64 = 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get($barcodePath));

// Generate PDF
$pdf = Pdf::loadView('pdf.surat-izin-belajar', [
    'surat' => $surat,
    'qrCodeBase64' => $qrCodeBase64,
    'barcodeBase64' => $barcodeBase64,
]);

$filename = 'Surat_Dengan_QR.pdf';
$filePath = storage_path('app/public/' . $filename);
file_put_contents($filePath, $pdf->output());

echo PHP_EOL . "PDF berhasil dibuat!" . PHP_EOL;
echo "File: " . $filename . PHP_EOL;
echo "Size: " . filesize($filePath) . " bytes" . PHP_EOL;

// Update surat
$surat->update(['file_path' => $filename]);
echo "Surat file_path updated!" . PHP_EOL;

echo PHP_EOL . "Download URL: " . url('storage/' . $filename) . PHP_EOL;
