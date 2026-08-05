<?php

namespace App\Http\Controllers;

use App\Models\Pengajuan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPengajuanController extends Controller
{
    /**
     * Delete a pengajuan (admin only).
     *
     * Admin can delete any pengajuan regardless of status.
     * This is a permanent deletion that removes the record from the database.
     *
     * @return JsonResponse
     */
    public function destroy(string $id)
    {
        $pengajuan = Pengajuan::findOrFail($id);

        // Admin authorization is handled by the 'admin' middleware on the route

        // Permanent delete: remove the record from database
        // This will cascade delete related records if foreign keys are set up properly
        $pengajuan->delete();

        return response()->json([
            'message' => 'Pengajuan berhasil dihapus secara permanen',
        ]);
    }

    /**
     * Delete multiple pengajuans at once (admin only).
     *
     * Admin can delete multiple pengajuans in a single request.
     */
    public function destroyMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $ids = $request->input('ids');
        $count = Pengajuan::whereIn('id', $ids)->delete();

        return response()->json([
            'message' => "{$count} pengajuan berhasil dihapus secara permanen",
            'deleted_count' => $count,
        ]);
    }
}
