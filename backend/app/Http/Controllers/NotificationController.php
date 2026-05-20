<?php

namespace App\Http\Controllers;

use App\Models\Notification;
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
