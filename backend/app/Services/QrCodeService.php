<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class QrCodeService
{
    /**
     * Generate QR code and save to storage.
     *
     * @param string $data Data to encode in QR code
     * @param string $filename Optional filename (without extension)
     * @return string Path to saved QR code image
     */
    public function generateAndSave(string $data, string $filename = null): string
    {
        $filename = $filename ?? 'qr-' . md5($data) . '-' . time();
        $filePath = "qr-codes/{$filename}.png";

        // Check if file already exists
        if (Storage::disk('public')->exists($filePath)) {
            return $filePath;
        }

        // Generate QR code using external API
        $size = 150;
        $apiUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($data);

        try {
            $response = Http::timeout(10)->withoutVerifying()->get($apiUrl);

            if ($response->successful()) {
                // Save image to storage
                Storage::disk('public')->put($filePath, $response->body());
                return $filePath;
            }

            // Fallback: return API URL if storage fails
            return $apiUrl;
        } catch (\Exception $e) {
            // Fallback: return API URL
            return $apiUrl;
        }
    }

    /**
     * Generate verification QR code data for surat.
     *
     * @param string $suratType Type of surat (izin, tugas)
     * @param int $suratId ID of the surat
     * @param string $nomorSurat Nomor surat
     * @return string QR code data/URL
     */
    public function generateForSurat(string $suratType, int $suratId, string $nomorSurat): string
    {
        $data = [
            'type' => $suratType,
            'id' => $suratId,
            'nomor' => $nomorSurat,
            'timestamp' => now()->toIso8601String(),
        ];

        $qrData = json_encode($data);
        $filename = "{$suratType}-{$suratId}";

        return $this->generateAndSave($qrData, $filename);
    }

    /**
     * Get verification URL for QR code.
     *
     * @param string $qrCodeData
     * @return string
     */
    public function getVerificationUrl(string $qrCodeData): string
    {
        // For now, use local verification endpoint
        return config('app.url') . '/api/surat-izin/verify/' . $qrCodeData;
    }

    /**
     * Generate QR code as base64 image (for inline embedding).
     *
     * @param string $data Data to encode
     * @param int $size Size in pixels
     * @return string|null Base64 encoded image or null on failure
     */
    public function generateAsBase64(string $data, int $size = 150): ?string
    {
        $apiUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($data);

        try {
            $response = Http::timeout(10)->withoutVerifying()->get($apiUrl);

            if ($response->successful()) {
                return 'data:image/png;base64,' . base64_encode($response->body());
            }
        } catch (\Exception $e) {
            // Silent fail
        }

        return null;
    }
}
