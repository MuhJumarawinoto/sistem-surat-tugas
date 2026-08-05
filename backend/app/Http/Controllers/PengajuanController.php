<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class PengajuanController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Pengajuan::with([
            'user:id,name,nip,jabatan,unit_kerja_id',
            'createdBy:id,name,nip,jabatan',
            'jenjang:id,nama,kode,urutan',
            'dokumen:id,pengajuan_id,jenis_dokumen,file_path,status_verifikasi',
        ]);

        // Check if client wants to include deleted (dicabut) pengajuan
        $includeDeleted = $request->has('include_deleted') && $request->input('include_deleted') === '1';

        if ($user->isPemohon()) {
            // Pemohon biasa: hanya melihat pengajuan sendiri
            $query->where('user_id', $user->id);

            // Exclude 'dicabut' unless explicitly requested
            if (! $includeDeleted) {
                $query->where('status', '!=', 'dicabut');
            }
        } elseif ($user->isKepalaUnit()) {
            // Kepala Dinas/Unit: melihat pengajuan pegawai di unit kerja + pengajuan sendiri + yang dibuat untuk orang lain
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id) // Pengajuan sendiri
                    ->orWhere('created_by', $user->id) // Pengajuan yang dibuat untuk orang lain
                    ->orWhereHas('user', function ($subQuery) use ($user) {
                        $subQuery->where('unit_kerja_id', $user->unit_kerja_id);
                    }); // Pengajuan pegawai di unit kerja yang sama
            });

            // Exclude 'dicabut' unless explicitly requested
            if (! $includeDeleted) {
                $query->where('status', '!=', 'dicabut');
            }
        } elseif ($user->isKepalaBkpsdm() && ! $user->isKepalaUnit()) {
            // Kepala BKPSDM (bukan kepala unit): hanya melihat pengajuan sendiri untuk riwayat
            $query->where('user_id', $user->id);
            // Include 'dicabut' for riwayat
        } elseif ($user->isAtasan()) {
            // Atasan biasa (bukan kepala unit): melihat pengajuan sendiri saja
            $query->where('user_id', $user->id);

            // Exclude 'dicabut' unless explicitly requested
            if (! $includeDeleted) {
                $query->where('status', '!=', 'dicabut');
            }
        } elseif ($user->isAdminBkpsdm()) {
            // Admin melihat semua pengajuan (all statuses)
            // Default filter: show pending first, then others
            if (! $request->has('status')) {
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
        $user = $request->user();

        // Base validation rules
        $rules = [
            'nomor_pengajuan' => 'nullable|string|max:255',
            'jenjang_id' => 'required|exists:jenjang_pendidikan,id',
            'nama_prodi' => 'required|string|max:255',
            'perguruan_tinggi' => 'required|string|max:255',
            'akreditasi_prodi' => 'required|string|max:50',
            'lokasi_pt' => 'required|string|max:255',
            'rencana_mulai' => 'required|date',
            'rencana_selesai' => 'required|date|after:rencana_mulai',
        ];

        // For kepala unit, require user_id
        if ($user->isKepalaUnit()) {
            $rules['user_id'] = 'required|exists:users,id';
        }

        $request->validate($rules);

        // Determine target user_id
        $targetUserId = $user->isKepalaUnit() ? $request->user_id : $user->id;

        // Security check for kepala unit creating for others
        if ($user->isKepalaUnit() && $targetUserId !== $user->id) {
            $targetUser = User::findOrFail($targetUserId);
            if ($targetUser->unit_kerja_id !== $user->unit_kerja_id) {
                return response()->json([
                    'message' => 'Hanya dapat membuat pengajuan untuk pegawai di unit kerja yang sama.',
                ], 403);
            }
        }

        // Create pengajuan with retry logic to handle race conditions
        $pengajuan = $this->createPengajuanWithRetry(
            $request,
            $user,
            $targetUserId
        );

        // Notify target staff if created by kepala unit
        if ($pengajuan->isCreatedByKepalaUnit()) {
            Notification::createForUser(
                userId: $pengajuan->user_id,
                type: 'info',
                title: 'Pengajuan Baru Dibuat',
                message: "Pengajuan izin belajar untuk {$pengajuan->nama_prodi} dibuat oleh {$user->name}. Silakan review dan lengkapi jika diperlukan.",
                pengajuanId: $pengajuan->id
            );
        }

        return response()->json([
            'data' => $pengajuan->load(['user', 'createdBy', 'jenjang']),
        ], 201);
    }

    public function show(string $id)
    {
        $pengajuan = Pengajuan::with(['user.unitKerja', 'createdBy', 'jenjang', 'dokumen', 'approvalHistory.approver'])
            ->findOrFail($id);

        $user = request()->user();

        // Admin can view all
        if ($user->isAdminBkpsdm()) {
            return response()->json($pengajuan);
        }

        // Kepala unit can view:
        // - Own pengajuan (user_id === user.id)
        // - Created by self (created_by === user.id)
        // - Staff in same unit (user.unit_kerja_id === pengajuan.user.unit_kerja_id)
        if ($user->isKepalaUnit()) {
            $canView = $pengajuan->user_id === $user->id
                || $pengajuan->created_by === $user->id
                || $pengajuan->user->unit_kerja_id === $user->unit_kerja_id;

            if (! $canView) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            return response()->json($pengajuan);
        }

        // Pemohon biasa & Atasan: hanya bisa lihat pengajuan sendiri
        if (($user->isPemohon() || $user->isAtasan()) && $pengajuan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Kepala BKPSDM (non-unit kepala): hanya pengajuan sendiri
        if ($user->isKepalaBkpsdm() && $pengajuan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($pengajuan);
    }

    public function update(Request $request, string $id)
    {
        $pengajuan = Pengajuan::findOrFail($id);
        $user = $request->user();

        // Use authorization method
        if (! $pengajuan->canBeEditedBy($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (! $pengajuan->isDraft() && ! $pengajuan->isDitolak()) {
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

        return response()->json($pengajuan->load(['user', 'createdBy', 'jenjang']));
    }

    public function destroy(string $id)
    {
        $pengajuan = Pengajuan::findOrFail($id);
        $user = request()->user();

        // Use authorization method
        if (! $pengajuan->canBeEditedBy($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Bisa delete draft atau yang sudah dicabut (untuk masuk riwayat)
        if (! $pengajuan->isDraft() && $pengajuan->status !== 'dicabut') {
            return response()->json(['message' => 'Hanya dapat menghapus pengajuan draft atau yang sudah dicabut'], 400);
        }

        // Soft delete: change status to 'dicabut' instead of deleting record
        $pengajuan->update([
            'status' => 'dicabut',
            'catatan_tolak' => 'Pengajuan dihapus',
        ]);

        return response()->json(['message' => 'Pengajuan dihapus dan masuk ke riwayat']);
    }

    public function cancel(Request $request, string $id)
    {
        $pengajuan = Pengajuan::findOrFail($id);
        $user = $request->user();

        // Use authorization method (owner or creator can cancel)
        if (! $pengajuan->canBeEditedBy($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Hanya bisa cancel jika status pending atau verified
        if (! in_array($pengajuan->status, ['pending_admin', 'verified'])) {
            return response()->json(['message' => 'Hanya dapat menarik pengajuan dengan status Pending atau Terverifikasi'], 400);
        }

        // Change status back to draft (bisa diedit lagi)
        $pengajuan->update([
            'status' => 'draft',
            'catatan_tolak' => null,
            'tanggal_submit_admin' => null,
            'tanggal_approve_admin' => null,
        ]);

        return response()->json($pengajuan->load(['user', 'createdBy', 'jenjang']));
    }

    public function restore(Request $request, string $id)
    {
        $pengajuan = Pengajuan::findOrFail($id);
        $user = $request->user();

        // Use authorization method (owner or creator can restore)
        if (! $pengajuan->canBeEditedBy($user)) {
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

        return response()->json($pengajuan->load(['user', 'createdBy', 'jenjang']));
    }

    public function submit(Request $request, string $id)
    {
        $pengajuan = Pengajuan::findOrFail($id);
        $user = $request->user();

        // Authorization: Admin can submit any pengajuan
        // Kepala unit can submit pengajuan they created (created_by === user.id)
        // Pemohon can submit their own pengajuan (user_id === user.id)
        $canSubmit = $user->isAdminBkpsdm()
            || ($user->isKepalaUnit() && $pengajuan->created_by === $user->id)
            || $pengajuan->user_id === $user->id;

        if (! $canSubmit) {
            return response()->json(['message' => 'Unauthorized. Anda tidak memiliki akses untuk menyetujui pengajuan ini.'], 403);
        }

        if (! $pengajuan->isDraft()) {
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

        return response()->json($pengajuan->load(['user', 'createdBy', 'jenjang']));
    }

    public function getNomor()
    {
        return response()->json([
            'nomor_pengajuan' => $this->generateNomorPengajuanWithRetry(),
        ]);
    }

    private function generateNomorPengajuan(): string
    {
        $year = date('Y');

        // Use lockForUpdate to prevent race condition
        // This locks the row until the transaction completes
        $lastNomor = Pengajuan::whereYear('created_at', $year)
            ->orderBy('created_at', 'desc')
            ->lockForUpdate()
            ->first();

        $sequence = $lastNomor ? (int) substr($lastNomor->nomor_pengajuan, -4) + 1 : 1;

        return 'IBL/'.$year.'/'.str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate nomor pengajuan with retry logic for race conditions
     * This wrapper handles duplicate key errors by retrying with a new sequence
     */
    private function generateNomorPengajuanWithRetry(int $maxRetries = 3): string
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxRetries) {
            try {
                \DB::beginTransaction();

                $nomor = $this->generateNomorPengajuan();

                // Verify this nomor doesn't exist yet (double-check)
                $exists = Pengajuan::where('nomor_pengajuan', $nomor)->lockForUpdate()->first();

                if ($exists) {
                    // If by some chance it exists, generate a new one in next iteration
                    \DB::rollBack();
                    $attempt++;

                    continue;
                }

                \DB::commit();

                return $nomor;
            } catch (\Exception $e) {
                \DB::rollBack();
                $lastException = $e;
                $attempt++;
            }
        }

        // If all retries failed, throw the last exception
        throw $lastException ?? new \Exception('Failed to generate nomor pengajuan after '.$maxRetries.' attempts');
    }

    /**
     * Create pengajuan with retry logic to handle race conditions on duplicate nomor_pengajuan
     * This keeps the lock active until the pengajuan is created
     */
    private function createPengajuanWithRetry(Request $request, $user, $targetUserId, int $maxRetries = 3): Pengajuan
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxRetries) {
            try {
                \DB::beginTransaction();

                // Generate nomor pengajuan with lock
                $nomorPengajuan = $request->filled('nomor_pengajuan')
                    ? $request->nomor_pengajuan
                    : $this->generateNomorPengajuan();

                // Create pengajuan within the same transaction (lock is still active)
                $pengajuan = Pengajuan::create([
                    'nomor_pengajuan' => $nomorPengajuan,
                    'user_id' => $targetUserId,
                    'created_by' => $user->isKepalaUnit() && $targetUserId !== $user->id ? $user->id : null,
                    'jenjang_id' => $request->jenjang_id,
                    'nama_prodi' => $request->nama_prodi,
                    'perguruan_tinggi' => $request->perguruan_tinggi,
                    'akreditasi_prodi' => $request->akreditasi_prodi,
                    'lokasi_pt' => $request->lokasi_pt,
                    'rencana_mulai' => $request->rencana_mulai,
                    'rencana_selesai' => $request->rencana_selesai,
                    'status' => 'draft',
                ]);

                \DB::commit();

                return $pengajuan;
            } catch (QueryException $e) {
                \DB::rollBack();

                // If it's a duplicate key error, retry with a new nomor
                if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'Duplicate entry')) {
                    // Clear the provided nomor so we generate a new one on next attempt
                    $request->merge(['nomor_pengajuan' => null]);
                    $lastException = $e;
                    $attempt++;

                    continue;
                }

                // If it's not a duplicate error, throw immediately
                throw $e;
            } catch (\Exception $e) {
                \DB::rollBack();
                throw $e;
            }
        }

        // If all retries failed, throw the last exception
        throw $lastException ?? new \Exception('Failed to create pengajuan after '.$maxRetries.' attempts');
    }
}
