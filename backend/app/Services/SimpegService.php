<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class SimpegService
{
    protected string $baseUrl = 'https://simpeg.bkpsdmcloud.com';
    protected string $username;
    protected string $password;
    protected $client;
    protected $cookieJar;

    public function __construct()
    {
        $this->username = config('services.simpeg.username', 'admin');
        $this->password = config('services.simpeg.password', 'Admin123');

        // Initialize cookie jar for session management
        $this->cookieJar = new CookieJar();

        // Initialize Guzzle client
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false,
            'cookies' => $this->cookieJar,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            ],
        ]);
    }

    /**
     * Login to SIMPEG
     */
    public function login(): array
    {
        try {
            // Step 1: Get login page and CSRF token
            $response = $this->client->get("{$this->baseUrl}/auth/login");

            if ($response->getStatusCode() !== 200) {
                return [
                    'success' => false,
                    'message' => 'Gagal mengakses halaman login SIMPEG',
                ];
            }

            $body = (string) $response->getBody();

            // Extract CSRF token
            $csrfToken = $this->extractCsrfToken($body);

            if (!$csrfToken) {
                return [
                    'success' => false,
                    'message' => 'Gagal mendapatkan CSRF token',
                ];
            }

            // Step 2: Submit login
            $loginResponse = $this->client->post("{$this->baseUrl}/auth/login", [
                'form_params' => [
                    '_token' => $csrfToken,
                    'username' => $this->username,
                    'password' => $this->password,
                ],
            ]);

            $loginBody = (string) $loginResponse->getBody();

            // Check if login successful (not on login page anymore)
            if (!str_contains($loginBody, 'type="password"') || str_contains($loginBody, 'logout')) {
                return [
                    'success' => true,
                    'message' => 'Login berhasil',
                ];
            }

            // Check for error messages
            if (str_contains($loginBody, 'Invalid') || str_contains($loginBody, ' Salah')) {
                return [
                    'success' => false,
                    'message' => 'Username atau password salah',
                ];
            }

            return [
                'success' => false,
                'message' => 'Login gagal. Pastikan username dan password SIMPEG benar.',
            ];

        } catch (\Exception $e) {
            Log::error('SIMPEG Login Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error koneksi: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Extract CSRF token from HTML
     */
    protected function extractCsrfToken($html): ?string
    {
        // Try meta tag first
        if (preg_match('/<meta\s+name="csrf-token"\s+content="([^"]+)"/i', $html, $matches)) {
            return $matches[1];
        }

        // Try input hidden
        if (preg_match('/<input\s+type="hidden"\s+name="_token"\s+value="([^"]+)"/i', $html, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Fetch pegawai data from SIMPEG
     */
    public function fetchPegawai(): array
    {
        try {
            // Login first
            $loginResult = $this->login();

            if (!$loginResult['success']) {
                return [
                    'success' => false,
                    'message' => $loginResult['message'],
                    'suggestion' => 'Gunakan fitur Import JSON untuk sinkronisasi data pegawai',
                    'data' => [],
                ];
            }

            // Access pegawai list page
            $pegawaiUrl = "{$this->baseUrl}/list-all-pegawai/032";
            $response = $this->client->get($pegawaiUrl);

            if ($response->getStatusCode() !== 200) {
                return [
                    'success' => false,
                    'message' => 'Gagal mengakses halaman pegawai',
                    'data' => [],
                ];
            }

            $html = (string) $response->getBody();

            // Parse pegawai data from HTML table
            $pegawaiData = $this->parsePegawaiTable($html);

            if (empty($pegawaiData)) {
                return [
                    'success' => false,
                    'message' => 'Tidak ada data pegawai ditemukan',
                    'data' => [],
                ];
            }

            return [
                'success' => true,
                'message' => 'Data berhasil diambil',
                'data' => $pegawaiData,
            ];

        } catch (\Exception $e) {
            Log::error('SIMPEG Fetch Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Parse pegawai table from HTML
     */
    protected function parsePegawaiTable($html): array
    {
        $pegawaiList = [];

        // Find table rows
        if (!preg_match_all('/<tr[^>]*>.*?<\/tr>/is', $html, $rowMatches)) {
            return $pegawaiList;
        }

        foreach ($rowMatches[0] as $row) {
            // Skip header row and empty rows
            if (str_contains($row, '<th>') || !str_contains($row, '<td>')) {
                continue;
            }

            // Extract cells
            if (!preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $row, $cellMatches)) {
                continue;
            }

            $cells = $cellMatches[1];

            // Need at least 9 cells for valid pegawai data
            if (count($cells) < 9) {
                continue;
            }

            // Extract NIP from second cell
            $nip = trim(strip_tags($cells[1]));
            $nip = preg_replace('/\s+/', '', $nip);

            // Extract Nama and TTL from first cell
            $firstCell = trim($cells[0]);
            $nama = '';
            $tempatTglLahir = '';

            if (preg_match('/<a[^>]*>(.*?)<\/a>/is', $firstCell, $namaMatch)) {
                $nama = trim(strip_tags($namaMatch[1]));
            }

            if (preg_match('/<br[^>]*>(.*)/is', $firstCell, $ttlMatch)) {
                $tempatTglLahir = trim(strip_tags($ttlMatch[1]));
            }

            // Extract other fields
            $golongan = trim(strip_tags($cells[2]));
            $tmtGolongan = trim(strip_tags($cells[3]));
            $jabatan = trim(strip_tags($cells[4]));
            $tmtJabatan = trim(strip_tags($cells[5]));
            $statusPegawai = trim(strip_tags($cells[6]));
            $tmtPegawai = trim(strip_tags($cells[7]));
            $masaKerjaTahun = trim(strip_tags($cells[8]));
            $masaKerjaBulan = isset($cells[9]) ? trim(strip_tags($cells[9])) : '';

            // Skip if NIP is empty
            if (empty($nip) || empty($nama)) {
                continue;
            }

            $pegawaiList[] = [
                'nip' => $nip,
                'nama' => $nama,
                'tempat_tgl_lahir' => $tempatTglLahir,
                'golongan' => $golongan,
                'tmt_golongan' => $tmtGolongan,
                'jabatan' => $jabatan,
                'tmt_jabatan' => $tmtJabatan,
                'status_pegawai' => $statusPegawai,
                'tmt_pegawai' => $tmtPegawai,
                'masa_kerja_tahun' => $masaKerjaTahun,
                'masa_kerja_bulan' => $masaKerjaBulan,
                'unit_kerja' => 'Tidak Ada Unit Kerja', // Will be extracted if available
            ];
        }

        return $pegawaiList;
    }

    /**
     * Sync pegawai data to local database
     */
    public function syncToDatabase(): array
    {
        $result = $this->fetchPegawai();

        if (!$result['success']) {
            return $result;
        }

        $pegawaiData = $result['data'];

        return $this->importPegawaiData($pegawaiData);
    }

    /**
     * Import pegawai data array to database
     */
    protected function importPegawaiData(array $data): array
    {
        try {
            $results = [
                'success' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => 0,
            ];

            foreach ($data as $item) {
                try {
                    // Map SIMPEG fields to local fields
                    $nip = $item['nip'] ?? null;
                    $nama = $item['nama'] ?? null;

                    if (!$nip || !$nama) {
                        $results['skipped']++;
                        continue;
                    }

                    // Check existing
                    $user = \App\Models\User::where('nip', $nip)->first();

                    if ($user) {
                        // Update existing
                        $updateData = ['name' => $nama];

                        if (!empty($item['golongan'])) {
                            $updateData['pangkat_gol'] = $item['golongan'];
                        }
                        if (!empty($item['jabatan'])) {
                            $updateData['jabatan'] = $item['jabatan'];
                        }

                        $user->update($updateData);
                        $results['updated']++;
                    } else {
                        // Create new
                        $role = \App\Models\Role::where('slug', 'pemohon')->first();

                        \App\Models\User::create([
                            'name' => $nama,
                            'email' => $nip . '@simpeg.local',
                            'nip' => $nip,
                            'password' => bcrypt('password123'),
                            'role_id' => $role?->id ?? 1,
                            'pangkat_gol' => $item['golongan'] ?? null,
                            'jabatan' => $item['jabatan'] ?? null,
                            'is_active' => true,
                            'email_verified_at' => now(),
                        ]);
                        $results['success']++;
                    }

                } catch (\Exception $e) {
                    $results['failed']++;
                    Log::error('Failed to import pegawai: ' . $e->getMessage());
                }
            }

            return [
                'success' => true,
                'message' => 'Sync berhasil diselesaikan',
                'data' => $results,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'data' => [],
            ];
        }
    }
}
