<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengajuanController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Pengajuan::with([
            'user:id,name,nip,jabatan,unit_kerja_id',
            'jenjang:id,nama,kode,urutan',
            'dokumen:id,pengajuan_id,jenis_dokumen,file_path,status_verifikasi',
        ]);

        // Check if client wants to include deleted (dicabut) pengajuan
        $includeDeleted = $request->has('include_deleted') && $request->get('include_deleted') === '1';

        if ($user->isPemohon()) {
            // Pemohon biasa: hanya melihat pengajuan sendiri
            $query->where('user_id', $user->id);

            // Exclude 'dicabut' unless explicitly requested
            if (!$includeDeleted) {
                $query->where('status', '!=', 'dicabut');
            }
        } elseif ($user->isAtasan()) {
            // Atasan: melihat pengajuan sendiri saja
            $query->where('user_id', $user->id);

            // Exclude 'dicabut' unless explicitly requested
            if (!$includeDeleted) {
                $query->where('status', '!=', 'dicabut');
            }
        } elseif ($user->isKepalaBkpsdm() && !$user->isKepalaUnit()) {
            // Kepala BKPSDM (bukan kepala unit): hanya melihat pengajuan sendiri untuk riwayat
            $query->where('user_id', $user->id);
            // Include 'dicabut' for riwayat
        } elseif ($user->isKepalaUnit()) {
            // Kepala Dinas/Unit: melihat pengajuan pegawai di unit kerja + pengajuan sendiri
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id) // Pengajuan sendiri
                  ->orWhereHas('user', function ($subQuery) use ($user) {
                      $subQuery->where('unit_kerja_id', $user->unit_kerja_id);
                  }); // Pengajuan pegawai di unit kerja yang sama
            });

            // Exclude 'dicabut' unless explicitly requested
            if (!$includeDeleted) {
                $query->where('status', '!=', 'dicabut');
            }
        } elseif ($user->isAdminBkpsdm()) {
            // Admin melihat semua pengajuan (all statuses)
            // Default filter: show pending first, then others
            if (!$request->has('status')) {
                // Use case-when instead of FIELD for better compatibility
                $query->orderByRaw("
                    CASE status
                        WHEN 'pending_admin' THEN 1
                        WHEN 'verified' THEN 2
                        WHEN 'disetujui' THEN 3
                        WHEN 'ditolak' THEN 4
                        WHEN 'draft' THEN 5
                        WHEN 'signed' THEN 6
                        WHEN 'completed' THEN 7
                        ELSE 8
                    END
                ");
            }
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

        $user = $request->user();
        $nomorPengajuan = $this->generateNomorPengajuan();

        $pengajuan = Pengajuan::create([
            'nomor_pengajuan' => $nomorPengajuan,
            'user_id' => $user->id,
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

        // Pemohon biasa & Atasan: hanya bisa lihat pengajuan sendiri
        if (($user->isPemohon() || $user->isAtasan()) && $pengajuan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($pengajuan);
    }

    public function update(Request $request, string $id)
    {
        $pengajuan = Pengajuan::findOrFail($id);

        $user = $request->user();

        // Pemohon biasa dan Atasan: hanya bisa edit pengajuan sendiri
        if (($user->isPemohon() || $user->isAtasan()) && $pengajuan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$pengajuan->isDraft() && !$pengajuan->isDitolak()) {
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

        // Pemohon biasa dan Atasan: hanya bisa delete pengajuan sendiri
        if (($user->isPemohon() || $user->isAtasan()) && $pengajuan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Bisa delete draft atau yang sudah dicabut (untuk masuk riwayat)
        if (!$pengajuan->isDraft() && $pengajuan->status !== 'dicabut') {
            return response()->json(['message' => 'Hanya dapat menghapus pengajuan draft atau yang sudah dicabut'], 400);
        }

        // Soft delete: change status to 'dicabut' instead of deleting record
        $pengajuan->update([
            'status' => 'dicabut',
            'catatan_tolak' => 'Pengajuan dihapus oleh pemohon',
        ]);

        return response()->json(['message' => 'Pengajuan dihapus dan masuk ke riwayat']);
    }

    public function cancel(Request $request, string $id)
    {
        $pengajuan = Pengajuan::findOrFail($id);

        $user = $request->user();

        // Pemohon biasa dan Atasan: hanya bisa cancel pengajuan sendiri
        if (($user->isPemohon() || $user->isAtasan()) && $pengajuan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Hanya bisa cancel jika status pending atau verified
        if (!in_array($pengajuan->status, ['pending_admin', 'verified'])) {
            return response()->json(['message' => 'Hanya dapat menarik pengajuan dengan status Pending atau Terverifikasi'], 400);
        }

        // Change status back to draft (bisa diedit lagi)
        $pengajuan->update([
            'status' => 'draft',
            'catatan_tolak' => null,
            'tanggal_submit_admin' => null,
            'tanggal_approve_admin' => null,
        ]);

        return response()->json($pengajuan->load(['user', 'jenjang']));
    }

    public function restore(Request $request, string $id)
    {
        $pengajuan = Pengajuan::findOrFail($id);

        $user = $request->user();

        // Pemohon biasa dan Atasan: hanya bisa restore pengajuan sendiri
        if (($user->isPemohon() || $user->isAtasan()) && $pengajuan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Hanya bisa restore jika status dicabut
        if ($pengajuan->status !== 'dicabut') {
            return response()->json(['message' => 'Hanya dapat memulihkan pengajuan dengan status Dicabut'], 400);
        }

        // Restore status ke draft
        $pengajuan->update([
            'status' => 'draft',
            'catatan_tolak' => null,
        ]);

        return response()->json($pengajuan->load(['user', 'jenjang']));
    }

    public function submit(Request $request, string $id)
    {
        $pengajuan = Pengajuan::findOrFail($id);

        $user = $request->user();

        // Pemohon biasa dan Atasan: hanya bisa submit pengajuan sendiri
        if (($user->isPemohon() || $user->isAtasan()) && $pengajuan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$pengajuan->isDraft()) {
            return response()->json(['message' => 'Pengajuan already submitted'], 400);
        }

        // Validasi dokumen dihapus - pengajuan bisa dikirim meskipun dokumen tidak lengkap
        // Admin akan menilai kelengkapan dokumen saat verifikasi
        // Langsung ke admin tanpa approval atasan

        $pengajuan->update([
            'status' => 'pending_admin',
            'tanggal_submit_admin' => now(),
        ]);

        // Notify all admin BKPSDM users
        $adminUsers = User::whereHas('role', function ($query) {
            $query->where('slug', 'admin_bkpsdm');
        })->get();

        foreach ($adminUsers as $admin) {
            Notification::createForUser(
                userId: $admin->id,
                type: 'info',
                title: 'Pengajuan Baru',
                message: "Pengajuan baru dari {$pengajuan->user->name} menunggu verifikasi.",
                pengajuanId: $pengajuan->id
            );
        }

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
