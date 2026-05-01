<?php

namespace Aanugerah\WeddingPro\Models;

use Aanugerah\WeddingPro\Enums\OrderPaymentStatus;
use Aanugerah\WeddingPro\Enums\OrderStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $package_id
 * @property string $order_number
 * @property float $total_price
 * @property string $status
 * @property string $payment_status
 * @property \Illuminate\Support\Carbon $booking_date
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $event_date
 * @property-read Transaction|null $latestTransaction
 * @property-read Package $package
 * @property-read Collection<int, Transaction> $transactions
 * @property-read int|null $transactions_count
 * @property-read \Illuminate\Contracts\Auth\Authenticatable& \Illuminate\Database\Eloquent\Model $user
 * @property-read WeddingOrganizer|null $weddingOrganizer
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereBookingDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereOrderNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePaymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereTotalPrice($value)
 * @method static \Aanugerah\WeddingPro\Models\Order|null find(mixed $id, array|string $columns = ['*'])
 * @method static \Aanugerah\WeddingPro\Models\Order findOrFail(mixed $id, array|string $columns = ['*'])
 * @method static \Aanugerah\WeddingPro\Models\Order|null first(array|string $columns = ['*'])
 * @method static \Aanugerah\WeddingPro\Models\Order firstOrFail(array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Collection<int, \Aanugerah\WeddingPro\Models\Order> get(array|string $columns = ['*'])
 *
 * @property int $userId
 * @property int $packageId
 * @property string $orderNumber
 * @property numeric $totalPrice
 * @property string $paymentStatus
 * @property \Illuminate\Support\Carbon $bookingDate
 * @property \Illuminate\Support\Carbon|null $createdAt
 * @property \Illuminate\Support\Carbon|null $updatedAt
 * @property-read mixed $eventDate
 * @property-read int|null $transactionsCount
 * @property-read bool|null $transactionsExists
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\Aanugerah\WeddingPro\Models\Order whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\Aanugerah\WeddingPro\Models\Order whereUserId($value)
 *
 * @mixin \Eloquent
 */
class Order extends Model
{
    protected $fillable = [
        'user_id',
        'package_id',
        'product_id',
        'order_number',
        'total_price',
        'status',
        'payment_status',
        'booking_date',
        'notes',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'total_price' => 'decimal:2',
        'status' => OrderStatus::class,
        'payment_status' => OrderPaymentStatus::class,
    ];

    protected $appends = ['event_date'];

    public function getEventDateAttribute()
    {
        return Carbon::parse($this->booking_date)->format('Y-m-d');
    }

    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function weddingOrganizer()
    {
        if ($this->package_id) {
            return $this->hasOneThrough(
                WeddingOrganizer::class,
                Package::class,
                'id',
                'id',
                'package_id',
                'wedding_organizer_id'
            );
        }

        return $this->hasOneThrough(
            WeddingOrganizer::class,
            Product::class,
            'id',
            'id',
            'product_id',
            'wedding_organizer_id'
        );
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function latestTransaction()
    {
        return $this->hasOne(Transaction::class)->latestOfMany();
    }
}
