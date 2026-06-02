<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class BarcodeService
{
    /**
     * Generate barcode and save to storage.
     *
     * @param string $data Data to encode in barcode
     * @param string $filename Optional filename (without extension)
     * @return string Path to saved barcode image
     */
    public function generateAndSave(string $data, string $filename = null): string
    {
        $filename = $filename ?? 'barcode-' . md5($data) . '-' . time();
        $filePath = "barcodes/{$filename}.png";

        // Check if file already exists
        if (Storage::disk('public')->exists($filePath)) {
            return $filePath;
        }

        // Generate barcode using external API (Barcode Generator)
        // Using barcodeapi.org for Code128 barcode generation
        $apiUrl = "https://barcodeapi.org/api/code128/" . urlencode($data);

        try {
            $response = Http::timeout(10)->withoutVerifying()->get($apiUrl);

            if ($response->successful() && strlen($response->body()) > 100) {
                // Save image to storage
                Storage::disk('public')->put($filePath, $response->body());
                return $filePath;
            }

            // Fallback: return API URL if storage fails
            return $apiUrl;
        } catch (\Exception $e) {
            // Fallback: generate using local GD library
            return $this->generateWithGd($data, $filename);
        }
    }

    /**
     * Generate barcode for surat with nomor_surat.
     *
     * @param string $nomorSurat Nomor surat
     * @param string $suratType Type of surat (for filename)
     * @param int $suratId ID of the surat (for filename)
     * @return string Path to barcode image
     */
    public function generateForSurat(string $nomorSurat, string $suratType, int $suratId): string
    {
        // Use nomor_surat as barcode data
        $filename = "{$suratType}-{$suratId}-barcode";

        return $this->generateAndSave($nomorSurat, $filename);
    }

    /**
     * Generate barcode as base64 image (for inline embedding).
     *
     * @param string $data Data to encode
     * @return string|null Base64 encoded image or null on failure
     */
    public function generateAsBase64(string $data): ?string
    {
        $apiUrl = "https://barcodeapi.org/api/code128/" . urlencode($data);

        try {
            $response = Http::timeout(10)->get($apiUrl);

            if ($response->successful()) {
                return 'data:image/png;base64,' . base64_encode($response->body());
            }
        } catch (\Exception $e) {
            // Silent fail
        }

        return null;
    }

    /**
     * Generate barcode using GD library (fallback).
     *
     * @param string $data Data to encode
     * @param string $filename Filename
     * @return string Path to saved barcode
     */
    protected function generateWithGd(string $data, string $filename): string
    {
        $filePath = "barcodes/{$filename}.png";

        // Check if GD is available
        if (!function_exists('imagecreatetruecolor')) {
            // GD not available, return API URL directly
            return "https://barcodeapi.org/api/code128/" . urlencode($data);
        }

        try {
            // Create image
            $width = 400;
            $height = 80;
            $image = imagecreatetruecolor($width, $height);

            // Colors
            $white = imagecolorallocate($image, 255, 255, 255);
            $black = imagecolorallocate($image, 0, 0, 0);

            // Fill background
            imagefill($image, 0, 0, $white);

            // Draw simple text representation (fallback)
            $fontSize = 4;
            $textWidth = imagefontwidth($fontSize) * strlen($data);
            $x = ($width - $textWidth) / 2;
            $y = ($height - imagefontheight($fontSize)) / 2;

            imagestring($image, $fontSize, $x, $y, $data, $black);

            // Add border
            imagerectangle($image, 0, 0, $width - 1, $height - 1, $black);

            // Capture output
            ob_start();
            imagepng($image);
            $imageData = ob_get_clean();
            imagedestroy($image);

            // Save to storage
            Storage::disk('public')->put($filePath, $imageData);

            return $filePath;
        } catch (\Exception $e) {
            // Return API URL as final fallback
            return "https://barcodeapi.org/api/code128/" . urlencode($data);
        }
    }

    /**
     * Get barcode as HTML img tag.
     *
     * @param string $data Data to encode
     * @param array $attributes Optional HTML attributes
     * @return string HTML img tag
     */
    public function getAsImgTag(string $data, array $attributes = []): string
    {
        $base64 = $this->generateAsBase64($data);

        if ($base64) {
            $attrs = array_merge([
                'src' => $base64,
                'alt' => $data,
                'style' => 'height: 60px;'
            ], $attributes);

            $attrString = '';
            foreach ($attrs as $key => $value) {
                if ($key === 'style' && isset($attributes['style'])) {
                    $attrString .= ' style="' . htmlspecialchars($value) . '"';
                } elseif ($key !== 'style') {
                    $attrString .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
                }
            }

            return '<img' . $attrString . ' />';
        }

        return '';
    }

    /**
     * Generate text-based barcode representation (for PDF without GD).
     * Uses simple ASCII characters for maximum compatibility.
     *
     * @param string $data Data to encode
     * @return string HTML with barcode pattern
     */
    public static function generateTextBarcode(string $data): string
    {
        // Create a simple barcode pattern using ASCII characters
        $encodedData = strtoupper($data);

        // Use | for bars and spaces for gaps (3D barcode effect)
        $html = '';

        // Create 3 rows for 3D effect
        for ($row = 0; $row < 3; $row++) {
            $html .= '<div style="font-family: monospace; font-size: 6px; line-height: 1; white-space: nowrap; text-align: center;">';

            foreach (str_split($encodedData) as $char) {
                if ($char === ' ') {
                    $ord = 32;
                } elseif ($char === '/') {
                    $ord = 47;
                } elseif ($char === '.') {
                    $ord = 46;
                } elseif ($char === '-') {
                    $ord = 45;
                } elseif ($char === ':') {
                    $ord = 58;
                } elseif (is_numeric($char)) {
                    $ord = ord($char);
                } else {
                    $ord = ord($char);
                }

                // Generate pattern
                $pattern = $ord % 31;
                $binary = str_pad(decbin($pattern), 5, '0', STR_PAD_LEFT);

                // Each character becomes 5 bars
                for ($i = 0; $i < 5; $i++) {
                    if ($binary[$i] === '1') {
                        $html .= '&#124;'; // HTML entity for |
                    } else {
                        $html .= '&nbsp;'; // Non-breaking space
                    }
                }
                $html .= '&nbsp;'; // Gap between characters
            }

            $html .= '</div>';
        }

        return $html;
    }
}
