<?php

namespace Aanugerah\WeddingPro\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $icon
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Package> $packages
 * @property-read int|null $packages_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereName($value)
 * @method static \Aanugerah\WeddingPro\Models\Category|null find(mixed $id, array|string $columns = ['*'])
 * @method static \Aanugerah\WeddingPro\Models\Category findOrFail(mixed $id, array|string $columns = ['*'])
 * @method static \Aanugerah\WeddingPro\Models\Category|null first(array|string $columns = ['*'])
 * @method static \Aanugerah\WeddingPro\Models\Category firstOrFail(array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Collection<int, \Aanugerah\WeddingPro\Models\Category> get(array|string $columns = ['*'])
 *
 * @property Carbon|null $createdAt
 * @property Carbon|null $updatedAt
 * @property-read int|null $packagesCount
 * @property-read bool|null $packagesExists
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\Aanugerah\WeddingPro\Models\Category whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\Aanugerah\WeddingPro\Models\Category whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Category extends Model
{
    protected $fillable = ['name', 'slug', 'icon', 'color', 'description'];

    public function packages()
    {
        return $this->hasMany(Package::class);
    }
}
