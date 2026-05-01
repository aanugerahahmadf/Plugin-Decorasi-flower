<?php

namespace Aanugerah\WeddingPro\Observers;

use Aanugerah\WeddingPro\Models\History;
use Aanugerah\WeddingPro\Models\Transaction;
use Aanugerah\WeddingPro\Helpers\NativeNotificationHelper;
use Filament\Notifications\Notification;

class TransactionObserver
{
    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction): void
    {
        $this->logToHistory($transaction);
    }

    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        $this->logToHistory($transaction);

        // Check if status changed to success to handle balance and notifications
        if ($transaction->status === 'success' && $transaction->getOriginal('status') !== 'success') {
            if ($transaction->type === 'topup') {
                $user = $transaction->user;
                if ($user) {
                    $user->increment('balance', $transaction->amount);

                    try {
                        Notification::make()
                            ->title(__('Topup Berhasil'))
                            ->body(__('Saldo sebesar Rp ').number_format($transaction->amount, 0, ',', '.').__(' telah masuk ke akun Anda.'))
                            ->success()
                            ->icon('heroicon-o-banknotes')
                            ->sendToDatabase($user);

                        // 📱 Kirim juga ke Native Mobile Notification
                        NativeNotificationHelper::send(
                            $user,
                            __('Topup Berhasil'),
                            __('Saldo sebesar Rp ').number_format($transaction->amount, 0, ',', '.').__(' telah masuk ke akun Anda.')
                        );
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('[TransactionObserver] Notification failed: '.$e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Log transaction to history table.
     */
    protected function logToHistory(Transaction $transaction): void
    {
        History::updateOrCreate(
            ['type' => $transaction->type, 'transaction_id' => $transaction->id],
            [
                'user_id' => $transaction->user_id,
                'reference_number' => $transaction->reference_number,
                'status' => $transaction->status,
                'amount' => $transaction->total_amount,
                'notes' => $transaction->notes,
                'info' => $transaction->payment_method ?? ucfirst($transaction->type),
                'created_at' => $transaction->created_at,
            ]
        );
    }
}
