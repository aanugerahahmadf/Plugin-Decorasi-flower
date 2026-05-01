<?php

namespace Aanugerah\WeddingPro\Http\Controllers\Api;

use Aanugerah\WeddingPro\Http\Controllers\Controller;
use Aanugerah\WeddingPro\Models\Inbox;
use Aanugerah\WeddingPro\Models\Message;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function getConversations(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => __('Tidak terautentikasi'),
            ], 401);
        }

        // Fetch inboxes where the user is a participant
        $inboxes = Inbox::whereJsonContains('user_ids', $user->id, 'and', false)
            ->with(['messages' => function ($query): void {
                $query->latest()->limit(1);
            }])
            ->get(['*']);

        $adminUser = (config('auth.providers.users.model'))::whereHas('roles', function ($q) {
            $q->where('name', 'super_admin');
        })->first(['*']);
        $adminId = $adminUser?->id ?? 1;
        $isAdmin = $user->hasRole('super_admin');
        $data = $inboxes->map(function (Inbox $inbox) use ($user, $adminId, $isAdmin) {
            $lastMessage = $inbox->messages->first();
            $userIds = $inbox->user_ids ?? [];
            $otherId = collect($userIds)->first(fn ($id) => (int) $id !== (int) $user->id);
            $otherUser = $otherId ? (config('auth.providers.users.model'))::find($otherId, ['*']) : null;

            $title = $isAdmin
                ? ($otherUser ? __('Chat dengan :name', ['name' => $otherUser->full_name ?: $otherUser->username ?: __('Pelanggan')]) : $inbox->title ?? __('Chat Bantuan'))
                : __('Chat dengan Admin');
            $otherPayload = [
                'id' => $otherUser?->id ?? $adminId,
                'name' => $otherUser ? ($otherUser->full_name ?: $otherUser->username ?? __('Pengguna')) : __('Admin'),
                'profile_photo' => $otherUser?->avatar_url ?? null,
            ];

            return [
                'id' => $inbox->id,
                'title' => $title,
                'other_user' => $otherPayload,
                'last_message' => $lastMessage ? [
                    'id' => $lastMessage->id,
                    'message' => $lastMessage->message,
                    'created_at' => $lastMessage->created_at->toIso8601String(),
                    'sender_id' => $lastMessage->user_id,
                ] : null,
                'unread_count' => 0,
                'created_at' => $inbox->created_at->toIso8601String(),
                'updated_at' => $inbox->updated_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'current_user_is_super_admin' => $isAdmin,
        ]);
    }

    public function getMessages($inboxId)
    {
        $user = Auth::user();
        $inbox = Inbox::findOrFail($inboxId, ['*']);
        $userIds = $inbox->user_ids ?? [];
        if (! in_array((int) $user->id, array_map('intval', $userIds))) {
            return response()->json(['success' => false, 'message' => __('Tidak terautentikasi')], 403);
        }

        $messages = Message::where('inbox_id', $inbox->id)
            ->with('sender')
            ->oldest()
            ->get(['*']);

        $data = $messages->map(function (Message $message) use ($user) {
            $sender = $message->sender;

            return [
                'id' => $message->id,
                'message' => $message->message,
                'sender_id' => $message->user_id,
                'sender_name' => $sender?->full_name ?? $sender?->username ?? __('Tidak dikenal'),
                'is_me' => $message->user_id === $user->id,
                'read_by' => [],
                'attachments' => [],
                'created_at' => $message->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'inbox_id' => 'required',
            'message' => 'required|string',
        ]);

        $user = Auth::user();
        $inbox = Inbox::find($request->inbox_id, ['*']);
        if (! $inbox) {
            return response()->json(['success' => false, 'message' => __('Kotak pesan tidak ditemukan')], 404);
        }
        $userIds = $inbox->user_ids ?? [];
        if (! in_array((int) $user->id, array_map('intval', $userIds))) {
            return response()->json(['success' => false, 'message' => __('Tidak terautentikasi')], 403);
        }

        $message = Message::create([
            'inbox_id' => $request->inbox_id,
            'user_id' => $user->id,
            'message' => $request->message,
        ]);

        return response()->json([
            'success' => true,
            'data' => $message,
        ], 201);
    }

    public function startConversation(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $adminUser = (config('auth.providers.users.model'))::whereHas('roles', function ($q) {
            $q->where('name', 'super_admin');
        })->first(['*']);
        $adminId = $adminUser ? (int) $adminUser->id : 1;
        $isAdmin = $user->hasRole('super_admin');

        if ($isAdmin) {
            // Superadmin chat dengan user/customer — wajib kirim with_user_id
            $withUserId = $request->input('with_user_id');
            if (! $withUserId) {
                return response()->json([
                    'success' => false,
                    'message' => __('Pilih customer untuk memulai chat.'),
                ], 400);
            }
            $withUserId = (int) $withUserId;
            if ($withUserId === (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => __('Tidak bisa chat dengan diri sendiri.'),
                ], 400);
            }
            $inbox = Inbox::whereJsonContains('user_ids', (int) $user->id, 'and', false)
                ->whereJsonContains('user_ids', $withUserId, 'and', false)
                ->first(['*']);
            if (! $inbox) {
                $inbox = Inbox::create([
                    'user_ids' => [(int) $user->id, $withUserId],
                    'title' => __('Chat Bantuan'),
                ]);
            }
        } else {
            // Customer chat dengan admin/superadmin
            $inbox = Inbox::whereJsonContains('user_ids', (int) $user->id, 'and', false)
                ->whereJsonContains('user_ids', $adminId, 'and', false)
                ->first(['*']);
            if (! $inbox) {
                $inbox = Inbox::create([
                    'user_ids' => [(int) $user->id, $adminId],
                    'title' => __('Chat Bantuan'),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $inbox->id,
            ],
        ], 201);
    }

    /**
     * Daftar customer untuk superadmin (pilih siapa yang mau diajak chat).
     */
    public function getCustomersForChat(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => __('Tidak terautentikasi')], 403);
        }
        $customers = (config('auth.providers.users.model'))::whereHas('roles', function ($q) {
            $q->where('name', 'customer');
        })
            ->where('id', '!=', $user->id)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'username', 'email']);

        return response()->json([
            'success' => true,
            'data' => $customers->map(fn ($u) => [
                'id' => $u->id,
                'full_name' => $u->full_name,
                'username' => $u->username,
                'email' => $u->email,
            ]),
        ]);
    }

    public function getUnreadCount()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => 0,
            ],
        ]);
    }
}
