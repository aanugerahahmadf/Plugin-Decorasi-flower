<?php

namespace Aanugerah\WeddingPro\Http\Controllers\Api;

use Aanugerah\WeddingPro\Enums\OrderPaymentStatus;
use Aanugerah\WeddingPro\Enums\OrderStatus;
use Aanugerah\WeddingPro\Http\Controllers\Controller;
use Aanugerah\WeddingPro\Models\Order;
use Aanugerah\WeddingPro\Models\Transaction;
use Aanugerah\WeddingPro\Services\MidtransService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    /**
     * Get available payment methods (Now managed seamlessly by Midtrans)
     */
    public function getPaymentMethods(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                [
                    'id' => 'midtrans',
                    'name' => 'Midtrans Payment Gateway',
                    'icon' => null,
                    'enabled' => true,
                    'type' => 'gateway',
                    'fee' => 0,
                    'instructions' => __('Pilih metode pembayaran (Virtual Account, E-Wallet, Kartu Kredit, dll) langsung via Midtrans.'),
                ],
                [
                    'id' => 'wallet',
                    'name' => 'Saldo Dompet',
                    'icon' => null,
                    'enabled' => true,
                    'type' => 'wallet',
                    'fee' => 0,
                    'instructions' => __('Pembayaran otomatis dipotong dari saldo dompet Anda.'),
                ],
            ],
        ]);
    }

    /**
     * Create a new payment for an order
     */
    public function createPayment(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'order_id' => 'required|exists:orders,id',
                'payment_method' => 'required|string',
                'amount' => 'required|numeric|min:1',
                'notes' => 'nullable|string|max:500',
            ]);

            $order = Order::where('id', $validatedData['order_id'])
                ->where('user_id', Auth::id())
                ->firstOrFail(['*']);

            if ($order->payment_status === OrderPaymentStatus::PAID) {
                return response()->json(['status' => 'error', 'message' => __('Pesanan sudah dibayar')], 400);
            }

            if ($order->status === OrderStatus::CANCELLED) {
                return response()->json(['status' => 'error', 'message' => __('Tidak dapat membayar untuk pesanan yang dibatalkan')], 400);
            }

            $tolerance = 1000;
            if (abs($validatedData['amount'] - $order->total_price) > $tolerance) {
                return response()->json(['status' => 'error', 'message' => __('Jumlah pembayaran tidak sesuai dengan total pesanan')], 400);
            }

            $existingTransaction = Transaction::where('order_id', $order->id)
                ->where('type', 'order')
                ->where('status', 'pending')
                ->first(['*']);

            if ($existingTransaction) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('Pembayaran sudah ada untuk pesanan ini'),
                    'transaction' => $existingTransaction,
                ], 409);
            }

            $amount = $validatedData['amount'];
            $reference = 'TRX-'.time().'-'.Str::random(4);

            // --- Cek wallet payment ---
            if ($validatedData['payment_method'] === 'wallet') {
                $user = Auth::user();
                if ($user->balance < $amount) {
                    return response()->json([
                        'status' => 'error',
                        'message' => __('Saldo tidak mencukupi. Silakan top up terlebih dahulu.'),
                    ], 400);
                }

                $transaction = Transaction::create([
                    'user_id' => Auth::id(),
                    'order_id' => $order->id,
                    'type' => 'order',
                    'reference_number' => $reference,
                    'amount' => $amount,
                    'admin_fee' => 0,
                    'total_amount' => $amount,
                    'status' => 'success',
                    'payment_gateway' => 'wallet',
                    'paid_at' => now(),
                    'notes' => $validatedData['notes'] ?? null,
                ]);

                $user->decrement('balance', $amount);
                $order->update(['payment_status' => OrderPaymentStatus::PAID, 'status' => OrderStatus::CONFIRMED]);

                return response()->json([
                    'status' => 'success',
                    'message' => __('Pembayaran saldo berhasil'),
                    'data' => ['transaction' => $transaction],
                ], 201);
            }

            // --- Midtrans Payment ---
            $transaction = Transaction::create([
                'user_id' => Auth::id(),
                'order_id' => $order->id,
                'type' => 'order',
                'reference_number' => $reference,
                'amount' => $amount,
                'admin_fee' => 0,
                'total_amount' => $amount,
                'status' => 'pending',
                'payment_gateway' => 'midtrans',
                'notes' => $validatedData['notes'] ?? null,
            ]);

            try {
                $midtrans = new MidtransService;
                $transaction = $midtrans->createTransactionSnap($transaction);
            } catch (\Exception $e) {
                Log::error('[Midtrans] Snap creation failed for api payment', [
                    'reference' => $transaction->reference_number,
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => __('Pembayaran berhasil dibuat'),
                'data' => [
                    'transaction' => $transaction->fresh(),
                    'snap_token' => $transaction->snap_token,
                    'payment_url' => $transaction->payment_url,
                ],
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['status' => 'error', 'message' => __('Pesanan tidak ditemukan atau bukan milik Anda')], 404);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => __('Gagal membuat pesanan'), 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get user's payment / transaction history
     */
    public function getUserPayments(Request $request)
    {
        try {
            $query = Transaction::where('user_id', Auth::id());

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            $allowedSortFields = ['created_at', 'amount', 'status'];
            if (! in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }

            $query->orderBy($sortBy, $sortDirection === 'asc' ? 'asc' : 'desc');

            $transactions = $query->paginate($request->get('per_page', 10));

            return response()->json([
                'status' => 'success',
                'data' => $transactions->products(),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                    'has_more_pages' => $transactions->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengambil riwayat transaksi'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel a payment
     */
    public function cancelPayment($referenceNumber)
    {
        try {
            $transaction = Transaction::where('reference_number', $referenceNumber)
                ->where('user_id', Auth::id())
                ->firstOrFail(['*']);

            if ($transaction->status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => __('Transaksi tidak dapat dibatalkan karena status: ').$transaction->status,
                ], 400);
            }

            $transaction->markAsFailed('Dibatalkan oleh Pengguna');

            return response()->json([
                'status' => 'success',
                'message' => __('Transaksi berhasil dibatalkan'),
                'data' => $transaction->fresh(),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Transaksi tidak ditemukan atau bukan milik Anda'),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal membatalkan transaksi'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
