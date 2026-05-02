<?php

namespace Aanugerah\WeddingPro\Models;

use Aanugerah\WeddingPro\Traits\BelongsToBrand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property int $author_id
 * @property string $title
 * @property string $slug
 * @property string $content
 * @property string|null $image_url
 * @property string|null $video_url
 * @property bool $is_published
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $author
 * @property-read string|null $media_video_url
 * @property-read MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereAuthorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereImageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereIsPublished($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereTitle($value)
 * @method static \Aanugerah\WeddingPro\Models\Article|null find(mixed $id, array|string $columns = ['*'])
 * @method static \Aanugerah\WeddingPro\Models\Article findOrFail(mixed $id, array|string $columns = ['*'])
 * @method static \Aanugerah\WeddingPro\Models\Article|null first(array|string $columns = ['*'])
 * @method static \Aanugerah\WeddingPro\Models\Article firstOrFail(array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Collection<int, \Aanugerah\WeddingPro\Models\Article> get(array|string $columns = ['*'])
 *
 * @property int $authorId
 * @property string|null $imageUrl
 * @property string|null $videoUrl
 * @property bool $isPublished
 * @property Carbon|null $publishedAt
 * @property Carbon|null $createdAt
 * @property Carbon|null $updatedAt
 * @property-read string|null $mediaVideoUrl
 * @property-read int|null $mediaCount
 * @property-read bool|null $mediaExists
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\Aanugerah\WeddingPro\Models\Article whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\Aanugerah\WeddingPro\Models\Article whereVideoUrl($value)
 *
 * @mixin \Eloquent
 */
class Article extends Model implements HasMedia
{
    use BelongsToBrand;
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')->singleFile();
        $this->addMediaCollection('videos')->singleFile();
    }

    protected $fillable = [
        'author_id',
        'category_id',
        'wedding_organizer_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'image_url',
        'video_url',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected $appends = [];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function weddingOrganizer()
    {
        return $this->belongsTo(WeddingOrganizer::class);
    }

    public function getMediaVideoUrlAttribute(): ?string
    {
        $url = $this->getFirstMediaUrl('videos') ?: $this->video_url;
        return $url ? \Aanugerah\WeddingPro\NativeServiceProvider::normalizeUrl($url) : null;
    }

    public function getImageUrlAttribute(): ?string
    {
        $url = $this->getFirstMediaUrl('images') ?: ($this->attributes['image_url'] ?? null);

        if (! $url) {
            return 'https://ui-avatars.com/api/?name='.urlencode($this->title);
        }

        if (str_starts_with($url, 'http')) {
            return \Aanugerah\WeddingPro\NativeServiceProvider::normalizeUrl($url);
        }

        $resolved = Storage::disk('public')->url(ltrim($url, '/'));
        return \Aanugerah\WeddingPro\NativeServiceProvider::normalizeUrl($resolved);
    }

    public function packages()
    {
        return $this->hasMany(Package::class);
    }
}
