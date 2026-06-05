<?php

namespace App\Console\Commands;

use App\Models\UnitKerja;
use App\Models\User;
use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportPegawai extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pegawai:import {file : Path to JSON file} {--mode=sync : Import mode (create, update, sync)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import pegawai from JSON file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('file');
        $mode = $this->option('mode');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $content = file_get_contents($file);

        // Remove BOM if present
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3);
        }

        $data = json_decode($content, true);

        if (!$data || !is_array($data)) {
            $this->error("Invalid JSON format");
            return 1;
        }

        $this->info("Found " . count($data) . " records to import");
        $this->info("Mode: {$mode}");

        // Detect format
        $format = 'standard';
        if (!empty($data[0]) && isset($data[0]['golongan']) && !isset($data[0]['pangkat_gol'])) {
            $format = 'simpeg';
        }
        $this->info("Format detected: {$format}");

        DB::beginTransaction();

        $results = [
            'success' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        $bar = $this->output->createProgressBar(count($data));
        $bar->start();

        foreach ($data as $index => $item) {
            $bar->advance();

            try {
                if ($format === 'simpeg') {
                    $nip = $item['nip'] ?? null;
                    $nama = $item['nama'] ?? null;
                    $golongan = $item['golongan'] ?? null;
                    $jabatan = $item['jabatan'] ?? null;
                    $unitKerjaNama = $item['unit_kerja'] ?? null;

                    if (!$nip || !$nama) {
                        $results['skipped']++;
                        continue;
                    }

                    // Find or create unit kerja
                    $unitKerja = null;
                    if (!empty($unitKerjaNama) && $unitKerjaNama !== 'Tidak Ada Unit Kerja') {
                        $unitKerja = UnitKerja::where('nama', 'like', '%' . $unitKerjaNama . '%')->first();
                        if (!$unitKerja) {
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

                    $email = $nip . '@simpeg.local';
                    $jabatanKategori = $this->mapGolonganToKategori($golongan);
                    $statusPegawai = $item['status_pegawai'] ?? '-';
                    $isActive = ($statusPegawai === 'PNS' || $statusPegawai === 'CPNS');

                } else {
                    $nip = $item['nip'] ?? null;
                    $nama = $item['nama'] ?? null;
                    $email = $item['email'] ?? null;
                    $golongan = $item['pangkat_gol'] ?? null;
                    $jabatan = $item['jabatan'] ?? null;
                    $unitKerjaNama = $item['unit_kerja_nama'] ?? null;
                    $jabatanKategori = $item['jabatan_kategori'] ?? null;
                    $isActive = $item['is_active'] ?? true;

                    if (!$nip || !$nama) {
                        $results['skipped']++;
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

                // Find atasan
                $atasan = null;
                if ($format === 'standard' && !empty($item['atasan_nip'])) {
                    $atasan = User::where('nip', $item['atasan_nip'])->first();
                }

                // Find existing user
                $user = User::where('nip', $nip)->first();

                if ($user) {
                    if ($mode === 'create') {
                        $results['skipped']++;
                        continue;
                    }

                    $user->update([
                        'name' => $nama,
                        'email' => $user->email,
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
                    if ($mode === 'update') {
                        $results['skipped']++;
                        continue;
                    }

                    $role = Role::where('slug', 'pemohon')->first();

                    User::create([
                        'name' => $nama,
                        'email' => $email,
                        'nip' => $nip,
                        'password' => bcrypt('password123'),
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
                $this->newLine();
                $this->warn("Row " . ($index + 1) . " failed: " . $e->getMessage());
            }
        }

        $bar->finish();
        $this->newLine(2);

        DB::commit();

        $this->info("Import completed!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Success (New)', $results['success']],
                ['Updated', $results['updated']],
                ['Skipped', $results['skipped']],
                ['Failed', $results['failed']],
            ]
        );

        return 0;
    }

    private function generateSingkatan($nama): string
    {
        $words = explode(' ', $nama);
        $singkatan = '';
        foreach ($words as $word) {
            if (strlen($word) > 0) {
                $singkatan .= strtoupper(substr($word, 0, 1));
            }
        }
        return substr($singkatan, 0, 6);
    }

    private function mapGolonganToKategori($golongan): string
    {
        if (!$golongan || $golongan === '-' || $golongan === '') {
            return 'staf';
        }

        $golongan = strtoupper($golongan);

        if (strpos($golongan, 'IV/E') !== false) {
            return 'kepala';
        }
        if (strpos($golongan, 'IV/D') !== false) {
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
}
