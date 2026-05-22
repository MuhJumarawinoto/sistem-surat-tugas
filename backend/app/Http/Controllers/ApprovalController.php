<?php

namespace App\Http\Controllers;

use App\Models\ApprovalHistory;
use App\Models\Notification;
use App\Models\Pengajuan;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function approveAtasan(Request $request, string $id)
    {
        $pengajuan = Pengajuan::with('user')->findOrFail($id);

        if (!$pengajuan->isPendingAtasan()) {
            return response()->json(['message' => 'Pengajuan is not pending atasan approval'], 400);
        }

        $pengajuan->update([
            'status' => 'pending_admin',
            'tanggal_approve_atasan' => now(),
        ]);

        ApprovalHistory::create([
            'pengajuan_id' => $id,
            'approver_id' => $request->user()->id,
            'role_approval' => 'atasan',
            'status' => 'setuju',
            'catatan' => $request->catatan,
        ]);

        // Send notification to pemohon
        $message = 'Pengajuan Anda telah disetujui oleh atasan';
        if ($request->catatan) {
            $message .= '. Catatan: ' . $request->catatan;
        }
        Notification::createForUser(
            $pengajuan->user_id,
            'success',
            'Pengajuan Disetujui Atasan',
            $message,
            $pengajuan->id
        );

        return response()->json($pengajuan->load(['user', 'jenjang', 'approvalHistory']));
    }

    public function approveAdmin(Request $request, string $id)
    {
        $pengajuan = Pengajuan::with('user')->findOrFail($id);

        if (!$pengajuan->isPendingAdmin()) {
            return response()->json(['message' => 'Pengajuan is not pending admin approval'], 400);
        }

        $request->validate([
            'catatan' => 'nullable|string',
        ]);

        $pengajuan->update([
            'status' => 'disetujui',
            'tanggal_approve_admin' => now(),
        ]);

        ApprovalHistory::create([
            'pengajuan_id' => $id,
            'approver_id' => $request->user()->id,
            'role_approval' => 'admin_bkpsdm',
            'status' => 'setuju',
            'catatan' => $request->catatan,
        ]);

        // Send notification to pemohon
        $message = 'Pengajuan Anda telah disetujui oleh Admin BKPSDM';
        if ($request->catatan) {
            $message .= '. Catatan: ' . $request->catatan;
        }
        Notification::createForUser(
            $pengajuan->user_id,
            'success',
            'Pengajuan Disetujui Admin',
            $message,
            $pengajuan->id
        );

        return response()->json($pengajuan->load(['user', 'jenjang', 'approvalHistory']));
    }

    public function reject(Request $request, string $id)
    {
        $pengajuan = Pengajuan::findOrFail($id);

        $user = $request->user();
        $roleApproval = match (true) {
            $user->isAtasan() && $pengajuan->isPendingAtasan() => 'atasan',
            $user->isAdminBkpsdm() && $pengajuan->isPendingAdmin() => 'admin_bkpsdm',
            default => null,
        };

        if (!$roleApproval) {
            return response()->json(['message' => 'Cannot reject this pengajuan'], 400);
        }

        $request->validate([
            'catatan' => 'required|string',
        ]);

        $pengajuan->update([
            'status' => 'ditolak',
            'catatan_tolak' => $request->catatan,
        ]);

        ApprovalHistory::create([
            'pengajuan_id' => $id,
            'approver_id' => $user->id,
            'role_approval' => $roleApproval,
            'status' => 'tolak',
            'catatan' => $request->catatan,
        ]);

        // Send notification to pemohon
        $roleName = $roleApproval === 'atasan' ? 'Atasan' : 'Admin BKPSDM';
        Notification::createForUser(
            $pengajuan->user_id,
            'error',
            "Pengajuan Ditolak oleh $roleName",
            "Pengajuan Anda ditolak. Alasan: {$request->catatan}",
            $pengajuan->id
        );

        return response()->json($pengajuan->load(['user', 'jenjang', 'approvalHistory']));
    }

    public function verifyDocuments(Request $request, string $id)
    {
        $request->validate([
            'dokumen_id' => 'required|exists:dokumen_pengajuan,id',
            'status' => 'required|in:lengkap,tidak_lengkap',
            'catatan' => 'nullable|string',
        ]);

        $pengajuan = Pengajuan::with('user')->findOrFail($id);

        if (!$pengajuan->isPendingAdmin()) {
            return response()->json(['message' => 'Pengajuan is not pending admin verification'], 400);
        }

        $dokumen = $pengajuan->dokumen()->findOrFail($request->dokumen_id);

        $dokumen->update([
            'status_verifikasi' => $request->status,
            'catatan' => $request->catatan,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        // Send notification if document is marked as incomplete with notes
        if ($request->status === 'tidak_lengkap' && $request->catatan) {
            Notification::createForUser(
                $pengajuan->user_id,
                'warning',
                'Dokumen Perlu Diperbaiki',
                "Dokumen {$dokumen->file_name} ditandai sebagai tidak lengkap. Catatan: {$request->catatan}",
                $pengajuan->id
            );
        }

        return response()->json($dokumen);
    }

    /**
     * Verify a single document (used by frontend modal)
     */
    public function verifyDocument(Request $request, string $dokumenId)
    {
        $request->validate([
            'status' => 'required|in:lengkap,tidak_lengkap',
            'catatan' => 'nullable|string',
        ]);

        $dokumen = \App\Models\DokumenPengajuan::with('pengajuan.user')->findOrFail($dokumenId);

        $pengajuan = $dokumen->pengajuan;
        if (!$pengajuan->isPendingAdmin()) {
            return response()->json(['message' => 'Pengajuan is not pending admin verification'], 400);
        }

        $oldStatus = $dokumen->status_verifikasi;
        $dokumen->update([
            'status_verifikasi' => $request->status,
            'catatan' => $request->catatan,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        // Send notification if document is marked as incomplete with notes
        if ($request->status === 'tidak_lengkap' && $request->catatan && $oldStatus !== 'tidak_lengkap') {
            Notification::createForUser(
                $pengajuan->user_id,
                'warning',
                'Dokumen Perlu Diperbaiki',
                "Dokumen {$dokumen->file_name} ditandai sebagai tidak lengkap. Catatan: {$request->catatan}",
                $pengajuan->id
            );
        }

        return response()->json($dokumen);
    }

    public function sendNotification(Request $request, string $id)
    {
        $pengajuan = Pengajuan::with('user')->findOrFail($id);

        $request->validate([
            'message' => 'required|string',
            'type' => 'nullable|in:info,warning,success,error',
        ]);

        $type = $request->type ?? 'info';
        $user = $request->user();
        $senderRole = match (true) {
            $user->isAdminBkpsdm() => 'Admin BKPSDM',
            $user->isKepalaBkpsdm() => 'Kepala BKPSDM',
            $user->isAtasan() => 'Atasan',
            default => 'Sistem',
        };

        Notification::createForUser(
            $pengajuan->user_id,
            $type,
            "Pesan dari $senderRole",
            $request->message,
            $pengajuan->id
        );

        return response()->json(['message' => 'Notification sent successfully']);
    }
}
