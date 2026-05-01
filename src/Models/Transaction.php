<?php

namespace Aanugerah\WeddingPro\Models;

use Aanugerah\WeddingPro\Enums\OrderPaymentStatus;
use Aanugerah\WeddingPro\Enums\OrderStatus;
use Aanugerah\WeddingPro\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'type', // 'topup' or 'order'
        'reference_number',
        'amount',
        'admin_fee',
        'total_amount',
        'payment_gateway',
        'payment_method',
        'snap_token',
        'payment_url',
        'status', // 'pending', 'success', 'failed', 'expired', 'cancelled'
        'paid_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'metadata' => 'json',
        'status' => PaymentStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function markAsSuccess(): void
    {
        $this->update([
            'status' => 'success',
            'paid_at' => now(),
        ]);

        if ($this->type === 'topup') {
            $this->user->increment('balance', $this->amount);
        } elseif ($this->type === 'order' && $this->order) {
            $this->order->update([
                'status' => OrderStatus::CONFIRMED,
                'payment_status' => OrderPaymentStatus::PAID,
            ]);

            // Mark voucher as used if exists — gunakan Eloquent bukan DB::table()
            try {
                $voucherLink = \Illuminate\Support\Facades\DB::table('user_vouchers')
                    ->where('order_id', $this->order_id)
                    ->where('user_id', $this->user_id)
                    ->first();

                if ($voucherLink && $voucherLink->voucher_id) {
                    $voucher = Voucher::find($voucherLink->voucher_id);
                    if ($voucher) {
                        $voucher->markAsUsedBy($this->user_id, $this->order_id);
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[Transaction] Voucher mark failed: '.$e->getMessage());
            }
        }
    }

    public function markAsFailed(?string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'notes' => $reason ?? $this->notes,
        ]);

        if ($this->type === 'order' && $this->order) {
            $this->order->update([
                'payment_status' => OrderPaymentStatus::FAILED,
            ]);
        }
    }
}
