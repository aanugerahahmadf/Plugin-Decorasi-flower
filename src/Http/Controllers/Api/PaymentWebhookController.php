<?php

namespace Aanugerah\WeddingPro\Http\Controllers\Api;

use Aanugerah\WeddingPro\Http\Controllers\Controller;
use Aanugerah\WeddingPro\Models\Transaction;
use Aanugerah\WeddingPro\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Midtrans\Notification;

class PaymentWebhookController extends Controller
{
    /**
     * Handle Midtrans payment notification webhook.
     */
    public function handleMidtransNotification(Request $request)
    {
        try {
            // This will automatically handle signature verification if the SDK is configured
            $midtrans = new MidtransService;
            $notification = new Notification;

            $status = $notification->transaction_status;
            $fraudStatus = $notification->fraud_status;
            $orderId = $notification->order_id;

            Log::info('[Midtrans] Webhook received via SDK', [
                'order_id' => $orderId,
                'status' => $status,
                'fraud' => $fraudStatus,
            ]);

            // Look up the transaction using the exact reference_number sent to Midtrans as order_id
            $transaction = Transaction::where('reference_number', $orderId)->first();

            if (! $transaction) {
                Log::warning('[Midtrans] Transaction not found in database', ['order_id' => $orderId]);

                return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
            }

            // Save raw notification for audit
            $transaction->update([
                'notes' => json_encode($notification->getResponse()),
            ]);

            // 3. Process status based on Midtrans logic
            if ($status === 'capture' && $fraudStatus === 'challenge') {
                $transaction->update(['status' => 'pending', 'notes' => 'Midtrans: Payment is challenged by FDS']);
            } elseif ($status === 'settlement' || ($status === 'capture' && $fraudStatus === 'accept')) {
                if ($transaction->status !== 'success') {
                    $transaction->markAsSuccess();
                }
            } elseif (in_array($status, ['deny', 'expire', 'cancel'])) {
                $transaction->markAsFailed('Midtrans: '.$status);
            } else {
                $transaction->update(['status' => 'pending']);
            }

            return response()->json(['success' => true, 'message' => 'Webhook processed']);

        } catch (\Exception $e) {
            Log::error('[Midtrans] Webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle Snap-BI standard notification.
     */
    public function handleSnapBiNotification(Request $request)
    {
        return response()->json(['message' => 'Snap-BI is currently disabled'], 501);
        /*
        try {
            // ... (kode lama yang di-comment)
        } catch (\Exception $e) {
            // ...
        }
        */
    }
}
