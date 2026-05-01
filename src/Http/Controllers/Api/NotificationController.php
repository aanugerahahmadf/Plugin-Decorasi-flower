<?php

namespace Aanugerah\WeddingPro\Http\Controllers\Api;

use Aanugerah\WeddingPro\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get user notifications
     */
    public function index(Request $request)
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            $notifications = $user->notifications()
                ->paginate($request->get('per_page', 20));

            return response()->json([
                'status' => 'success',
                'data' => $notifications->products(),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengambil notifikasi'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        try {
            /** @var User $user */
            $user = Auth::user();
            $notification = $user->notifications()->findOrFail($id);
            $notification->markAsRead();

            return response()->json([
                'status' => 'success',
                'message' => __('Notifikasi ditandai sebagai sudah dibaca'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal menandai notifikasi sebagai sudah dibaca'),
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        try {
            /** @var User $user */
            $user = Auth::user();
            $user->unreadNotifications->markAsRead();

            return response()->json([
                'status' => 'success',
                'message' => __('Semua notifikasi ditandai sebagai sudah dibaca'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal menandai semua notifikasi sebagai sudah dibaca'),
            ], 500);
        }
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount()
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'count' => $user->unreadNotifications()->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengambil jumlah notifikasi belum dibaca'),
            ], 500);
        }
    }
}
