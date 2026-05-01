<?php

namespace Aanugerah\WeddingPro\Models;

use Aanugerah\WeddingPro\Enums\DiscountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string|null $description
 * @property float $discount_amount
 * @property string $discount_type
 * @property float $min_purchase
 * @property Carbon|null $expires_at
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereDiscountAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereDiscountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereIsActive($value)
 * @method static \Aanugerah\WeddingPro\Models\Voucher|null find(mixed $id, array|string $columns = ['*'])
 * @method static \Aanugerah\WeddingPro\Models\Voucher findOrFail(mixed $id, array|string $columns = ['*'])
 * @method static \Aanugerah\WeddingPro\Models\Voucher|null first(array|string $columns = ['*'])
 * @method static \Aanugerah\WeddingPro\Models\Voucher firstOrFail(array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Collection<int, \Aanugerah\WeddingPro\Models\Voucher> get(array|string $columns = ['*'])
 *
 * @property numeric $discountAmount
 * @property string $discountType
 * @property numeric $minPurchase
 * @property Carbon|null $expiresAt
 * @property bool $isActive
 * @property Carbon|null $createdAt
 * @property Carbon|null $updatedAt
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\Aanugerah\WeddingPro\Models\Voucher whereMinPurchase($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\Aanugerah\WeddingPro\Models\Voucher whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Voucher extends Model
{
    protected $fillable = [
        'code',
        'description',
        'discount_amount',
        'discount_type',
        'min_purchase',
        'expires_at',
        'is_active',
        'is_global',
        'max_uses',
        'uses_count',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'is_global' => 'boolean',
        'discount_amount' => 'decimal:2',
        'min_purchase' => 'decimal:2',
        'discount_type' => DiscountType::class,
    ];

    // ─── Relations ────────────────────────────────────────────

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_vouchers')
            ->withPivot('claimed_at', 'used_at', 'order_id')
            ->withTimestamps();
    }

    // ─── Business Logic ───────────────────────────────────────

    /**
     * Hitung besaran diskon berdasarkan total harga.
     */
    public function calculateDiscount(float $totalPrice): float
    {
        if ($this->discount_type === DiscountType::PERCENTAGE) {
            return round($totalPrice * ($this->discount_amount / 100), 2);
        }

        return min((float) $this->discount_amount, $totalPrice);
    }

    /**
     * Cek apakah voucher valid untuk digunakan.
     */
    public function isValidFor(float $totalPrice): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }
        if ($totalPrice < (float) $this->min_purchase) {
            return false;
        }
        if ($this->max_uses && $this->uses_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Cek apakah user berhak pakai voucher ini.
     */
    public function isAccessibleBy(int $userId): bool
    {
        if ($this->is_global) {
            return true;
        }

        return $this->users()->where('users.id', $userId)->exists();
    }

    /**
     * Tandai voucher sebagai digunakan oleh user.
     */
    public function markAsUsedBy(int $userId, ?int $orderId = null): void
    {
        $this->users()->updateExistingPivot($userId, [
            'used_at' => now(),
            'order_id' => $orderId,
        ]);

        $this->increment('uses_count');
    }

    /**
     * Assign voucher ke user (claim).
     */
    public function assignToUser(int $userId): void
    {
        if (! $this->users()->where('users.id', $userId)->exists()) {
            $this->users()->attach($userId, ['claimed_at' => now()]);
        }
    }
}
