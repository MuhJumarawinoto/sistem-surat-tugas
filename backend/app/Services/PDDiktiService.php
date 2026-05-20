<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class PDDiktiService
{
    private string $baseUrl;
    private string $semester;
    private int $cacheTtl;

    public function __construct()
    {
        $this->baseUrl = config('services.pddikti.base_url', 'https://pddikti.rone.dev/api');
        $this->semester = config('services.pddikti.semester', '20241');
        $this->cacheTtl = config('services.pddikti.cache_ttl', 86400); // 24 jam
    }

    private function get(string $endpoint, array $params = [])
    {
        $cacheKey = 'pddikti:' . str_replace('/', ':', ltrim($endpoint, '/')) . ':' . md5(json_encode($params));

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($endpoint, $params) {
            $url = $this->baseUrl . $endpoint;

            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }

            $response = Http::timeout(30)
                ->acceptJson()
                ->withoutVerifying()
                ->get($url);

            if (!$response->successful()) {
                return null;
            }

            return $response->json();
        });
    }

    /**
     * Cari universitas berdasarkan keyword
     */
    public function searchUniversitas(string $keyword): ?array
    {
        $data = $this->get("/search/pt/{$keyword}/");

        if (!$data || !is_array($data)) {
            return null;
        }

        return array_map(fn($item) => [
            'id' => $item['id'] ?? '',
            'kode_pt' => $item['kode'] ?? '',
            'nama_pt' => $item['nama'] ?? '',
            'nama_singkat' => $item['nama_singkat'] ?? '',
        ], $data);
    }

    /**
     * Get detail universitas
     */
    public function getUniversitasDetail(string $id): ?array
    {
        $data = $this->get("/pt/detail/{$id}/");

        if (!$data) {
            return null;
        }

        return [
            'id' => $data['id'] ?? '',
            'nama_pt' => $data['nama_pt'] ?? '',
            'nama_singkat' => $data['nm_singkat'] ?? '',
            'kode_pt' => trim($data['kode_pt'] ?? ''),
            'kelompok' => $data['kelompok'] ?? '',
            'pembina' => $data['pembina'] ?? '',
            'status' => $data['status_pt'] ?? '',
            'akreditasi' => $data['akreditasi_pt'] ?? '',
            'provinsi' => $data['provinsi_pt'] ?? '',
            'kab_kota' => $data['kab_kota_pt'] ?? '',
            'kecamatan' => $data['kecamatan_pt'] ?? '',
            'alamat' => $data['alamat'] ?? '',
            'kode_pos' => $data['kode_pos'] ?? '',
            'website' => $data['website'] ?? '',
            'email' => $data['email'] ?? '',
            'telepon' => $data['no_tel'] ?? '',
            'fax' => $data['no_fax'] ?? '',
            'latitude' => $data['lintang_pt'] ?? 0,
            'longitude' => $data['bujur_pt'] ?? 0,
        ];
    }

    /**
     * Get list prodi universitas
     */
    public function getUniversitasProdi(string $idPt, ?string $semester = null, bool $withDetail = false): ?array
    {
        $smt = $semester ?? $this->semester;
        $prodiList = $this->get("/pt/prodi/{$idPt}/{$smt}");

        if (!$prodiList) {
            return [];
        }

        if (!is_array($prodiList)) {
            $prodiList = [$prodiList];
        }

        $results = [];
        foreach ($prodiList as $p) {
            $rec = [
                'id_prodi' => $p['id_sms'] ?? '',
                'nama_prodi' => $p['nama_prodi'] ?? '',
                'kode_prodi' => $p['kode_prodi'] ?? '',
                'jenjang' => $p['jenj_didik'] ?? '',
                'bidang_ilmu' => $p['kel_bidang'] ?? '',
                'status' => $p['status'] ?? '',
                'akreditasi' => $p['akreditasi'] ?? '',
                'akreditasi_internasional' => $p['akreditasi_internasional'] ?? '',
            ];

            if ($withDetail && !empty($p['id_sms'])) {
                $det = $this->get("/prodi/detail/{$p['id_sms']}/");
                if ($det && !empty($det['nama_prodi'])) {
                    $rec['akreditasi'] = $det['akreditasi'] ?? $rec['akreditasi'];
                    $rec['akreditasi_internasional'] = $det['akreditasi_internasional'] ?? $rec['akreditasi_internasional'];
                    $rec['status'] = $det['status'] ?? $rec['status'];
                }
            }

            $results[] = $rec;
        }

        return $results;
    }

    /**
     * Cari prodi berdasarkan keyword
     */
    public function searchProdi(string $keyword): ?array
    {
        $data = $this->get("/search/prodi/{$keyword}/");

        if (!$data || !is_array($data)) {
            return null;
        }

        return array_map(fn($item) => [
            'id_prodi' => $item['id'] ?? '',
            'nama_prodi' => $item['nama'] ?? '',
            'jenjang' => $item['jenjang'] ?? '',
            'nama_pt' => $item['pt'] ?? '',
            'nama_pt_singkat' => $item['pt_singkat'] ?? '',
        ], $data);
    }

    /**
     * Get detail prodi
     */
    public function getProdiDetail(string $id): ?array
    {
        $data = $this->get("/prodi/detail/{$id}/");

        if (!$data) {
            return null;
        }

        return [
            'id_prodi' => $data['id_sms'] ?? '',
            'nama_prodi' => $data['nama_prodi'] ?? '',
            'kode_prodi' => $data['kode_prodi'] ?? '',
            'jenjang' => $data['jenj_didik'] ?? '',
            'bidang_ilmu' => $data['kel_bidang'] ?? '',
            'status' => $data['status'] ?? '',
            'akreditasi' => $data['akreditasi'] ?? '',
            'akreditasi_internasional' => $data['akreditasi_internasional'] ?? '',
            'status_akreditasi' => $data['status_akreditasi'] ?? '',
            'nama_pt' => $data['nama_pt'] ?? '',
            'kode_pt' => trim($data['kode_pt'] ?? ''),
            'tanggal_berdiri' => $data['tgl_berdiri'] ?? '',
            'sk_selenggara' => $data['sk_selenggara'] ?? '',
            'telepon' => $data['no_tel'] ?? '',
            'fax' => $data['no_fax'] ?? '',
            'website' => $data['website'] ?? '',
            'email' => $data['email'] ?? '',
            'alamat' => $data['alamat'] ?? '',
            'provinsi' => $data['provinsi'] ?? '',
            'kab_kota' => $data['kab_kota'] ?? '',
            'kecamatan' => $data['kecamatan'] ?? '',
            'latitude' => $data['lintang'] ?? 0,
            'longitude' => $data['bujur'] ?? 0,
        ];
    }

    /**
     * Search all (universitas + prodi)
     */
    public function searchAll(string $keyword): ?array
    {
        $data = $this->get("/search/all/{$keyword}/");

        if (!$data) {
            return null;
        }

        $universitas = [];
        $prodi = [];

        foreach ($data['pt'] ?? [] as $item) {
            $universitas[] = [
                'id' => $item['id'] ?? '',
                'kode_pt' => $item['kode'] ?? '',
                'nama_pt' => $item['nama'] ?? '',
                'nama_singkat' => $item['nama_singkat'] ?? '',
            ];
        }

        foreach ($data['prodi'] ?? [] as $item) {
            $prodi[] = [
                'id_prodi' => $item['id'] ?? '',
                'nama_prodi' => $item['nama'] ?? '',
                'jenjang' => $item['jenjang'] ?? '',
                'nama_pt' => $item['pt'] ?? '',
            ];
        }

        return [
            'universitas' => $universitas,
            'prodi' => $prodi,
        ];
    }

    /**
     * Get current semester
     */
    public function getSemester(): string
    {
        return $this->semester;
    }
}
