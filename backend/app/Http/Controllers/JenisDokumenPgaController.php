<?php

namespace App\Http\Controllers;

use App\Models\JenisDokumenPga;
use Illuminate\Http\Request;

class JenisDokumenPgaController extends Controller
{
    /**
     * Display a listing of the PGA document types.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Only admin can access
        if (! $user->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = JenisDokumenPga::query();

        // Filter by active status if requested
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $jenisDokumen = $query->orderBy('urutan')->get();

        return response()->json($jenisDokumen);
    }

    /**
     * Store a newly created PGA document type.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Only admin can access
        if (! $user->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'kode' => 'required|string|max:50|unique:jenis_dokumen_pga,kode',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'is_wajib' => 'boolean',
            'urutan' => 'integer',
            'persyaratan' => 'nullable|array',
            'format_nama' => 'nullable|string|max:255',
            'catatan' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $jenisDokumen = JenisDokumenPga::create($validated);

        return response()->json($jenisDokumen, 201);
    }

    /**
     * Display the specified PGA document type.
     */
    public function show(string $id)
    {
        $user = request()->user();

        // Only admin can access
        if (! $user->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $jenisDokumen = JenisDokumenPga::findOrFail($id);

        return response()->json($jenisDokumen);
    }

    /**
     * Update the specified PGA document type.
     */
    public function update(Request $request, string $id)
    {
        $user = $request->user();

        // Only admin can access
        if (! $user->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $jenisDokumen = JenisDokumenPga::findOrFail($id);

        $validated = $request->validate([
            'kode' => 'required|string|max:50|unique:jenis_dokumen_pga,kode,'.$id,
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'is_wajib' => 'boolean',
            'urutan' => 'integer',
            'persyaratan' => 'nullable|array',
            'format_nama' => 'nullable|string|max:255',
            'catatan' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $jenisDokumen->update($validated);

        return response()->json($jenisDokumen);
    }

    /**
     * Remove the specified PGA document type.
     */
    public function destroy(string $id)
    {
        $user = request()->user();

        // Only admin can access
        if (! $user->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $jenisDokumen = JenisDokumenPga::findOrFail($id);

        $jenisDokumen->delete();

        return response()->json(['message' => 'Jenis dokumen PGA berhasil dihapus']);
    }

    /**
     * Get active PGA document types for public/pemohon use.
     */
    public function active()
    {
        $jenisDokumen = JenisDokumenPga::active()->get();

        return response()->json($jenisDokumen);
    }

    /**
     * Get required PGA document types.
     */
    public function required()
    {
        $jenisDokumen = JenisDokumenPga::required()->get();

        return response()->json($jenisDokumen);
    }

    /**
     * Bulk update urutan (for drag-and-drop reordering)
     */
    public function updateOrder(Request $request)
    {
        $user = $request->user();

        // Only admin can access
        if (! $user->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:jenis_dokumen_pga,id',
            'items.*.urutan' => 'required|integer',
        ]);

        foreach ($validated['items'] as $item) {
            JenisDokumenPga::where('id', $item['id'])->update(['urutan' => $item['urutan']]);
        }

        return response()->json(['message' => 'Urutan dokumen berhasil diperbarui']);
    }
}
