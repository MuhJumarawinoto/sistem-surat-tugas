<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\UnitKerja;
use App\Models\User;
use App\Services\SimpegService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PegawaiSyncController extends Controller
{
    /**
     * Import pegawai from JSON file.
     * Supports two formats:
     * 1. Standard format (nip, nama, email, pangkat_gol, jabatan, unit_kerja_kode, etc.)
     * 2. SIMPEG format (nip, nama, golongan, jabatan, unit_kerja, status_pegawai, etc.)
     */
    public function importFromJson(Request $request): JsonResponse
    {
        // Only admin can import
        if (!$request->user()?->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized. Admin only.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:json,txt',
            'mode' => 'sometimes|in:create,update,sync',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $mode = $request->input('mode', 'sync'); // create, update, or sync

        try {
            $file = $request->file('file');
            $content = file_get_contents($file->getPathname());

            // Remove BOM if present
            if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
                $content = substr($content, 3);
            }

            $data = json_decode($content, true);

            if (!$data || !is_array($data)) {
                return response()->json(['message' => 'Invalid JSON format'], 400);
            }

            DB::beginTransaction();

            $results = [
                'success' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => 0,
                'errors' => [],
            ];

            // Detect format from first item
            $format = 'standard';
            if (!empty($data[0]) && isset($data[0]['golongan']) && !isset($data[0]['pangkat_gol'])) {
                $format = 'simpeg';
            }

            foreach ($data as $index => $item) {
                try {
                    if ($format === 'simpeg') {
                        // SIMPEG format mapping
                        $nip = $item['nip'] ?? null;
                        $nama = $item['nama'] ?? null;
                        $golongan = $item['golongan'] ?? null;
                        $jabatan = $item['jabatan'] ?? null;
                        $unitKerjaNama = $item['unit_kerja'] ?? null;

                        // Skip if required fields missing
                        if (!$nip || !$nama) {
                            $results['skipped']++;
                            $results['errors'][] = "Row " . ($index + 1) . ": Missing required fields (nip, nama)";
                            continue;
                        }

                        // Find or create unit kerja by name
                        $unitKerja = null;
                        if (!empty($unitKerjaNama) && $unitKerjaNama !== 'Tidak Ada Unit Kerja') {
                            $unitKerja = UnitKerja::where('nama', 'like', '%' . $unitKerjaNama . '%')->first();
                            if (!$unitKerja) {
                                // Create new unit kerja
                                $singkatan = $this->generateSingkatan($unitKerjaNama);
                                $kode = 'UK_' . strtoupper(substr(md5($unitKerjaNama), 0, 4));
                                $unitKerja = UnitKerja::create([
                                    'kode' => $kode,
                                    'nama' => $unitKerjaNama,
                                    'singkatan' => $singkatan,
                                    'is_active' => true,
                                ]);
                            }
                        }

                        // Generate email from NIP if not exists
                        $email = $nip . '@simpeg.local';

                        // Determine jabatan kategori from golongan
                        $jabatanKategori = $this->mapGolonganToKategori($golongan);

                    } else {
                        // Standard format
                        $nip = $item['nip'] ?? null;
                        $nama = $item['nama'] ?? null;
                        $email = $item['email'] ?? null;
                        $golongan = $item['pangkat_gol'] ?? null;
                        $jabatan = $item['jabatan'] ?? null;
                        $unitKerjaNama = $item['unit_kerja_nama'] ?? null;
                        $jabatanKategori = $item['jabatan_kategori'] ?? null;

                        // Skip if required fields missing
                        if (!$nip || !$nama) {
                            $results['skipped']++;
                            $results['errors'][] = "Row " . ($index + 1) . ": Missing required fields (nip, nama)";
                            continue;
                        }

                        // Find or create unit kerja
                        $unitKerja = null;
                        if (!empty($item['unit_kerja_kode'])) {
                            $unitKerja = UnitKerja::where('kode', $item['unit_kerja_kode'])->first();
                            if (!$unitKerja && !empty($unitKerjaNama)) {
                                $singkatan = $item['unit_kerja_singkatan'] ?? $this->generateSingkatan($unitKerjaNama);
                                $unitKerja = UnitKerja::create([
                                    'kode' => $item['unit_kerja_kode'],
                                    'nama' => $unitKerjaNama,
                                    'singkatan' => $singkatan,
                                    'is_active' => true,
                                ]);
                            }
                        } elseif (!empty($unitKerjaNama)) {
                            $unitKerja = UnitKerja::where('nama', 'like', '%' . $unitKerjaNama . '%')->first();
                            if (!$unitKerja && $unitKerjaNama !== 'Tidak Ada Unit Kerja') {
                                $singkatan = $this->generateSingkatan($unitKerjaNama);
                                $kode = 'UK_' . strtoupper(substr(md5($unitKerjaNama), 0, 4));
                                $unitKerja = UnitKerja::create([
                                    'kode' => $kode,
                                    'nama' => $unitKerjaNama,
                                    'singkatan' => $singkatan,
                                    'is_active' => true,
                                ]);
                            }
                        }
                    }

                    // Find atasan by NIP (standard format only)
                    $atasan = null;
                    if ($format === 'standard' && !empty($item['atasan_nip'])) {
                        $atasan = User::where('nip', $item['atasan_nip'])->first();
                    }

                    // Determine is_active from status_pegawai
                    $isActive = true;
                    if ($format === 'simpeg') {
                        $statusPegawai = $item['status_pegawai'] ?? '-';
                        $isActive = ($statusPegawai === 'PNS' || $statusPegawai === 'CPNS');
                    } else {
                        $isActive = $item['is_active'] ?? true;
                    }

                    // Find existing user by NIP
                    $user = User::where('nip', $nip)->first();

                    if ($user) {
                        // Update existing
                        if ($mode === 'create') {
                            $results['skipped']++;
                            continue;
                        }

                        $user->update([
                            'name' => $nama,
                            'email' => $user->email, // Keep existing email
                            'nip' => $nip,
                            'pangkat_gol' => $golongan,
                            'jabatan' => $jabatan,
                            'unit_kerja_id' => $unitKerja?->id ?? $user->unit_kerja_id,
                            'atasan_id' => $atasan?->id ?? $user->atasan_id,
                            'jabatan_kategori' => $jabatanKategori ?? $user->jabatan_kategori,
                            'is_active' => $isActive,
                        ]);
                        $results['updated']++;
                    } else {
                        // Create new
                        if ($mode === 'update') {
                            $results['skipped']++;
                            continue;
                        }

                        // Get default role (pemohon)
                        $role = Role::where('slug', 'pemohon')->first();

                        $user = User::create([
                            'name' => $nama,
                            'email' => $email,
                            'nip' => $nip,
                            'password' => bcrypt('password123'), // Default password
                            'role_id' => $role?->id ?? 1,
                            'pangkat_gol' => $golongan,
                            'jabatan' => $jabatan,
                            'unit_kerja_id' => $unitKerja?->id ?? null,
                            'atasan_id' => $atasan?->id ?? null,
                            'jabatan_kategori' => $jabatanKategori,
                            'is_active' => $isActive,
                            'email_verified_at' => now(),
                        ]);
                        $results['success']++;
                    }
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Row " . ($index + 1) . " (" . ($item['nip'] ?? 'unknown') . "): " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Import completed',
                'format_detected' => $format,
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate singkatan from nama unit kerja
     */
    private function generateSingkatan($nama): string
    {
        // Take first letters of each word
        $words = explode(' ', $nama);
        $singkatan = '';
        foreach ($words as $word) {
            if (strlen($word) > 0) {
                $singkatan .= strtoupper(substr($word, 0, 1));
            }
        }

        // Limit to 6 characters
        return substr($singkatan, 0, 6);
    }

    /**
     * Map golongan to jabatan kategori
     */
    private function mapGolonganToKategori($golongan): string
    {
        // Map golongan to jabatan kategori
        // IV/e, IV/d -> kepala
        // IV/c, IV/b, IV/a -> kabid
        // III/d, III/c -> kasi
        // Others -> staf

        if (!$golongan || $golongan === '-' || $golongan === '') {
            return 'staf';
        }

        $golongan = strtoupper($golongan);

        if (strpos($golongan, 'IV/E') !== false || strpos($golongan, 'IV/E') !== false) {
            return 'kepala';
        }
        if (strpos($golongan, 'IV/D') !== false || strpos($golongan, 'IV/D') !== false) {
            return 'kepala';
        }
        if (strpos($golongan, 'IV/C') !== false || strpos($golongan, 'IV/B') !== false || strpos($golongan, 'IV/A') !== false) {
            return 'kabid';
        }
        if (strpos($golongan, 'III/D') !== false || strpos($golongan, 'III/C') !== false) {
            return 'kasi';
        }

        return 'staf';
    }

    /**
     * Sync pegawai from external API (SIMPEG).
     * This method fetches data from SIMPEG website and updates local database.
     */
    public function syncFromSimpeg(Request $request): JsonResponse
    {
        // Only admin can sync
        if (!$request->user()?->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized. Admin only.'], 403);
        }

        try {
            $simpegService = new SimpegService();
            $result = $simpegService->syncToDatabase();

            if ($result['success']) {
                // Update last sync timestamp (optional - can be stored in settings)
                return response()->json([
                    'message' => 'Sync berhasil',
                    'data' => $result['data'],
                ]);
            }

            // Return error with suggestion if available
            $response = [
                'message' => $result['message'],
                'data' => $result['data'] ?? [],
            ];

            if (isset($result['suggestion'])) {
                $response['suggestion'] = $result['suggestion'];
            }

            return response()->json($response, 400);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Sync failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test connection to SIMPEG
     */
    public function testSimpegConnection(Request $request): JsonResponse
    {
        // Only admin can test
        if (!$request->user()?->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized. Admin only.'], 403);
        }

        try {
            $simpegService = new SimpegService();
            $session = $simpegService->login();

            if ($session) {
                return response()->json([
                    'message' => 'Connection successful',
                    'connected' => true,
                ]);
            }

            return response()->json([
                'message' => 'Connection failed. Check SIMPEG credentials.',
                'connected' => false,
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Connection error: ' . $e->getMessage(),
                'connected' => false,
            ], 500);
        }
    }

    /**
     * Get sync statistics.
     */
    public function getStats(Request $request): JsonResponse
    {
        // Only admin can view stats
        if (!$request->user()?->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized. Admin only.'], 403);
        }

        $stats = [
            'total_pegawai' => User::whereNotNull('nip')->count(),
            'total_pegawai_active' => User::whereNotNull('nip')->where('is_active', true)->count(),
            'total_unit_kerja' => UnitKerja::where('is_active', true)->count(),
            'last_sync_at' => null, // Can be stored in settings table
        ];

        return response()->json($stats);
    }

    /**
     * Download template JSON for pegawai import.
     */
    public function downloadTemplate(Request $request): JsonResponse
    {
        // Only admin can download
        if (!$request->user()?->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized. Admin only.'], 403);
        }

        $template = [
            [
                "nip" => "198001012010011001",
                "nama" => "Contoh Pegawai",
                "email" => "pegawai@example.com",
                "pangkat_gol" => "Penata Tk.I - III/d",
                "jabatan" => "Jabatan Pegawai",
                "unit_kerja_kode" => "01",
                "unit_kerja_nama" => "Nama Unit Kerja (Baru)",
                "unit_kerja_singkatan" => "Singkatan",
                "jabatan_kategori" => "staf",
                "atasan_nip" => "197506152005011002",
                "is_active" => true,
            ]
        ];

        return response()->json([
            'message' => 'Template downloaded',
            'template' => $template,
            'instructions' => [
                '1. Download this template',
                '2. Fill with your pegawai data',
                '3. Upload using importFromJson endpoint',
                '4. Modes: create (new only), update (existing only), sync (create + update)',
            ],
        ]);
    }
}
