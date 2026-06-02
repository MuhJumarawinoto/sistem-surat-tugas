<?php

namespace App\Console\Commands;

use App\Models\PerguruanTinggi;
use App\Models\Prodi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportPDDiktiData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pddikti:import
                            {--file=scrape_progress.json : Path to the JSON file}
                            {--limit= : Limit number of universities to import}
                            {--skip=0 : Skip first N universities}
                            {--force : Force update existing records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import PDDikti data from JSON file to database';

    protected int $importedPT = 0;
    protected int $updatedPT = 0;
    protected int $skippedPT = 0;
    protected int $importedProdi = 0;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->option('file');
        $limit = $this->option('limit');
        $skip = (int) $this->option('skip');
        $force = $this->option('force');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Reading PDDikti data from: {$filePath}");

        // Read and decode JSON
        $jsonContent = file_get_contents($filePath);
        $data = json_decode($jsonContent, true);

        if (!$data) {
            $this->error("Failed to decode JSON file");
            return Command::FAILURE;
        }

        $totalPT = count($data);
        $this->info("Found {$totalPT} universities in JSON file");

        if ($skip > 0) {
            $this->info("Skipping first {$skip} universities...");
        }

        // Process universities
        $processed = 0;
        foreach ($data as $key => $ptData) {
            if ($skip > 0 && $processed < $skip) {
                $processed++;
                continue;
            }

            if ($limit && $this->importedPT >= $limit) {
                break;
            }

            $this->importPerguruanTinggi($ptData, $force);
            $processed++;

            // Show progress every 10 records
            if ($processed % 10 === 0) {
                $this->info("Processed {$processed} universities...");
            }
        }

        $this->newLine();
        $this->info('Import completed!');
        $this->info("Universities imported: {$this->importedPT}");
        $this->info("Universities updated: {$this->updatedPT}");
        $this->info("Universities skipped: {$this->skippedPT}");
        $this->info("Study programs imported: {$this->importedProdi}");

        return Command::SUCCESS;
    }

    protected function importPerguruanTinggi(array $ptData, bool $force = false): void
    {
        try {
            // Check if PT already exists
            $existingPT = PerguruanTinggi::where('kode_pt', $ptData['kode_pt'] ?? '')->first();

            $ptDataToStore = [
                'kode_pt' => $ptData['kode_pt'] ?? null,
                'nama_pt' => $ptData['nama_pt'] ?? null,
                'nama_singkat' => $ptData['nama_singkat'] ?? $ptData['nama_pt'] ?? null,
                'jenis_perguruan_tinggi' => $ptData['kelompok'] ?? null,
                'alamat' => $ptData['alamat'] ?? null,
                'provinsi' => $ptData['provinsi'] ?? null,
                'kab_kota' => $ptData['kab_kota'] ?? null,
                'kecamatan' => $ptData['kecamatan'] ?? null,
                'kode_pos' => $ptData['kode_pos'] ?? null,
                'website' => $ptData['website'] ?? null,
                'telepon' => $ptData['telepon'] ?? null,
                'email' => $ptData['email'] ?? null,
                'akreditasi' => $ptData['akreditasi'] ?? null,
                'status_pt' => $ptData['status_pt'] ?? null,
                'metadata' => [
                    'id_pt' => $ptData['id_pt'] ?? null,
                    'pembina' => $ptData['pembina'] ?? null,
                    'tanggal_berdiri' => $ptData['tanggal_berdiri'] ?? null,
                ],
                'synced_at' => now(),
            ];

            if ($existingPT) {
                $ptId = $existingPT->id;
                if ($force) {
                    $existingPT->update($ptDataToStore);
                    $this->updatedPT++;
                } else {
                    $this->skippedPT++;
                }
            } else {
                $pt = PerguruanTinggi::create($ptDataToStore);
                $this->importedPT++;
                $ptId = $pt->id;
            }

            // Import prodi (always import prodis even if PT exists)
            if (isset($ptData['prodi']) && is_array($ptData['prodi'])) {
                $this->importProdi($ptId, $ptData['prodi'], $force);
            }

        } catch (\Exception $e) {
            $this->warn("Error importing PT: " . ($ptData['nama_pt'] ?? 'Unknown') . " - " . $e->getMessage());
            Log::error('PDDikti import error', [
                'pt' => $ptData['nama_pt'] ?? 'Unknown',
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function importProdi(int $ptId, array $prodiList, bool $force = false): void
    {
        foreach ($prodiList as $prodiData) {
            try {
                // Check if prodi already exists
                $existingProdi = Prodi::where('kode_prodi', $prodiData['kode_prodi'] ?? '')
                    ->where('perguruan_tinggi_id', $ptId)
                    ->first();

                $prodiDataToStore = [
                    'perguruan_tinggi_id' => $ptId,
                    'kode_prodi' => $prodiData['kode_prodi'] ?? null,
                    'nama_prodi' => $prodiData['nama_prodi'] ?? null,
                    'jenjang' => $prodiData['jenjang'] ?? null,
                    'akreditasi' => $prodiData['akreditasi'] ?? null,
                    'status_prodi' => $prodiData['status'] ?? 'Aktif',
                    'metadata' => [
                        'id_prodi' => $prodiData['id_prodi'] ?? null,
                        'jumlah_mahasiswa' => $prodiData['jumlah_mahasiswa'] ?? null,
                        'jumlah_dosen' => $prodiData['jumlah_dosen'] ?? null,
                    ],
                    'synced_at' => now(),
                ];

                if ($existingProdi) {
                    if ($force) {
                        $existingProdi->update($prodiDataToStore);
                    }
                } else {
                    Prodi::create($prodiDataToStore);
                    $this->importedProdi++;
                }

            } catch (\Exception $e) {
                $this->warn("Error importing prodi: " . ($prodiData['nama_prodi'] ?? 'Unknown') . " - " . $e->getMessage());
            }
        }
    }
}
