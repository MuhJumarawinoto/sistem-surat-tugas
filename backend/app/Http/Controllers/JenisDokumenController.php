<?php

namespace App\Http\Controllers;

use App\Models\JenisDokumen;
use Illuminate\Http\Request;

class JenisDokumenController extends Controller
{
    /**
     * Display a listing of the document types.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Only admin can access
        if (! $user->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = JenisDokumen::query();

        // Filter by active status if requested
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $jenisDokumen = $query->orderBy('urutan')->get();

        return response()->json($jenisDokumen);
    }

    /**
     * Store a newly created document type.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Only admin can access
        if (! $user->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'kode' => 'required|string|max:50|unique:jenis_dokumen,kode',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'is_wajib' => 'boolean',
            'urutan' => 'integer',
            'persyaratan' => 'nullable|array',
            'catatan' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $jenisDokumen = JenisDokumen::create($validated);

        return response()->json($jenisDokumen, 201);
    }

    /**
     * Display the specified document type.
     */
    public function show(string $id)
    {
        $user = request()->user();

        // Only admin can access
        if (! $user->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $jenisDokumen = JenisDokumen::findOrFail($id);

        return response()->json($jenisDokumen);
    }

    /**
     * Update the specified document type.
     */
    public function update(Request $request, string $id)
    {
        $user = $request->user();

        // Only admin can access
        if (! $user->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $jenisDokumen = JenisDokumen::findOrFail($id);

        $validated = $request->validate([
            'kode' => 'required|string|max:50|unique:jenis_dokumen,kode,'.$id,
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'is_wajib' => 'boolean',
            'urutan' => 'integer',
            'persyaratan' => 'nullable|array',
            'catatan' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $jenisDokumen->update($validated);

        return response()->json($jenisDokumen);
    }

    /**
     * Remove the specified document type.
     */
    public function destroy(string $id)
    {
        $user = request()->user();

        // Only admin can access
        if (! $user->isAdminBkpsdm()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $jenisDokumen = JenisDokumen::findOrFail($id);

        $jenisDokumen->delete();

        return response()->json(['message' => 'Jenis dokumen berhasil dihapus']);
    }

    /**
     * Get active document types for public/pemohon use.
     */
    public function active()
    {
        $jenisDokumen = JenisDokumen::active()->get();

        return response()->json($jenisDokumen);
    }
}
