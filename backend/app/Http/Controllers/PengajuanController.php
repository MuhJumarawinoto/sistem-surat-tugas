<?php

namespace App\Http\Controllers;

use App\Models\Pengajuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengajuanController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Pengajuan::with(['user', 'jenjang', 'dokumen']);

        if ($user->isPemohon()) {
            $query->where('user_id', $user->id);
        } elseif ($user->isAtasan()) {
            // DEBUG: Log unit kerja
            \Log::info('Atasan unit_kerja_id: ' . $user->unit_kerja_id);

            // Atasan hanya melihat pengajuan dari unit kerja yang sama
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('unit_kerja_id', $user->unit_kerja_id);
            });

            // Default filter untuk atasan: hanya yang pending
            if (!$request->has('status')) {
                $query->where('status', 'pending_atasan');
            }

            // DEBUG: Log SQL
            \Log::info('SQL: ' . $query->toSql());
        } elseif ($user->isAdminBkpsdm()) {
            $query->where('status', 'pending_admin');
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $pengajuan = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json($pengajuan);
    }

    public function store(Request $request)
    {
        $request->validate([
            'jenjang_id' => 'required|exists:jenjang_pendidikan,id',
            'nama_prodi' => 'required|string|max:255',
            'perguruan_tinggi' => 'required|string|max:255',
            'akreditasi_prodi' => 'required|string|max:50',
            'lokasi_pt' => 'required|string|max:255',
            'rencana_mulai' => 'required|date',
            'rencana_selesai' => 'required|date|after:rencana_mulai',
        ]);

        $nomorPengajuan = $this->generateNomorPengajuan();

        $pengajuan = Pengajuan::create([
            'nomor_pengajuan' => $nomorPengajuan,
            'user_id' => $request->user()->id,
            'jenjang_id' => $request->jenjang_id,
            'nama_prodi' => $request->nama_prodi,
            'perguruan_tinggi' => $request->perguruan_tinggi,
            'akreditasi_prodi' => $request->akreditasi_prodi,
            'lokasi_pt' => $request->lokasi_pt,
            'rencana_mulai' => $request->rencana_mulai,
            'rencana_selesai' => $request->rencana_selesai,
            'status' => 'draft',
        ]);

        return response()->json($pengajuan->load(['user', 'jenjang']), 201);
    }

    public function show(string $id)
    {
        $pengajuan = Pengajuan::with(['user', 'jenjang', 'dokumen', 'approvalHistory.approver'])
            ->findOrFail($id);

        $user = request()->user();

        if ($user->isPemohon() && $pengajuan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($pengajuan);
    }

    public function update(Request $request, string $id)
    {
        $pengajuan = Pengajuan::findOrFail($id);

        $user = $request->user();

        if ($user->isPemohon() && $pengajuan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$pengajuan->isDraft()) {
            return response()->json(['message' => 'Cannot update submitted pengajuan'], 400);
        }

        $request->validate([
            'jenjang_id' => 'required|exists:jenjang_pendidikan,id',
            'nama_prodi' => 'required|string|max:255',
            'perguruan_tinggi' => 'required|string|max:255',
            'akreditasi_prodi' => 'required|string|max:50',
            'lokasi_pt' => 'required|string|max:255',
            'rencana_mulai' => 'required|date',
            'rencana_selesai' => 'required|date|after:rencana_mulai',
        ]);

        $pengajuan->update([
            'jenjang_id' => $request->jenjang_id,
            'nama_prodi' => $request->nama_prodi,
            'perguruan_tinggi' => $request->perguruan_tinggi,
            'akreditasi_prodi' => $request->akreditasi_prodi,
            'lokasi_pt' => $request->lokasi_pt,
            'rencana_mulai' => $request->rencana_mulai,
            'rencana_selesai' => $request->rencana_selesai,
        ]);

        return response()->json($pengajuan->load(['user', 'jenjang']));
    }

    public function destroy(string $id)
    {
        $pengajuan = Pengajuan::findOrFail($id);

        $user = request()->user();

        if ($user->isPemohon() && $pengajuan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$pengajuan->isDraft()) {
            return response()->json(['message' => 'Cannot delete submitted pengajuan'], 400);
        }

        $pengajuan->delete();

        return response()->json(['message' => 'Pengajuan deleted successfully']);
    }

    public function submit(Request $request, string $id)
    {
        $pengajuan = Pengajuan::findOrFail($id);

        $user = $request->user();

        if ($user->isPemohon() && $pengajuan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$pengajuan->isDraft()) {
            return response()->json(['message' => 'Pengajuan already submitted'], 400);
        }

        // Validasi dokumen dihapus - pengajuan bisa dikirim meskipun dokumen tidak lengkap
        // Atasan/Admin akan menilai kelengkapan dokumen saat verifikasi

        $pengajuan->update([
            'status' => 'pending_atasan',
            'tanggal_submit_atasan' => now(),
        ]);

        return response()->json($pengajuan->load(['user', 'jenjang']));
    }

    public function getNomor()
    {
        return response()->json([
            'nomor_pengajuan' => $this->generateNomorPengajuan(),
        ]);
    }

    private function generateNomorPengajuan(): string
    {
        $year = date('Y');

        $lastNomor = Pengajuan::whereYear('created_at', $year)
            ->orderBy('created_at', 'desc')
            ->first();

        $sequence = $lastNomor ? (int) substr($lastNomor->nomor_pengajuan, -4) + 1 : 1;

        return 'IBL/' . $year . '/' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
