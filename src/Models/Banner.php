<?php

namespace Aanugerah\WeddingPro\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $title
 * @property string $image_url
 * @property string|null $link_url
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereImageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereLinkUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereSortOrder($value)
 * @method static \Aanugerah\WeddingPro\Models\Banner|null find(mixed $id, array|string $columns = ['*'])
 * @method static \Aanugerah\WeddingPro\Models\Banner findOrFail(mixed $id, array|string $columns = ['*'])
 * @method static \Aanugerah\WeddingPro\Models\Banner|null first(array|string $columns = ['*'])
 * @method static \Aanugerah\WeddingPro\Models\Banner firstOrFail(array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Collection<int, \Aanugerah\WeddingPro\Models\Banner> get(array|string $columns = ['*'])
 *
 * @property string $imageUrl
 * @property string|null $linkUrl
 * @property bool $isActive
 * @property int $sortOrder
 * @property Carbon|null $createdAt
 * @property Carbon|null $updatedAt
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\Aanugerah\WeddingPro\Models\Banner whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\Aanugerah\WeddingPro\Models\Banner whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Banner extends Model
{
    protected $fillable = [
        'title',
        'image_url',
        'link_url',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
