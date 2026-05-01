<?php

namespace Aanugerah\WeddingPro\Http\Controllers\Api;

use Aanugerah\WeddingPro\Http\Controllers\Controller;
use Aanugerah\WeddingPro\Models\Transaction;
use Aanugerah\WeddingPro\Models\Withdrawal;
use Aanugerah\WeddingPro\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    public function getWalletData(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'balance' => $request->user()->balance,
            ],
        ]);
    }

    public function getHistory(Request $request)
    {
        $history = Transaction::where('user_id', $request->user()->id)
            ->where('type', 'topup')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $history,
        ]);
    }

    public function requestTopup(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000',
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();
            $amount = $request->amount;

            $referenceNumber = 'TRX-'.time().'-'.strtoupper(Str::random(5));

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'type' => 'topup',
                'reference_number' => $referenceNumber,
                'amount' => $amount,
                'admin_fee' => 0,
                'total_amount' => $amount,
                'status' => 'pending',
                'payment_gateway' => 'midtrans',
            ]);

            try {
                $midtrans = new MidtransService;
                $transaction = $midtrans->createTransactionSnap($transaction);
            } catch (\Exception $e) {
                Log::error('[Midtrans] Snap creation failed for api topup', [
                    'reference' => $transaction->reference_number,
                    'error' => $e->getMessage(),
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => __('Permintaan topup berhasil dibuat'),
                'data' => [
                    'transaction' => $transaction->fresh(),
                    'snap_token' => $transaction->snap_token,
                    'payment_url' => $transaction->payment_url,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function requestWithdrawal(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000',
            'bank_name' => 'required|string',
            'account_number' => 'required|string',
            'account_holder' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();
            $amount = $request->amount;

            if ($user->balance < $amount) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('Saldo Anda tidak mencukupi untuk penarikan ini'),
                ], 400);
            }

            $withdrawal = Withdrawal::create([
                'user_id' => $user->id,
                'reference_number' => 'WD-'.time().'-'.strtoupper(Str::random(5)),
                'amount' => $amount,
                'admin_fee' => 0, // Set fixed fee or calculate if necessary
                'total_amount' => $amount,
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'account_holder' => $request->account_holder,
                'status' => 'pending',
                'notes' => $request->notes,
            ]);

            // Deduct balance immediately
            $user->decrement('balance', $amount);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => __('Permintaan penarikan dana berhasil dibuat'),
                'data' => $withdrawal,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => __('Gagal membuat permintaan penarikan dana'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
