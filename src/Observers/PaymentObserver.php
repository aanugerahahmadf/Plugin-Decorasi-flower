<?php

namespace Aanugerah\WeddingPro\Observers;

use Aanugerah\WeddingPro\Enums\OrderStatus;
use Aanugerah\WeddingPro\Enums\PaymentStatus;
use Aanugerah\WeddingPro\Models\History;
use Aanugerah\WeddingPro\Models\Payment;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        History::create([
            'user_id' => $payment->order->user_id,
            'type' => 'payment',
            'transaction_id' => $payment->id,
            'reference_number' => $payment->payment_number,
            'amount' => $payment->total_amount,
            'info' => $payment->order->package?->name ?? __('Pembayaran Pesanan'),
            'status' => $payment->status instanceof \BackedEnum ? $payment->status->value : $payment->status,
            'notes' => $payment->notes,
            'created_at' => $payment->created_at,
        ]);

        // 🎯 OTOMATISASI STATUS PESANAN (CREATED)
        if ($payment->status === PaymentStatus::SUCCESS || $payment->status === 'success') {
            $payment->order->update([
                'payment_status' => 'paid',
                'status' => OrderStatus::CONFIRMED,
            ]);
        }
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        // 🔄 Sync ke tabel History
        History::updateOrCreate(
            ['type' => 'payment', 'transaction_id' => $payment->id],
            [
                'user_id' => $payment->order->user_id,
                'reference_number' => $payment->payment_number,
                'status' => $payment->status instanceof \BackedEnum ? $payment->status->value : $payment->status,
                'amount' => $payment->total_amount,
                'notes' => $payment->notes,
                'info' => $payment->order->package?->name ?? __('Pembayaran Pesanan'),
            ]
        );

        // 🎯 OTOMATISASI STATUS PESANAN
        // Jika status Pembayaran berubah menjadi SUCCESS, maka:
        // 1. Order payment_status = paid
        // 2. Order status = confirmed
        if ($payment->status === PaymentStatus::SUCCESS && $payment->getOriginal('status') !== PaymentStatus::SUCCESS) {
            $payment->order->update([
                'payment_status' => 'paid',
                'status' => OrderStatus::CONFIRMED,
            ]);
        }
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        // Bersihkan riwayat jika data dihapus (opsional, tapi bagus untuk integritas data)
        History::where('type', 'payment')
            ->where('transaction_id', $payment->id)
            ->delete();
    }

    /**
     * Handle the Payment "restored" event.
     */
    public function restored(Payment $payment): void
    {
        History::withTrashed()
            ->where('type', 'payment')
            ->where('transaction_id', $payment->id)
            ->restore();
    }

    /**
     * Handle the Payment "force deleted" event.
     */
    public function forceDeleted(Payment $payment): void
    {
        History::withTrashed()
            ->where('type', 'payment')
            ->where('transaction_id', $payment->id)
            ->forceDelete();
    }
}
