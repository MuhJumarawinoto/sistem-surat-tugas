<?php

namespace App\Http\Controllers;

use App\Models\JenisDokumen;
use App\Models\JenisDokumenPga;
use App\Models\JenjangPendidikan;
use App\Models\PerguruanTinggi;
use App\Models\Prodi;
use App\Models\UnitKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MasterController extends Controller
{
    private const CACHE_TTL = 3600; // 1 hour

    private const CACHE_TTL_SHORT = 300; // 5 minutes - for frequently changed data

    public function jenjang()
    {
        $jenjang = Cache::remember('master:jenjang', self::CACHE_TTL, function () {
            return JenjangPendidikan::where('is_active', true)
                ->orderBy('urutan')
                ->get(['id', 'nama', 'kode', 'urutan'])
                ->toArray();
        });

        return response()->json($jenjang);
    }

    public function unitKerja()
    {
        $unitKerja = Cache::remember('master:unit_kerja', self::CACHE_TTL, function () {
            return UnitKerja::where('is_active', true)
                ->orderBy('nama')
                ->get(['id', 'nama', 'singkatan'])
                ->toArray();
        });

        return response()->json($unitKerja);
    }

    /**
     * Clear all master data cache
     * Call this after updating master data
     */
    public function clearCache()
    {
        Cache::forget('master:jenjang');
        Cache::forget('master:unit_kerja');
        Cache::forget('master:jenis_dokumen');
        Cache::forget('master:jenis_dokumen_pga');
        Cache::forget('master:akreditasi');
        Cache::forget('master:jenis_dokumen');

        return response()->json(['message' => 'Master data cache cleared']);
    }

    public function statusPengajuan()
    {
        $status = [
            ['value' => 'draft', 'label' => 'Draft'],
            ['value' => 'pending_atasan', 'label' => 'Pending Atasan'],
            ['value' => 'pending_admin', 'label' => 'Pending Admin'],
            ['value' => 'disetujui', 'label' => 'Disetujui'],
            ['value' => 'ditolak', 'label' => 'Ditolak'],
            ['value' => 'selesai', 'label' => 'Selesai'],
        ];

        return response()->json($status);
    }

    /**
     * Get active jenis dokumen (public endpoint)
     * Note: Uses shorter cache (5 min) because admins can add/edit document types frequently
     * Query param ?refresh=true bypasses cache and forces fresh data
     */
    public function jenisDokumen(Request $request)
    {
        $refresh = $request->query('refresh', false);

        if ($refresh) {
            // Bypass cache - get fresh data and update cache
            $jenisDokumen = JenisDokumen::active()
                ->get(['id', 'kode', 'nama', 'deskripsi', 'is_wajib', 'urutan', 'persyaratan', 'catatan'])
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'value' => $item->kode,
                        'label' => $item->nama,
                        'kode' => $item->kode,
                        'nama' => $item->nama,
                        'deskripsi' => $item->deskripsi,
                        'required' => $item->is_wajib,
                        'urutan' => $item->urutan,
                        'persyaratan' => $item->persyaratan,
                        'catatan' => $item->catatan,
                    ];
                })
                ->sortBy('urutan')
                ->values()
                ->toArray();

            // Update cache with fresh data
            Cache::put('master:jenis_dokumen', $jenisDokumen, self::CACHE_TTL_SHORT);

            return response()->json($jenisDokumen);
        }

        $jenisDokumen = Cache::remember('master:jenis_dokumen', self::CACHE_TTL_SHORT, function () {
            return JenisDokumen::active()
                ->get(['id', 'kode', 'nama', 'deskripsi', 'is_wajib', 'urutan', 'persyaratan', 'catatan'])
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'value' => $item->kode,
                        'label' => $item->nama,
                        'kode' => $item->kode,
                        'nama' => $item->nama,
                        'deskripsi' => $item->deskripsi,
                        'required' => $item->is_wajib,
                        'urutan' => $item->urutan,
                        'persyaratan' => $item->persyaratan,
                        'catatan' => $item->catatan,
                    ];
                })
                ->sortBy('urutan')
                ->values()
                ->toArray();
        });

        return response()->json($jenisDokumen);
    }

    /**
     * Get active jenis dokumen PGA (public endpoint)
     * Note: Uses shorter cache (5 min) because admins can add/edit document types frequently
     * Query param ?refresh=true bypasses cache and forces fresh data
     */
    public function jenisDokumenPga(Request $request)
    {
        $refresh = $request->query('refresh', false);

        if ($refresh) {
            // Bypass cache - get fresh data and update cache
            $jenisDokumen = JenisDokumenPga::active()
                ->get(['id', 'kode', 'nama', 'deskripsi', 'is_wajib', 'urutan', 'persyaratan', 'format_nama', 'catatan'])
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'value' => $item->kode,
                        'label' => $item->nama,
                        'kode' => $item->kode,
                        'nama' => $item->nama,
                        'deskripsi' => $item->deskripsi,
                        'required' => $item->is_wajib,
                        'urutan' => $item->urutan,
                        'persyaratan' => $item->persyaratan,
                        'format_nama' => $item->format_nama,
                        'catatan' => $item->catatan,
                    ];
                })
                ->sortBy('urutan')
                ->values()
                ->toArray();

            // Update cache with fresh data
            Cache::put('master:jenis_dokumen_pga', $jenisDokumen, self::CACHE_TTL_SHORT);

            return response()->json($jenisDokumen);
        }

        $jenisDokumen = Cache::remember('master:jenis_dokumen_pga', self::CACHE_TTL_SHORT, function () {
            return JenisDokumenPga::active()
                ->get(['id', 'kode', 'nama', 'deskripsi', 'is_wajib', 'urutan', 'persyaratan', 'format_nama', 'catatan'])
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'value' => $item->kode,
                        'label' => $item->nama,
                        'kode' => $item->kode,
                        'nama' => $item->nama,
                        'deskripsi' => $item->deskripsi,
                        'required' => $item->is_wajib,
                        'urutan' => $item->urutan,
                        'persyaratan' => $item->persyaratan,
                        'format_nama' => $item->format_nama,
                        'catatan' => $item->catatan,
                    ];
                })
                ->sortBy('urutan')
                ->values()
                ->toArray();
        });

        return response()->json($jenisDokumen);
    }

    public function akreditasi()
    {
        // Fetch from database dynamically
        $akreditasiList = Cache::remember('master:akreditasi', self::CACHE_TTL, function () {
            $values = Prodi::selectRaw('DISTINCT akreditasi')
                ->whereNotNull('akreditasi')
                ->where('akreditasi', '!=', '')
                ->orderBy('akreditasi')
                ->pluck('akreditasi')
                ->toArray();

            return array_map(function ($item) {
                return [
                    'value' => $item,
                    'label' => $item,
                ];
            }, $values);
        });

        return response()->json($akreditasiList);
    }

    public function perguruanTinggi(Request $request)
    {
        $keyword = $request->input('keyword');

        $query = PerguruanTinggi::orderBy('nama_pt');

        if ($keyword) {
            $query->where('nama_pt', 'like', '%'.$keyword.'%');
        }

        $pt = $query->limit(100)->get(['id', 'kode_pt', 'nama_pt', 'provinsi', 'kab_kota', 'akreditasi']);

        return response()->json($pt);
    }

    public function prodi(Request $request)
    {
        $ptId = $request->input('perguruan_tinggi_id');
        $keyword = $request->input('keyword');

        $query = Prodi::with('perguruanTinggi:id,nama_pt');

        if ($ptId) {
            $query->where('perguruan_tinggi_id', $ptId);
        }

        if ($keyword) {
            $query->where('nama_prodi', 'like', '%'.$keyword.'%');
        }

        $prodi = $query->orderBy('nama_prodi')->limit(100)->get();

        return response()->json($prodi);
    }

    // ==================== ADMIN CRUD METHODS ====================

    /**
     * Get all jenis dokumen (admin only - includes inactive)
     */
    public function adminJenisDokumenIndex()
    {
        $jenisDokumen = JenisDokumen::allWithInactive()->get();

        return response()->json($jenisDokumen);
    }

    /**
     * Get single jenis dokumen (admin only)
     */
    public function showJenisDokumen(string $id)
    {
        $jenisDokumen = JenisDokumen::findOrFail($id);

        return response()->json($jenisDokumen);
    }

    /**
     * Create new jenis dokumen (admin only)
     */
    public function storeJenisDokumen(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|string|unique:jenis_dokumen,kode',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'is_wajib' => 'boolean',
            'urutan' => 'integer|min:0',
            'persyaratan' => 'nullable|array',
            'persyaratan.*' => 'string',
            'catatan' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $jenisDokumen = JenisDokumen::create($validated);

        // Clear cache
        Cache::forget('master:jenis_dokumen');

        return response()->json($jenisDokumen, 201);
    }

    /**
     * Update jenis dokumen (admin only)
     */
    public function updateJenisDokumen(Request $request, string $id)
    {
        $jenisDokumen = JenisDokumen::findOrFail($id);

        $validated = $request->validate([
            'kode' => 'required|string|unique:jenis_dokumen,kode,'.$id,
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'is_wajib' => 'boolean',
            'urutan' => 'integer|min:0',
            'persyaratan' => 'nullable|array',
            'persyaratan.*' => 'string',
            'catatan' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $jenisDokumen->update($validated);

        // Clear cache
        Cache::forget('master:jenis_dokumen');

        return response()->json($jenisDokumen);
    }

    /**
     * Delete jenis dokumen (admin only - soft delete)
     */
    public function destroyJenisDokumen(string $id)
    {
        $jenisDokumen = JenisDokumen::findOrFail($id);

        // Soft delete - set is_active to false
        $jenisDokumen->update(['is_active' => false]);

        // Clear cache
        Cache::forget('master:jenis_dokumen');

        return response()->json(['message' => 'Jenis dokumen dinonaktifkan']);
    }
}
