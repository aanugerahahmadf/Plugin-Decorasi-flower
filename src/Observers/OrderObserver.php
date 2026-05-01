<?php

namespace Aanugerah\WeddingPro\Observers;

use Aanugerah\WeddingPro\Enums\OrderPaymentStatus;
use Aanugerah\WeddingPro\Enums\OrderStatus;
use Aanugerah\WeddingPro\Models\History;
use Aanugerah\WeddingPro\Models\Order;
use Aanugerah\WeddingPro\Helpers\NativeNotificationHelper;
use Filament\Notifications\Notification;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        History::create([
            'user_id' => $order->user_id,
            'type' => 'order',
            'transaction_id' => $order->id,
            'reference_number' => $order->order_number,
            'amount' => $order->total_price,
            'info' => $order->package?->name ?? __('Pemesanan Paket'),
            'status' => $order->status instanceof \BackedEnum ? $order->status->value : $order->status,
            'notes' => $order->notes,
            'created_at' => $order->created_at,
        ]);
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Fitur Otomatis: Auto-Refund jika Order Dibatalkan tapi sudah Dibayar
        if ($order->isDirty('status') && $order->status === OrderStatus::CANCELLED) {
            if (in_array($order->payment_status, [OrderPaymentStatus::PAID, OrderPaymentStatus::PARTIAL])) {
                $user = $order->user;
                if ($user) {
                    $user->increment('balance', $order->total_price);

                    // Update status pembayaran jadi Refunded secara otomatis
                    $order->updateQuietly(['payment_status' => OrderPaymentStatus::REFUNDED]);

                    // Catat Log Refund Otomatis
                    History::create([
                        'user_id' => $order->user_id,
                        'type' => 'balance',
                        'transaction_id' => $order->id,
                        'reference_number' => 'REF-'.$order->order_number,
                        'amount' => $order->total_price,
                        'info' => __('Refund Otomatis (Pembatalan Order #').$order->order_number.')',
                        'status' => 'success',
                    ]);

                    // 🔔 Notify User: Refund
                    try {
                        Notification::make()
                            ->title(__('Refund Berhasil'))
                            ->body(__('Dana sebesar Rp ').number_format($order->total_price, 2, ',', '.').__(' telah dikembalikan ke saldo Anda karena pembatalan Order #').$order->order_number)
                            ->success()
                            ->sendToDatabase($user);

                        // 📱 Kirim juga ke Native Mobile Notification
                        NativeNotificationHelper::send(
                            $user,
                            __('Refund Berhasil'),
                            __('Dana sebesar Rp ').number_format($order->total_price, 0, ',', '.').__(' telah dikembalikan ke saldo Anda.')
                        );
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('[OrderObserver] Notification failed: '.$e->getMessage());
                    }
                }
            }
        }

        // 🔔 Notify User: Status Change (Hanya jika status berubah)
        if ($order->isDirty('status')) {
            $user = $order->user;
            if ($user) {
                $statusLabel = $order->status instanceof OrderStatus
                    ? $order->status->getLabel()
                    : (is_string($order->status) ? $order->status : __('Tidak Diketahui'));

                $statusIcon = $order->status instanceof OrderStatus
                    ? $order->status->getIcon()
                    : 'heroicon-o-information-circle';

                try {
                    Notification::make()
                        ->title(__('Update Pesanan #').$order->order_number)
                        ->body(__('Status pesanan Anda kini: ').$statusLabel)
                        ->info()
                        ->icon($statusIcon)
                        ->sendToDatabase($user);

                    // 📱 Kirim juga ke Native Mobile Notification
                    NativeNotificationHelper::send(
                        $user,
                        __('Update Pesanan #').$order->order_number,
                        __('Status pesanan Anda kini: ').$statusLabel
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[OrderObserver] Status notification failed: '.$e->getMessage());
                }
            }
        }

        History::updateOrCreate(
            ['type' => 'order', 'transaction_id' => $order->id],
            [
                'status' => $order->status instanceof \BackedEnum ? $order->status->value : (string) $order->status,
                'amount' => $order->total_price,
                'info' => $order->package?->name ?? __('Pemesanan Paket'),
                'notes' => $order->notes,
            ]
        );
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        History::where('type', 'order')
            ->where('transaction_id', $order->id)
            ->delete();
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        History::withTrashed()
            ->where('type', 'order')
            ->where('transaction_id', $order->id)
            ->restore();
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        History::withTrashed()
            ->where('type', 'order')
            ->where('transaction_id', $order->id)
            ->forceDelete();
    }
}
