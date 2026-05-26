<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\ApprovalHistory;
use App\Models\DokumenPengajuan;
use App\Models\Pengajuan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = Notification::query()
            ->forUser($user->id)
            ->with(['pengajuan:id,nomor_pengajuan'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $unreadCount = Notification::query()
            ->forUser($user->id)
            ->unread()
            ->count();

        return response()->json([
            'data' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Get all messages including notifications, approval history, and document notes
     */
    public function allMessages(Request $request): JsonResponse
    {
        $user = $request->user();
        $pengajuanIds = Pengajuan::where('user_id', $user->id)->pluck('id');

        // Get notifications
        $notifications = Notification::query()
            ->forUser($user->id)
            ->with(['pengajuan:id,nomor_pengajuan'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => 'notif_' . $item->id,
                    'type' => 'notification',
                    'data' => $item,
                    'pengajuan_id' => $item->pengajuan_id,
                    'title' => $item->title,
                    'message' => $item->message,
                    'notif_type' => $item->type,
                    'is_read' => $item->is_read,
                    'created_at' => $item->created_at,
                    'pengajuan' => $item->pengajuan,
                ];
            });

        // Get approval history with catatan
        $approvalHistories = ApprovalHistory::query()
            ->whereIn('pengajuan_id', $pengajuanIds)
            ->whereNotNull('catatan')
            ->where('catatan', '!=', '')
            ->with(['pengajuan:id,nomor_pengajuan', 'approver:id,name,jabatan'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                $roleLabel = match($item->role_approval) {
                    'atasan' => 'Atasan',
                    'admin_bkpsdm' => 'Admin BKPSDM',
                    'kepala_bkpsdm' => 'Kepala BKPSDM',
                    default => $item->role_approval,
                };

                $statusLabel = match($item->status) {
                    'setuju' => 'Disetujui',
                    'tolak' => 'Ditolak',
                    default => $item->status,
                };

                $type = $item->status === 'tolak' ? 'error' : 'info';

                return [
                    'id' => 'approval_' . $item->id,
                    'type' => 'approval',
                    'data' => $item,
                    'pengajuan_id' => $item->pengajuan_id,
                    'title' => "Catatan {$roleLabel}",
                    'message' => "{$statusLabel}: {$item->catatan}",
                    'notif_type' => $type,
                    'is_read' => true,
                    'created_at' => $item->created_at,
                    'pengajuan' => $item->pengajuan,
                    'approver' => $item->approver,
                ];
            });

        // Get document verification notes (catatan dokumen)
        $documentNotes = DokumenPengajuan::query()
            ->whereIn('pengajuan_id', $pengajuanIds)
            ->whereNotNull('catatan')
            ->where('catatan', '!=', '')
            ->whereNotNull('verified_at')
            ->with(['pengajuan:id,nomor_pengajuan', 'verifiedBy:id,name,jabatan'])
            ->orderBy('verified_at', 'desc')
            ->get()
            ->map(function ($item) {
                $statusLabel = match($item->status_verifikasi) {
                    'lengkap' => 'Lengkap',
                    'tidak_lengkap' => 'Tidak Lengkap',
                    default => 'Diperiksa',
                };

                $type = $item->status_verifikasi === 'tidak_lengkap' ? 'warning' : 'success';

                return [
                    'id' => 'document_' . $item->id,
                    'type' => 'document',
                    'data' => $item,
                    'pengajuan_id' => $item->pengajuan_id,
                    'title' => "Catatan Verifikasi: {$item->jenis_dokumen_label}",
                    'message' => "Dokumen {$statusLabel}. Catatan: {$item->catatan}",
                    'notif_type' => $type,
                    'is_read' => true,
                    'created_at' => $item->verified_at,
                    'pengajuan' => $item->pengajuan,
                    'approver' => $item->verifiedBy,
                ];
            });

        // Merge and sort by created_at
        $allMessages = $notifications->concat($approvalHistories)->concat($documentNotes)
            ->sortByDesc('created_at')
            ->values()
            ->take(100);

        $unreadCount = Notification::query()
            ->forUser($user->id)
            ->unread()
            ->count();

        return response()->json([
            'data' => $allMessages,
            'unread_count' => $unreadCount,
        ]);
    }

    public function unread(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = Notification::query()
            ->forUser($user->id)
            ->unread()
            ->with(['pengajuan:id,nomor_pengajuan'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json($notifications);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = Notification::query()
            ->forUser($request->user()->id)
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json($notification);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $updated = Notification::query()
            ->forUser($request->user()->id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'message' => 'All notifications marked as read',
            'count' => $updated,
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = Notification::query()
            ->forUser($request->user()->id)
            ->findOrFail($id);

        $notification->delete();

        return response()->json(['message' => 'Notification deleted']);
    }

    public function getUnreadCount(Request $request): JsonResponse
    {
        $count = Notification::query()
            ->forUser($request->user()->id)
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }
}
