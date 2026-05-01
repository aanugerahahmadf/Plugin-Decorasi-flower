<?php

namespace Aanugerah\WeddingPro\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $package_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Package $package
 * @property-read \Illuminate\Contracts\Auth\Authenticatable& \Illuminate\Database\Eloquent\Model $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wishlist newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wishlist newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wishlist query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wishlist whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wishlist whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wishlist wherePackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wishlist whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wishlist whereUserId($value)
 *
 * @property int $id
 * @property int $user_id
 * @property int $package_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Package $package
 * @property-read \Illuminate\Contracts\Auth\Authenticatable& \Illuminate\Database\Eloquent\Model $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wishlist newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wishlist newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wishlist query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wishlist whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wishlist whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wishlist wherePackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wishlist whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Wishlist whereUserId($value)
 *
 * @property int $userId
 * @property int $packageId
 * @property Carbon|null $createdAt
 * @property Carbon|null $updatedAt
 *
 * @mixin \Eloquent
 */
class Wishlist extends Model
{
    /** @var Builder */
    protected $fillable = [
        'user_id',
        'package_id',
        'product_id',
    ];

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
}
