<?php

namespace App\Http\Controllers;

use App\Models\ApprovalHistory;
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

        return response()->json($pengajuan->load(['user', 'jenjang', 'approvalHistory']));
    }

    public function verifyDocuments(Request $request, string $id)
    {
        $request->validate([
            'dokumen_id' => 'required|exists:dokumen_pengajuan,id',
            'status' => 'required|in:lengkap,tidak_lengkap',
            'catatan' => 'nullable|string',
        ]);

        $pengajuan = Pengajuan::findOrFail($id);

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

        return response()->json($dokumen);
    }
}
