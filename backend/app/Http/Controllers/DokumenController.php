<?php

namespace App\Http\Controllers;

use App\Models\DokumenPengajuan;
use App\Models\JenisDokumen;
use App\Models\Pengajuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DokumenController extends Controller
{
    public function index(Request $request, string $pengajuanId)
    {
        $pengajuan = Pengajuan::findOrFail($pengajuanId);

        $user = $request->user();

        if ($user->isPemohon() && $pengajuan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($pengajuan->dokumen);
    }

    public function store(Request $request, string $pengajuanId)
    {
        // Get active jenis dokumen from database for dynamic validation
        $activeJenisDokumen = JenisDokumen::where('is_active', true)
            ->pluck('kode')
            ->toArray();

        $request->validate([
            'jenis_dokumen' => 'required|in:'.implode(',', $activeJenisDokumen),
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // Max 5MB
        ]);

        $pengajuan = Pengajuan::findOrFail($pengajuanId);

        $user = $request->user();

        if ($user->isPemohon() && $pengajuan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (! $pengajuan->isDraft() && ! $pengajuan->isDitolak()) {
            return response()->json(['message' => 'Cannot upload documents for this status'], 400);
        }

        $existing = $pengajuan->dokumen()->where('jenis_dokumen', $request->jenis_dokumen)->first();

        if ($existing) {
            Storage::disk('public')->delete($existing->file_path);
            $existing->delete();
        }

        $path = $request->file('file')->store('dokumen/'.$pengajuanId, 'public');

        $dokumen = DokumenPengajuan::create([
            'pengajuan_id' => $pengajuanId,
            'jenis_dokumen' => $request->jenis_dokumen,
            'file_path' => $path,
            'file_name' => $request->file('file')->getClientOriginalName(),
            'file_type' => $request->file('file')->getMimeType(),
            'file_size' => $request->file('file')->getSize(),
            'status_verifikasi' => 'pending',
        ]);

        return response()->json($dokumen, 201);
    }

    public function destroy(string $id)
    {
        $dokumen = DokumenPengajuan::findOrFail($id);

        $user = request()->user();

        if ($user->isPemohon() && $dokumen->pengajuan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (! $dokumen->pengajuan->isDraft() && ! $dokumen->pengajuan->isDitolak()) {
            return response()->json(['message' => 'Cannot delete documents for this status'], 400);
        }

        Storage::disk('public')->delete($dokumen->file_path);

        $dokumen->delete();

        return response()->json(['message' => 'Dokumen deleted successfully']);
    }
}
