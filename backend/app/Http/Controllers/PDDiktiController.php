<?php

namespace App\Http\Controllers;

use App\Services\PDDiktiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PDDiktiController extends Controller
{
    public function __construct(
        private PDDiktiService $pddiktiService
    ) {}

    /**
     * Cari universitas berdasarkan keyword
     */
    public function searchUniversitas(Request $request): JsonResponse
    {
        $request->validate([
            'keyword' => 'required|string|min:2',
        ]);

        $result = $this->pddiktiService->searchUniversitas($request->keyword);

        if ($result === null) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data dari PDDikti',
                'data' => [],
            ], 502);
        }

        return response()->json([
            'success' => true,
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
     */
    public function getUniversitasProdi(Request $request, string $id): JsonResponse
    {
        $semester = $request->query('semester');
        $withDetail = $request->query('with_detail', '0') === '1';

        $result = $this->pddiktiService->getUniversitasProdi($id, $semester, $withDetail);

        return response()->json([
            'success' => true,
            'semester' => $semester ?? $this->pddiktiService->getSemester(),
            'total' => count($result),
            'data' => $result,
        ]);
    }

    /**
     * Cari prodi berdasarkan keyword
     */
    public function searchProdi(Request $request): JsonResponse
    {
        $request->validate([
            'keyword' => 'required|string|min:2',
        ]);

        $result = $this->pddiktiService->searchProdi($request->keyword);

        if ($result === null) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data dari PDDikti',
                'data' => [],
            ], 502);
        }

        return response()->json([
            'success' => true,
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

