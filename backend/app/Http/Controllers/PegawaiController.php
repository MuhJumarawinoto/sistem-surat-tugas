<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PegawaiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::with(['role', 'unitKerja'])
            ->whereNotNull('nip');

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        // Filter by unit kerja
        if ($request->has('unit_kerja_id')) {
            $query->where('unit_kerja_id', $request->unit_kerja_id);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = $request->input('per_page', 15);
        $pegawai = $query->orderBy('name')
            ->paginate($perPage);

        return response()->json($pegawai);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $pegawai = User::with(['role', 'unitKerja'])
            ->where('id', $id)
            ->orWhere('nip', $id)
            ->firstOrFail();

        return response()->json($pegawai);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $pegawai = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'nip' => 'sometimes|required|string|unique:users,nip,' . $id,
            'role_id' => 'sometimes|required|exists:roles,id',
            'unit_kerja_id' => 'sometimes|nullable|exists:unit_kerja,id',
            'pangkat_gol' => 'sometimes|nullable|string|max:50',
            'jabatan' => 'sometimes|nullable|string|max:255',
            'no_hp' => 'sometimes|nullable|string|max:20',
            'alamat' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $pegawai->update($validated);

        return response()->json($pegawai->load(['role', 'unitKerja']));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $pegawai = User::findOrFail($id);
        $pegawai->delete();

        return response()->json(['message' => 'Pegawai berhasil dihapus']);
    }

    public function getRoles(Request $request): JsonResponse
    {
        $roles = \App\Models\Role::all();
        return response()->json($roles);
    }

    public function getUnitKerjas(Request $request): JsonResponse
    {
        $unitKerjas = \App\Models\UnitKerja::where('is_active', true)->get();
        return response()->json($unitKerjas);
    }
}
