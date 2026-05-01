<?php

namespace Aanugerah\WeddingPro\Http\Controllers\Api;

use Aanugerah\WeddingPro\Http\Controllers\Controller;
use Aanugerah\WeddingPro\Models\Voucher;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => Voucher::where('is_active', true)
                ->where(function ($q): void {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })->get(['*']),
        ]);
    }

    public function validateVoucher(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'amount' => 'required|numeric',
        ]);

        $voucher = Voucher::where('code', $request->code)
            ->where('is_active', true)
            ->where(function ($q): void {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })->first(['*']);

        if (! $voucher) {
            return response()->json([
                'status' => 'error',
                'message' => __('Voucher tidak valid atau sudah kadaluarsa'),
            ], 404);
        }

        if ($request->amount < $voucher->min_purchase) {
            return response()->json([
                'status' => 'error',
                'message' => __('Minimum pembelian untuk voucher ini adalah Rp').' '.number_format($voucher->min_purchase, 0, ',', '.'),
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'data' => $voucher,
        ]);
    }
}
