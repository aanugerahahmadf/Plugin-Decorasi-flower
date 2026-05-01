<?php

namespace Aanugerah\WeddingPro\Observers;

use Aanugerah\WeddingPro\Enums\WithdrawalStatus;
use Aanugerah\WeddingPro\Models\History;
use Aanugerah\WeddingPro\Models\Withdrawal;
use Filament\Notifications\Notification;

class WithdrawalObserver
{
    /**
     * Handle the Withdrawal "created" event.
     */
    public function created(Withdrawal $withdrawal): void
    {
        History::create([
            'user_id' => $withdrawal->user_id,
            'type' => 'withdrawal',
            'transaction_id' => $withdrawal->id,
            'reference_number' => $withdrawal->reference_number,
            'amount' => $withdrawal->amount,
            'info' => $withdrawal->bank_name,
            'status' => $withdrawal->status instanceof \BackedEnum ? $withdrawal->status->value : $withdrawal->status,
            'notes' => $withdrawal->notes,
            'created_at' => $withdrawal->created_at,
        ]);

        // Otomatis potong saldo jika request dibuat (Hold)
        $withdrawal->user->decrement('balance', $withdrawal->amount);

        // Jika langsung dibuat dalam status DITOLAK, kembalikan saldo
        $isRefundStatus = in_array($withdrawal->status, [
            WithdrawalStatus::REJECTED,
            'rejected',
        ]);

        if ($isRefundStatus) {
            $user = $withdrawal->user;
            if ($user) {
                $user->increment('balance', $withdrawal->amount);
                Notification::make()
                    ->title(__('Penarikan Saldo Ditolak'))
                    ->body(__('Saldo sebesar Rp ').number_format($withdrawal->amount, 0, ',', '.').__(' telah dikembalikan ke akun Anda.'))
                    ->warning()
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->sendToDatabase($user);
            }
        }
    }

    /**
     * Handle the Withdrawal "updated" event.
     */
    public function updated(Withdrawal $withdrawal): void
    {
        History::updateOrCreate(
            ['type' => 'withdrawal', 'transaction_id' => $withdrawal->id],
            [
                'user_id' => $withdrawal->user_id,
                'reference_number' => $withdrawal->reference_number,
                'status' => $withdrawal->status instanceof \BackedEnum ? $withdrawal->status->value : $withdrawal->status,
                'amount' => $withdrawal->amount,
                'notes' => $withdrawal->notes,
                'info' => $withdrawal->bank_name,
            ]
        );

        // Jika DITOLAK atau DIBATALKAN, kembalikan saldo (Refund)
        $isRefundStatus = in_array($withdrawal->status, [
            WithdrawalStatus::REJECTED,
            WithdrawalStatus::CANCELLED ?? 'cancelled', // Fallback if enum doesn't have cancelled
        ]);

        $originalStatus = $withdrawal->getOriginal('status');
        $wasRefundStatus = in_array($originalStatus, [
            WithdrawalStatus::REJECTED,
            WithdrawalStatus::CANCELLED ?? 'cancelled',
        ]);

        if ($isRefundStatus && ! $wasRefundStatus) {
            $user = $withdrawal->user;
            if ($user) {
                $user->increment('balance', $withdrawal->amount);

                // 🔔 Notify User: Withdrawal Rejected/Cancelled
                Notification::make()
                    ->title(__('Penarikan Saldo Ditolak'))
                    ->body(__('Saldo sebesar Rp ').number_format($withdrawal->amount, 2, ',', '.').__(' telah dikembalikan ke akun Anda.'))
                    ->warning()
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->sendToDatabase($user);
            }
        }

        // Jika berubah dari REJECTED kembali ke PENDING (mungkin admin salah klik)??
        // Sebenarnya ini handle agar tidak double decrement/increment
    }

    /**
     * Handle the Withdrawal "deleted" event.
     */
    public function deleted(Withdrawal $withdrawal): void
    {
        History::where('type', 'withdrawal')
            ->where('transaction_id', $withdrawal->id)
            ->delete();

        // 🔄 OTOMATIS REFUND PADA SAAT DELETE
        // Jika data dihapus saat statusnya PENDING atau PROCESSING (belum dibayarkan),
        // maka kembalikan saldo ke user agar uang tidak "hilang" dari balance mereka.
        $needsRefund = in_array($withdrawal->status, [
            WithdrawalStatus::PENDING,
            WithdrawalStatus::PROCESSING,
        ]);

        if ($needsRefund) {
            $withdrawal->user->increment('balance', $withdrawal->amount);
        }
    }

    /**
     * Handle the Withdrawal "restored" event.
     */
    public function restored(Withdrawal $withdrawal): void
    {
        History::withTrashed()
            ->where('type', 'withdrawal')
            ->where('transaction_id', $withdrawal->id)
            ->restore();
    }

    /**
     * Handle the Withdrawal "force deleted" event.
     */
    public function forceDeleted(Withdrawal $withdrawal): void
    {
        History::withTrashed()
            ->where('type', 'withdrawal')
            ->where('transaction_id', $withdrawal->id)
            ->forceDelete();
    }
}
