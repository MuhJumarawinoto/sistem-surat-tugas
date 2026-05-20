<?php

namespace App\Console\Commands;

use App\Models\UnitKerja;
use App\Models\User;
use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ImportPegawaiSimpeg extends Command
{
    protected $signature = 'simpeg:import {file? : Path to JSON file}';

    protected $description = 'Import data pegawai dari SIMPEG JSON file';

    // Mapping unit kerja dari SIMPEG ke kode unit kerja
    protected $unitKerjaMap = [
        'Sekretariat Badan Kepegawaian dan Pengembangan SDM' => 'SEKRETARIAT',
        'Sub Bagian Umum dan Kepegawaian' => 'SUB_BAG_UMUM',
        'Sub Bagian Keuangan' => 'SUB_BAG_KEUANGAN',
        'Bidang Pengadaan, Pemberhentian dan Informasi ASN' => 'BIDANG_PPP',
        'Bidang Kinerja, Disiplin dan Penghargaan ASN' => 'BIDANG_KDH',
        'Bidang Pengembangan Sumber Daya Manusia' => 'BIDANG_PSDM',
        'Bidang Mutasi, Kepangkatan dan Promosi ASN' => 'BIDANG_MKP',
        'Sub Koordinator Kinerja' => 'SUBKOR_KINERJA',
        'Sub Koordinator Pengembangan Kompetensi Teknis dan Fungsional' => 'SUBKOR_KOMPETENSI',
        'Sub Koordinator Penghargaan' => 'SUBKOR_PENGHARGAAN',
        'Sub Koordinator Mutasi' => 'SUBKOR_MUTASI',
        'Sub Koordinator Perencanaan dan Evaluasi' => 'SUBKOR_PERENCANAAN',
    ];

    public function handle()
    {
        $file = $this->argument('file') ?? base_path('../data_pegawai_simpeg.json');

        if (!file_exists($file)) {
            $this->error("File tidak ditemukan: {$file}");
            return 1;
        }

        // Remove BOM if present
        $jsonContent = file_get_contents($file);
        $jsonContent = preg_replace('/^\xEF\xBB\xBF/', '', $jsonContent);
        $jsonData = json_decode($jsonContent, true);

        if (!is_array($jsonData)) {
            $this->error("Format file JSON tidak valid");
            return 1;
        }

        $this->info("Mengimport " . count($jsonData) . " data pegawai...");
        $this->newLine();

        $bar = $this->output->createProgressBar(count($jsonData));
        $bar->start();

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($jsonData as $pegawai) {
            try {
                $nip = $pegawai['nip'] ?? null;
                $nama = $pegawai['nama'] ?? null;

                if (!$nip || !$nama) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                // Skip jika status pegawai bukan PNS
                if (($pegawai['status_pegawai'] ?? 'PNS') !== 'PNS') {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                // Mapping unit kerja
                $unitKerjaNama = $pegawai['unit_kerja'] ?? null;
                $unitKerjaKode = $this->unitKerjaMap[$unitKerjaNama] ?? null;

                if (!$unitKerjaKode) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                $unitKerja = UnitKerja::where('kode', $unitKerjaKode)->first();

                if (!$unitKerja) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                // Parse tanggal lahir
                $tanggalLahir = $this->parseTanggalLahir($pegawai['tempat_tgl_lahir'] ?? '');

                // Tentukan role berdasarkan jabatan
                $role = $this->determineRole($pegawai['jabatan'] ?? '');

                // Cek user berdasarkan NIP
                $user = User::where('nip', $nip)->first();

                $userData = [
                    'name' => $nama,
                    'email' => strtolower(str_replace([' ', ','], '.', $nama)) . '@bkpsdm.go.id',
                    'nip' => $nip,
                    'role_id' => $role->id,
                    'unit_kerja_id' => $unitKerja->id,
                    'pangkat_gol' => $pegawai['golongan'] ?? null,
                    'jabatan' => $pegawai['jabatan'] ?? null,
                    'tanggal_lahir' => $tanggalLahir,
                    'is_active' => true,
                ];

                if ($user) {
                    // Update existing user
                    $user->update($userData);
                    $updated++;
                } else {
                    // Create new user dengan password default
                    $userData['password'] = Hash::make('password123');
                    User::create($userData);
                    $created++;
                }

            } catch (\Exception $e) {
                $this->newLine();
                $this->warn("Error processing NIP {$nip}: " . $e->getMessage());
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->newLine();

        $this->info("Import selesai!");
        $this->table(
            ['Status', 'Jumlah'],
            [
                ['Dibuat', $created],
                ['Diupdate', $updated],
                ['Dilewati', $skipped],
                ['Error', $errors],
            ]
        );

        return 0;
    }

    private function parseTanggalLahir($tempatTglLahir)
    {
        if (empty($tempatTglLahir)) {
            return null;
        }

        // Format: "SUKABUMI,23 May 1988" atau "Lebak,12 February 1976"
        if (preg_match('/,(\d+)\s+(\w+)\s+(\d{4})$/', $tempatTglLahir, $matches)) {
            $day = $matches[1];
            $month = $matches[2];
            $year = $matches[3];

            $months = [
                'January' => '01', 'February' => '02', 'March' => '03', 'April' => '04',
                'May' => '05', 'June' => '06', 'July' => '07', 'August' => '08',
                'September' => '09', 'October' => '10', 'November' => '11', 'December' => '12',
            ];

            $monthNum = $months[$month] ?? '01';
            return "{$year}-{$monthNum}-{$day}";
        }

        return null;
    }

    private function determineRole($jabatan)
    {
        $jabatanUpper = strtoupper($jabatan);

        // Kepala Badan = Admin BKPSDM
        if (str_contains($jabatanUpper, 'KEPALA BADAN') || str_contains($jabatanUpper, 'KABID')) {
            return Role::where('name', 'admin_bkpsdm')->first() ?? Role::first();
        }

        // Sub Koordinator = Atasan
        if (str_contains($jabatanUpper, 'SUB KOORDINATOR') || str_contains($jabatanUpper, 'KEPALA SUB BAGIAN')) {
            return Role::where('name', 'atasan')->first() ?? Role::first();
        }

        // Default = Pemohon
        return Role::where('name', 'pemohon')->first() ?? Role::first();
    }
}
