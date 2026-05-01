<?php

namespace Aanugerah\WeddingPro\Services;

use Aanugerah\WeddingPro\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;
use Midtrans\Transaction as MidtransTransaction;

// use SnapBi\Config as SnapBiConfig;

class MidtransService
{
    public function __construct()
    {
        $serverKey = config('midtrans.server_key');

        if (empty($serverKey)) {
            Log::error('[Midtrans] Server key is missing');
            throw new \Exception('Midtrans Server Key is missing! Check your .env file.');
        }

        // Standard Snap Configuration
        MidtransConfig::$serverKey = $serverKey;
        MidtransConfig::$clientKey = config('midtrans.client_key');
        MidtransConfig::$isProduction = (bool) config('midtrans.is_production');
        MidtransConfig::$isSanitized = (bool) config('midtrans.is_sanitized', true);
        MidtransConfig::$is3ds = (bool) config('midtrans.is_3ds', true);

        /* 🚀 Snap-BI Configuration (Standard Nasional Open API)
        SnapBiConfig::$isProduction     = MidtransConfig::$isProduction;
        SnapBiConfig::$snapBiClientId   = config('midtrans.snap_bi.client_id');
        SnapBiConfig::$snapBiPrivateKey = config('midtrans.snap_bi.private_key');
        SnapBiConfig::$snapBiClientSecret = config('midtrans.snap_bi.client_secret');
        SnapBiConfig::$snapBiPartnerId  = config('midtrans.snap_bi.partner_id');
        SnapBiConfig::$snapBiChannelId  = config('midtrans.snap_bi.channel_id');
        SnapBiConfig::$snapBiPublicKey  = config('midtrans.snap_bi.public_key');
        SnapBiConfig::$enableLogging    = config('app.debug');
        */
    }

    /**
     * Format consistent Order ID for Midtrans: MID-{Timestamp}-{ID}
     * This follows the format seen in the user's reference screenshot.
     */
    public function getMidtransOrderId(Transaction $transaction): string
    {
        // Use the existing reference number so we can track and verify status later
        return $transaction->reference_number;
    }

    /**
     * Create Snap token for a Transaction with Advanced Features
     */
    public function createTransactionSnap(Transaction $transaction): Transaction
    {
        $user = $transaction->user;
        $orderId = $this->getMidtransOrderId($transaction);

        // Advanced: Better Product Naming for "Details" Dropdown
        $itemName = $transaction->type === 'topup'
            ? 'Wallet Deposit - #'.$transaction->reference_number
            : ($transaction->order?->package?->name ?? 'Wedding Package Order');

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $transaction->total_amount,
            ],
            'customer_details' => [
                'first_name' => $user->first_name ?? $user->name ?? 'Customer',
                'last_name' => $user->last_name ?? '',
                'email' => $user->email ?? '',
                'phone' => $user->phone ?? '',
                'billing_address' => [
                    'first_name' => $user->first_name ?? $user->name ?? 'Customer',
                    'last_name' => $user->last_name ?? '',
                    'email' => $user->email ?? '',
                    'phone' => $user->phone ?? '',
                    'address' => $user->address ?? 'Indonesia',
                    'city' => $user->city ?? '',
                    'country_code' => 'IDN',
                ],
            ],
            'item_details' => [
                [
                    'id' => 'PRODUCT-'.$transaction->id,
                    'price' => (int) $transaction->amount,
                    'quantity' => 1,
                    'name' => substr($itemName, 0, 50),
                ],
            ],
            // 🚀 Advanced Features from Link
            'credit_card' => [
                'secure' => true,
                'save_card' => false,
            ],
            'expiry' => [
                'start_time' => date('Y-m-d H:i:s O'),
                'unit' => 'hours',
                'duration' => 24,
            ],
            'callbacks' => [
                'finish' => url('/'),
            ],
        ];

        // Add admin fee as separate line product for transparent "Details"
        if ((int) $transaction->admin_fee > 0) {
            $params['item_details'][] = [
                'id' => 'FEE-ADMIN',
                'price' => (int) $transaction->admin_fee,
                'quantity' => 1,
                'name' => 'Service / Admin Fee',
            ];
        }

        try {
            $snapResponse = Snap::createTransaction($params);

            $transaction->update([
                'snap_token' => $snapResponse->token,
                'payment_url' => $snapResponse->redirect_url,
            ]);

            return $transaction;
        } catch (\Exception $e) {
            Log::error('[Midtrans] Snap Creation Failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Verify Midtrans notification signature
     */
    public function verifySignature(array $data): void
    {
        $serverKey = config('midtrans.server_key');
        $orderId = $data['order_id'] ?? '';
        $statusCode = $data['status_code'] ?? '';
        $grossAmount = $data['gross_amount'] ?? '';

        // Midtrans gross_amount in notification can be slightly different string format
        $grossAmount = number_format((float) $grossAmount, 2, '.', '');

        // However, SHA512 signature in Midtrans V2 usually expects exact string from payload
        // If number_format fails, we fallback to raw string
        $rawGross = (string) ($data['gross_amount'] ?? '');

        $expected = hash('sha512', $orderId.$statusCode.$rawGross.$serverKey);

        if (($data['signature_key'] ?? '') !== $expected) {
            // Try with formatted if raw fails (defensive)
            $expected2 = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);
            if (($data['signature_key'] ?? '') !== $expected2) {
                throw new \Exception('Invalid Midtrans signature');
            }
        }
    }

    /**
     * Check if status is successful
     */
    public function isSuccess(array $data): bool
    {
        $status = $data['transaction_status'] ?? '';
        $fraudStatus = $data['fraud_status'] ?? 'accept';

        return ($status === 'settlement') ||
               ($status === 'capture' && $fraudStatus === 'accept');
    }

    /**
     * Check if status is failed
     */
    public function isFailed(array $data): bool
    {
        return in_array($data['transaction_status'] ?? '', ['deny', 'expire', 'cancel'], true);
    }

    /**
     * Get transaction status from API
     */
    public function getStatus(string $orderId)
    {
        try {
            return MidtransTransaction::status($orderId);
        } catch (\Exception $e) {
            Log::error("[Midtrans] Status check failed for $orderId: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Cancel transaction
     */
    public function cancel(string $orderId)
    {
        return MidtransTransaction::cancel($orderId);
    }

    /**
     * Approve challenge transaction
     */
    public function approve(string $orderId)
    {
        return MidtransTransaction::approve($orderId);
    }
}
