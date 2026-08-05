<?php

namespace App\Console\Commands;

use App\Models\PerguruanTinggi;
use App\Models\Prodi;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Signature('app:import-prodi-from-json')]
#[Description('Import prodi data from LLDIKTI JSON file')]
class ImportProdiFromJson extends Command
{
    /**
     * The JSON file path.
     */
    protected string $jsonPath;

    /**
     * Statistics.
     */
    protected array $stats = [
        'pt_created' => 0,
        'pt_updated' => 0,
        'prodi_created' => 0,
        'prodi_updated' => 0,
        'errors' => [],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Importing prodi data from LLDIKTI JSON...');

        $jsonPath = 'C:/Users/Bahlil/pddikti/prodi_diploma_lldikti3.json';

        if (! file_exists($jsonPath)) {
            $this->error("JSON file not found at: {$jsonPath}");

            return self::FAILURE;
        }

        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);

        if (! isset($data['data']) || ! is_array($data['data'])) {
            $this->error('Invalid JSON format. Expected "data" array.');

            return self::FAILURE;
        }

        $this->info('Found '.count($data['data']).' prodi records to process.');
        $this->newLine();

        // Start transaction
        DB::beginTransaction();

        try {
            foreach ($data['data'] as $index => $item) {
                $this->processItem($item, $index + 1, count($data['data']));
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Import failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        // Display summary
        $this->newLine();
        $this->info('=== Import Summary ===');
        $this->info("Perguruan Tinggi Created: {$this->stats['pt_created']}");
        $this->info("Perguruan Tinggi Updated: {$this->stats['pt_updated']}");
        $this->info("Prodi Created: {$this->stats['prodi_created']}");
        $this->info("Prodi Updated: {$this->stats['prodi_updated']}");

        if (! empty($this->stats['errors'])) {
            $this->newLine();
            $this->warn('Errors: '.count($this->stats['errors']));
            foreach ($this->stats['errors'] as $error) {
                $this->warn("  - {$error}");
            }
        }

        $this->newLine();
        $this->info('Import completed successfully!');

        return self::SUCCESS;
    }

    /**
     * Process a single item from JSON.
     */
    protected function processItem(array $item, int $current, int $total): void
    {
        $this->output->write("\r  Processing {$current}/{$total}: {$item['nama_program_studi']}... ");

        try {
            // Find or create Perguruan Tinggi
            $pt = $this->findOrCreatePerguruanTinggi($item);

            // Find or create Prodi
            $this->findOrCreateProdi($item, $pt->id);

            $this->output->write("\r  \e[32m✓\e[0m Processed {$current}/{$total}: {$item['nama_program_studi']}       \n");
        } catch (\Exception $e) {
            $this->stats['errors'][] = $item['nama_program_studi'].': '.$e->getMessage();
            $this->output->write("\r  \e[31m✗\e[0m Failed: {$item['nama_program_studi']}       \n");
        }
    }

    /**
     * Find or create Perguruan Tinggi.
     */
    protected function findOrCreatePerguruanTinggi(array $item): PerguruanTinggi
    {
        // Extract provinsi from wilayah (e.g., "LLDIKTI III (DKI Jakarta)" -> "DKI Jakarta")
        $provinsi = $this->extractProvinsi($item['wilayah'] ?? '');

        $pt = PerguruanTinggi::where('kode_pt', $item['kode_pt'])->first();

        if (! $pt) {
            $pt = PerguruanTinggi::create([
                'kode_pt' => $item['kode_pt'],
                'nama_pt' => $item['nama_perguruan_tinggi'],
                'nama_singkat' => $this->createSingkatan($item['nama_perguruan_tinggi']),
                'jenis_perguruan_tinggi' => 'Universitas', // Default, can be updated
                'provinsi' => $provinsi,
                'akreditasi' => null, // Not in JSON
                'status_pt' => $item['status'] ?? 'Aktif',
                'metadata' => [
                    'wilayah' => $item['wilayah'] ?? null,
                    'source' => 'LLDIKTI III',
                ],
                'synced_at' => now(),
            ]);
            $this->stats['pt_created']++;
        } else {
            // Update existing PT
            $pt->update([
                'nama_pt' => $item['nama_perguruan_tinggi'],
                'provinsi' => $provinsi,
                'status_pt' => $item['status'] ?? 'Aktif',
                'synced_at' => now(),
            ]);
            $this->stats['pt_updated']++;
        }

        return $pt;
    }

    /**
     * Find or create Prodi.
     */
    protected function findOrCreateProdi(array $item, int $ptId): Prodi
    {
        $prodi = Prodi::where('kode_prodi', $item['kode_prodi'])
            ->where('perguruan_tinggi_id', $ptId)
            ->first();

        $akreditasi = ! empty($item['akreditasi']) && $item['akreditasi'] !== '-' ? $item['akreditasi'] : null;

        if (! $prodi) {
            $prodi = Prodi::create([
                'perguruan_tinggi_id' => $ptId,
                'kode_prodi' => $item['kode_prodi'],
                'nama_prodi' => $item['nama_program_studi'],
                'jenjang' => $item['jenjang'],
                'akreditasi' => $akreditasi,
                'status_prodi' => $item['status'] ?? 'Aktif',
                'id_prodi_external' => $item['kode_prodi'],
                'metadata' => [
                    'semester_mulai' => $item['semester_mulai'] ?? null,
                    'tanggal_akhir_akreditasi' => $item['tanggal_akhir_akreditasi'] ?? null,
                    'wilayah' => $item['wilayah'] ?? null,
                    'source' => 'LLDIKTI III',
                ],
                'synced_at' => now(),
            ]);
            $this->stats['prodi_created']++;
        } else {
            // Update existing prodi
            $prodi->update([
                'nama_prodi' => $item['nama_program_studi'],
                'jenjang' => $item['jenjang'],
                'akreditasi' => $akreditasi,
                'status_prodi' => $item['status'] ?? 'Aktif',
                'synced_at' => now(),
            ]);
            $this->stats['prodi_updated']++;
        }

        return $prodi;
    }

    /**
     * Extract provinsi from wilayah string.
     */
    protected function extractProvinsi(string $wilayah): string
    {
        if (preg_match('/\(([^)]+)\)/', $wilayah, $matches)) {
            return $matches[1];
        }

        return $wilayah;
    }

    /**
     * Create singkatan from nama perguruan tinggi.
     */
    protected function createSingkatan(string $nama): string
    {
        // Take first letters of each word
        $words = explode(' ', $nama);
        $singkatan = '';

        foreach ($words as $word) {
            $singkatan .= mb_substr($word, 0, 1);
        }

        // Limit to 10 characters
        return Str::limit($singkatan, 10, '');
    }
}
