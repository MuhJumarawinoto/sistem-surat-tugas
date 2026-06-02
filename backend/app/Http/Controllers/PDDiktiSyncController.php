<?php

namespace App\Http\Controllers;

use App\Models\PerguruanTinggi;
use App\Models\Prodi;
use App\Services\PDDiktiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PDDiktiSyncController extends Controller
{
    public function __construct(
        private PDDiktiService $pddiktiService
    ) {}

    /**
     * Sync universitas by keyword search
     */
    public function syncUniversitas(Request $request): JsonResponse
    {
        $request->validate([
            'keyword' => 'required|string|min:3',
        ]);

        $keyword = $request->keyword;
        $results = $this->pddiktiService->searchUniversitas($keyword);

        if (!$results) {
            return response()->json([
                'message' => 'Gagal mengambil data dari PDDikti',
            ], 500);
        }

        $synced = 0;
        $updated = 0;

        foreach ($results as $item) {
            $pt = PerguruanTinggi::updateOrCreate(
                ['kode_pt' => $item['kode_pt']],
                [
                    'nama_pt' => $item['nama_pt'],
                    'metadata' => array_merge($item, ['source' => 'pddikti']),
                    'synced_at' => now(),
                ]
            );

            if ($pt->wasRecentlyCreated) {
                $synced++;
            } else {
                $updated++;
            }
        }

        return response()->json([
            'message' => "Berhasil sync {$synced} universitas baru, {$updated} diperbarui",
            'data' => [
                'synced' => $synced,
                'updated' => $updated,
                'total' => $synced + $updated,
            ],
        ]);
    }

    /**
     * Sync prodi by universitas
     */
    public function syncProdi(Request $request): JsonResponse
    {
        $request->validate([
            'perguruan_tinggi_id' => 'required|exists:perguruan_tinggi,id',
        ]);

        $pt = PerguruanTinggi::findOrFail($request->perguruan_tinggi_id);

        // Get prodis from PDDikti
        $prodis = $this->pddiktiService->getUniversitasProdi(
            $pt->kode_pt,
            null,
            true
        );

        if (!$prodis) {
            return response()->json([
                'message' => 'Gagal mengambil data prodi dari PDDikti',
            ], 500);
        }

        $synced = 0;
        $updated = 0;

        foreach ($prodis as $item) {
            Prodi::updateOrCreate(
                [
                    'perguruan_tinggi_id' => $pt->id,
                    'kode_prodi' => $item['kode_prodi'] ?? $item['id_prodi'],
                ],
                [
                    'nama_prodi' => $item['nama_prodi'],
                    'jenjang' => $item['jenjang'],
                    'akreditasi' => $item['akreditasi'],
                    'status_prodi' => $item['status'],
                    'metadata' => array_merge($item, ['source' => 'pddikti']),
                    'synced_at' => now(),
                ]
            );

            // Check if was recently created
            $existing = Prodi::where('perguruan_tinggi_id', $pt->id)
                ->where('kode_prodi', $item['kode_prodi'] ?? $item['id_prodi'])
                ->first();

            if ($existing && $existing->wasRecentlyCreated) {
                $synced++;
            } else {
                $updated++;
            }
        }

        // Update PT with location info
        $detail = $this->pddiktiService->getUniversitasDetail($pt->kode_pt);
        if ($detail) {
            $pt->update([
                'provinsi' => $detail['provinsi'] ?? $pt->provinsi,
                'kab_kota' => $detail['kab_kota'] ?? $pt->kab_kota,
                'alamat' => $detail['alamat'] ?? $pt->alamat,
                'website' => $detail['website'] ?? $pt->website,
                'telepon' => $detail['telepon'] ?? $pt->telepon,
                'email' => $detail['email'] ?? $pt->email,
            ]);
        }

        return response()->json([
            'message' => "Berhasil sync {$synced} prodi baru, {$updated} diperbarui",
            'data' => [
                'synced' => $synced,
                'updated' => $updated,
                'total' => $synced + $updated,
            ],
        ]);
    }

    /**
     * Get synced universitas list
     */
    public function index(Request $request): JsonResponse
    {
        $query = PerguruanTinggi::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('nama_pt', 'like', "%{$search}%")
                  ->orWhere('kode_pt', 'like', "%{$search}%");
        }

        $perPage = $request->per_page ?? 20;
        $result = $query->orderBy('nama_pt')
                       ->paginate($perPage);

        return response()->json([
            'data' => $result->items(),
            'meta' => [
                'current_page' => $result->currentPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
                'last_page' => $result->lastPage(),
            ],
        ]);
    }

    /**
     * Get prodis by universitas
     */
    public function prodis(Request $request): JsonResponse
    {
        $request->validate([
            'perguruan_tinggi_id' => 'required|exists:perguruan_tinggi,id',
        ]);

        $prodis = Prodi::where('perguruan_tinggi_id', $request->perguruan_tinggi_id)
                       ->orderBy('nama_prodi')
                       ->get();

        return response()->json([
            'data' => $prodis,
        ]);
    }

    /**
     * Get universitas detail with prodis
     */
    public function show(string $id): JsonResponse
    {
        $pt = PerguruanTinggi::with('prodis')->findOrFail($id);

        return response()->json([
            'data' => $pt,
        ]);
    }

    /**
     * Delete synced data
     */
    public function destroy(string $id): JsonResponse
    {
        $pt = PerguruanTinggi::findOrFail($id);
        $pt->prodis()->delete();
        $pt->delete();

        return response()->json([
            'message' => 'Data berhasil dihapus',
        ]);
    }

    /**
     * Get sync statistics
     */
    public function stats(): JsonResponse
    {
        $totalPt = PerguruanTinggi::count();
        $totalProdi = Prodi::count();
        $lastSync = PerguruanTinggi::max('synced_at');

        return response()->json([
            'data' => [
                'total_perguruan_tinggi' => $totalPt,
                'total_prodi' => $totalProdi,
                'last_sync' => $lastSync,
            ],
        ]);
    }
}
