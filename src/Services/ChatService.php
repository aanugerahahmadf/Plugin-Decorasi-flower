<?php

namespace Aanugerah\WeddingPro\Services;

use Aanugerah\WeddingPro\Filament\Resources\OrderResource;
use Aanugerah\WeddingPro\Jobs\SendBotReply;
use Aanugerah\WeddingPro\Models\Inbox;
use Aanugerah\WeddingPro\Models\Message;
use Aanugerah\WeddingPro\Models\Order;

use Illuminate\Support\Facades\Auth;

class ChatService
{
    /**
     * Get or create an inbox between a user and the first super admin.
     */
    public static function getOrCreateInboxWithAdmin(int $userId): Inbox
    {
        $admin = (config('auth.providers.users.model'))::whereHas('roles', function ($q) {
            $q->where('name', 'super_admin');
        })->first();

        if (! $admin) {
            throw new \Exception('Super Admin not found.');
        }

        $inbox = Inbox::query()
            ->whereJsonContains('user_ids', $userId, 'and', false)
            ->whereJsonContains('user_ids', $admin->id, 'and', false)
            ->first();

        if (! $inbox) {
            $inbox = Inbox::create([
                'user_ids' => [$userId, $admin->id],
            ]);
        }

        return $inbox;
    }

    /**
     * Send a context message (product/package card) to an inbox.
     */
    public static function sendContextMessage(Inbox $inbox, array $meta): Message
    {
        // Avoid sending duplicate context cards for the same product in a short time
        $lastMessage = $inbox->messages()->latest('id')->first();
        if ($lastMessage && isset($lastMessage->meta['id']) && $lastMessage->meta['id'] == $meta['id']) {
            return $lastMessage;
        }

        $message = Message::create([
            'inbox_id' => $inbox->id,
            'user_id' => Auth::id(),
            'message' => __('Saya menanyakan tentang :itemType ini: :name', [
                'itemType' => __($meta['type'] == 'product' ? 'Produk' : 'Paket'),
                'name' => $meta['name'] ?? '',
            ]),
            'meta' => $meta,
        ]);

        // Dispatch bot reply if user is not admin
        if (Auth::user() && ! Auth::user()->hasRole('super_admin')) {
            SendBotReply::dispatch($message->id)->delay(now()->addSeconds(5));
        }

        return $message;
    }

    /**
     * Send an order confirmation message (order card) to an inbox.
     */
    public static function sendOrderMessage(Inbox $inbox, Order $order): Message
    {
        $type = $order->package_id ? 'package' : 'product';
        $item = $order->package ?? $order->product;

        $message = Message::create([
            'inbox_id' => $inbox->id,
            'user_id' => $order->user_id,
            'message' => __('Halo Admin, saya baru saja membuat pesanan baru dengan nomor: :orderNumber', [
                'orderNumber' => $order->order_number,
            ]),
            'meta' => [
                'type' => $type,
                'id' => $item->id,
                'name' => $item->name,
                'price' => $order->total_price,
                'image' => $item->image_url,
                'url' => OrderResource::getUrl('index').'?tableFilters[id][value]='.$order->id,
                'is_order' => true,
                'order_number' => $order->order_number,
                'order_status' => $order->status,
            ],
        ]);

        // Dispatch bot reply for new order
        if ($order->user && ! $order->user->hasRole('super_admin')) {
            SendBotReply::dispatch($message->id)->delay(now()->addSeconds(5));
        }

        return $message;
    }
}
