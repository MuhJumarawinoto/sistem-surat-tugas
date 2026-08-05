<?php

namespace App\Http\Controllers;

use App\Models\PerguruanTinggi;
use App\Models\Prodi;
use App\Services\PDDiktiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PDDiktiController extends Controller
{
    public function __construct(
        private PDDiktiService $pddiktiService
    ) {}

    /**
     * Cari universitas berdasarkan keyword
     * Priority: Local database first, then external API fallback
     */
    public function searchUniversitas(Request $request): JsonResponse
    {
        $request->validate([
            'keyword' => 'required|string|min:2',
        ]);

        $keyword = $request->keyword;

        // Try local database first
        $localResults = PerguruanTinggi::where('nama_pt', 'like', '%'.$keyword.'%')
            ->orWhere('kode_pt', 'like', '%'.$keyword.'%')
            ->orderBy('nama_pt')
            ->limit(50)
            ->get();

        if ($localResults->isNotEmpty()) {
            $result = $localResults->map(function ($pt) {
                return [
                    'id' => (string) $pt->id,
                    'kode_pt' => $pt->kode_pt ?? '',
                    'nama_pt' => $pt->nama_pt,
                    'nama_singkat' => $pt->nama_singkat ?? '',
                ];
            })->toArray();

            return response()->json([
                'success' => true,
                'source' => 'local',
                'total' => count($result),
                'data' => $result,
            ]);
        }

        // Fallback to external API
        $result = $this->pddiktiService->searchUniversitas($keyword);

        if ($result === null) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data dari PDDikti',
                'data' => [],
            ], 502);
        }

        return response()->json([
            'success' => true,
            'source' => 'external',
            'total' => count($result),
            'data' => $result,
        ]);
    }

    /**
     * Get detail universitas
     */
    public function getUniversitasDetail(string $id): JsonResponse
    {
        $result = $this->pddiktiService->getUniversitasDetail($id);

        if ($result === null) {
            return response()->json([
                'success' => false,
                'message' => 'Universitas tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get list prodi universitas
     * Priority: Local database first, then external API fallback
     */
    public function getUniversitasProdi(Request $request, string $id): JsonResponse
    {
        $semester = $request->query('semester');
        $withDetail = $request->query('with_detail', '0') === '1';

        // Try local database first (check by ID or kode_pt)
        $pt = PerguruanTinggi::where('id', $id)
            ->orWhere('kode_pt', $id)
            ->first();

        if ($pt) {
            $localProdis = Prodi::where('perguruan_tinggi_id', $pt->id)
                ->orderBy('nama_prodi')
                ->get();

            if ($localProdis->isNotEmpty()) {
                // Map local data to match PDDikti format
                $result = $localProdis->map(function ($prodi) use ($pt) {
                    return [
                        'id_prodi' => (string) $prodi->id,
                        'nama_prodi' => $prodi->nama_prodi,
                        'kode_prodi' => $prodi->kode_prodi ?? '',
                        'jenjang' => $prodi->jenjang,
                        'bidang_ilmu' => $prodi->bidang_ilmu ?? '',
                        'status' => $prodi->status ?? 'A',
                        'akreditasi' => $prodi->akreditasi ?? '',
                        'akreditasi_internasional' => '',
                        'nama_pt' => $pt->nama_pt,
                        'kode_pt' => $pt->kode_pt ?? '',
                    ];
                })->toArray();

                return response()->json([
                    'success' => true,
                    'semester' => $semester ?? $this->pddiktiService->getSemester(),
                    'source' => 'local',
                    'total' => count($result),
                    'data' => $result,
                ]);
            }
        }

        // Fallback to external API if local data not found
        $result = $this->pddiktiService->getUniversitasProdi($id, $semester, $withDetail);

        return response()->json([
            'success' => true,
            'semester' => $semester ?? $this->pddiktiService->getSemester(),
            'source' => 'external',
            'total' => count($result),
            'data' => $result,
        ]);
    }

    /**
     * Cari prodi berdasarkan keyword
     * Priority: Local database first, then external API fallback
     */
    public function searchProdi(Request $request): JsonResponse
    {
        $request->validate([
            'keyword' => 'required|string|min:2',
        ]);

        $keyword = $request->keyword;

        // Try local database first
        $localResults = Prodi::with('perguruanTinggi:id,nama_pt,kode_pt')
            ->where('nama_prodi', 'like', '%'.$keyword.'%')
            ->orderBy('nama_prodi')
            ->limit(50)
            ->get();

        if ($localResults->isNotEmpty()) {
            $result = $localResults->map(function ($prodi) {
                return [
                    'id_prodi' => (string) $prodi->id,
                    'nama_prodi' => $prodi->nama_prodi,
                    'jenjang' => $prodi->jenjang,
                    'nama_pt' => $prodi->perguruanTinggi->nama_pt ?? '',
                    'nama_pt_singkat' => $prodi->perguruanTinggi->nama_singkat ?? '',
                ];
            })->toArray();

            return response()->json([
                'success' => true,
                'source' => 'local',
                'total' => count($result),
                'data' => $result,
            ]);
        }

        // Fallback to external API
        $result = $this->pddiktiService->searchProdi($keyword);

        if ($result === null) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data dari PDDikti',
                'data' => [],
            ], 502);
        }

        return response()->json([
            'success' => true,
            'source' => 'external',
            'total' => count($result),
            'data' => $result,
        ]);
    }

    /**
     * Get detail prodi
     */
    public function getProdiDetail(string $id): JsonResponse
    {
        $result = $this->pddiktiService->getProdiDetail($id);

        if ($result === null) {
            return response()->json([
                'success' => false,
                'message' => 'Prodi tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Search all (universitas + prodi)
     */
    public function searchAll(Request $request): JsonResponse
    {
        $request->validate([
            'keyword' => 'required|string|min:2',
        ]);

        $result = $this->pddiktiService->searchAll($request->keyword);

        if ($result === null) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data dari PDDikti',
                'data' => ['universitas' => [], 'prodi' => []],
            ], 502);
        }

        return response()->json([
            'success' => true,
            'total' => [
                'universitas' => count($result['universitas'] ?? []),
                'prodi' => count($result['prodi'] ?? []),
            ],
            'data' => $result,
        ]);
    }
}
