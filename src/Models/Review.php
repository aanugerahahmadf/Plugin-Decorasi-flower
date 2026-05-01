<?php

namespace Aanugerah\WeddingPro\Models;

use Aanugerah\WeddingPro\Traits\BelongsToBrand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $wedding_organizer_id
 * @property int|null $package_id
 * @property int $rating
 * @property string|null $comment
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Package|null $package
 * @property-read \Illuminate\Contracts\Auth\Authenticatable& \Illuminate\Database\Eloquent\Model $user
 * @property-read WeddingOrganizer $weddingOrganizer
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review wherePackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereUserId($value)
 * @method static \Aanugerah\WeddingPro\Models\Review|null find(mixed $id, array|string $columns = ['*'])
 * @method static \Aanugerah\WeddingPro\Models\Review findOrFail(mixed $id, array|string $columns = ['*'])
 * @method static \Aanugerah\WeddingPro\Models\Review|null first(array|string $columns = ['*'])
 * @method static \Aanugerah\WeddingPro\Models\Review firstOrFail(array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Collection<int, \Aanugerah\WeddingPro\Models\Review> get(array|string $columns = ['*'])
 *
 * @property int $userId
 * @property int $weddingOrganizerId
 * @property int|null $packageId
 * @property Carbon|null $createdAt
 * @property Carbon|null $updatedAt
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\Aanugerah\WeddingPro\Models\Review whereWeddingOrganizerId($value)
 *
 * @mixin \Eloquent
 */
class Review extends Model
{
    use BelongsToBrand;

    protected $fillable = [
        'user_id',
        'wedding_organizer_id',
        'package_id',
        'rating',
        'comment',
    ];

    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
