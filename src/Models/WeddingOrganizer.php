<?php

namespace Aanugerah\WeddingPro\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $address
 * @property string|null $latitude
 * @property string|null $longitude
 * @property float $rating
 * @property int $is_verified
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read mixed $business_name
 * @property-read mixed $city
 * @property-read mixed $cover_image_url
 * @property-read mixed $email
 * @property-read mixed $logo_url
 * @property-read mixed $phone
 * @property-read mixed $total_reviews
 * @property-read string|null $video_url
 * @property-read MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 * @property-read Collection<int, Package> $packages
 * @property-read int|null $packages_count
 * @property-read Collection<int, Review> $reviews
 * @property-read int|null $reviews_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer whereIsVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WeddingOrganizer whereRating($value)
 * @method static \Aanugerah\WeddingPro\Models\WeddingOrganizer|null find(mixed $id, array $columns = ['*'])
 * @method static \Aanugerah\WeddingPro\Models\WeddingOrganizer findOrFail(mixed $id, array $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Collection<int, \Aanugerah\WeddingPro\Models\WeddingOrganizer> get(array $columns = ['*'])
 *
 * @property int $isVerified
 * @property Carbon|null $createdAt
 * @property Carbon|null $updatedAt
 * @property-read mixed $businessName
 * @property-read mixed $coverImageUrl
 * @property-read mixed $logoUrl
 * @property-read mixed $totalReviews
 * @property-read string|null $videoUrl
 * @property-read int|null $mediaCount
 * @property-read bool|null $mediaExists
 * @property-read int|null $packagesCount
 * @property-read bool|null $packagesExists
 * @property-read int|null $reviewsCount
 * @property-read bool|null $reviewsExists
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\Aanugerah\WeddingPro\Models\WeddingOrganizer whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\Aanugerah\WeddingPro\Models\WeddingOrganizer whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class WeddingOrganizer extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
        $this->addMediaCollection('gallery');
        $this->addMediaCollection('videos');
    }

    /**
     * Boot the model to enforce single-vendor logic.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (self::query()->count() >= 1) {
                throw new \Exception(__('Aplikasi ini eksklusif untuk satu perusahan (Dekorasi Bunga Pernikahan). Tidak dizinkan membuat profil baru.'));
            }
        });

        // Auto-geocode on saving ONLY if coordinates are missing or zero
        static::saving(function ($model) {
            $isMissingCoords = empty($model->latitude) || empty($model->longitude) || ($model->latitude == 0 && $model->longitude == 0);

            if ($model->isDirty('address') && $model->address && $isMissingCoords) {
                try {
                    $response = Http::timeout(5)  // Timeout 5 detik agar tidak hang di mobile
                        ->retry(1, 200, throw: false)
                        ->withHeaders([
                            'User-Agent' => 'WeddingOrganizerApp/1.0',
                        ])->get('https://nominatim.openstreetmap.org/search', [
                            'q' => $model->address,
                            'format' => 'json',
                            'limit' => 1,
                        ]);

                    if ($response->successful() && $json = $response->json()) {
                        if (isset($json[0])) {
                            $result = $json[0];
                            $model->latitude = (float) $result['lat'];
                            $model->longitude = (float) $result['lon'];
                        }
                    }
                } catch (\Throwable $e) {
                    // Fail silently — geocoding tidak boleh block save operation
                    \Illuminate\Support\Facades\Log::warning('[WeddingOrganizer] Geocoding failed: '.$e->getMessage());
                }
            }
        });
    }

    protected $fillable = [
        'name',
        'description',
        'address',
        'latitude',
        'longitude',
        'is_verified',
        'phone',
        'whatsapp',
        'email',
        'instagram',
        'operational_hours',
    ];

    protected $appends = [
        'business_name',
        'city',
        'logo_url',
        'cover_image_url',
        'total_reviews',
        'video_url',
        'location',
    ];

    /**
     * Get the location as an array for Filament Google Maps
     */
    public function getLocationAttribute(): array
    {
        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }

    /**
     * Set the latitude and longitude from the location array
     */
    public function setLocationAttribute(?array $location): void
    {
        if (is_array($location)) {
            $this->attributes['latitude'] = $location['lat'];
            $this->attributes['longitude'] = $location['lng'];
        }
    }

    /**
     * Get the superadmin user (Dekorasi Bunga Pernikahan owner)
     */
    public static function getOwner()
    {
        return (config('auth.providers.users.model'))::whereHas('roles', function ($query): void {
            $query->where('name', 'super_admin');
        })->first();
    }

    public static function getBrand(): ?self
    {
        // Hanya mencari record utama Dekorasi Bunga Pernikahan
        return self::query()
            ->where('id', 1)
            ->first() ?? self::query()->first();
    }

    public function getBusinessNameAttribute()
    {
        return $this->name;
    }

    public function getCityAttribute()
    {
        if (! $this->address) {
            return 'Unknown';
        }

        // Jika address sudah ringkas (1-2 bagian), kembalikan langsung
        $parts = array_map('trim', explode(',', $this->address));
        $parts = array_filter($parts); // Hapus elemen kosong
        $parts = array_values($parts);

        if (count($parts) <= 2) {
            return implode(', ', $parts);
        }

        // Jika address panjang (dari Nominatim display_name lama),
        // abaikan suffix negara ("Indonesia") lalu ambil 2 bagian terakhir
        $ignoreSuffix = ['Indonesia', 'indonesia'];
        if (in_array(end($parts), $ignoreSuffix)) {
            array_pop($parts);
        }

        // Ambil 2 bagian terakhir: "Kota, Provinsi"
        $relevant = array_slice($parts, -2);

        return implode(', ', $relevant) ?: 'Unknown';
    }

    public function getPhoneAttribute()
    {
        // Use DB column first, fallback to owner user
        if (! empty($this->attributes['phone'])) {
            return $this->attributes['phone'];
        }

        return self::getOwner()?->phone ?? '';
    }

    public function getWhatsappAttribute()
    {
        if (! empty($this->attributes['whatsapp'])) {
            return $this->attributes['whatsapp'];
        }

        // fallback to phone
        return $this->getPhoneAttribute();
    }

    public function getEmailAttribute()
    {
        if (! empty($this->attributes['email'])) {
            return $this->attributes['email'];
        }

        return self::getOwner()?->email ?? '';
    }

    public function getOperationalHoursAttribute()
    {
        return $this->attributes['operational_hours'] ?? 'Senin - Minggu: 09:00 - 18:00';
    }

    public function getLogoUrlAttribute()
    {
        return $this->normalizeImageUrl($this->getValidMediaUrl($this->getFirstMedia('logo')), null);
    }

    public function getCoverImageUrlAttribute()
    {
        return $this->normalizeImageUrl($this->getValidMediaUrl($this->getFirstMedia('gallery')), null);
    }

    public function getVideoUrlAttribute(): ?string
    {
        $url = $this->getFirstMediaUrl('videos') ?: null;
        return $url ? \App\Providers\NativeServiceProvider::normalizeUrl($url) : null;
    }

    public function getTotalReviewsAttribute()
    {
        return $this->reviews_count ?? $this->reviews()->count();
    }

    public function packages()
    {
        return $this->hasMany(Package::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    private function normalizeImageUrl(?string $url, ?string $fallback = null): ?string
    {
        if (! filled($url)) {
            return $fallback;
        }

        if (Str::startsWith($url, ['http://', 'https://', 'data:image', '/'])) {
            return \App\Providers\NativeServiceProvider::normalizeUrl($url);
        }

        $resolved = Storage::disk('public')->url(ltrim($url, '/'));

        return \App\Providers\NativeServiceProvider::normalizeUrl($resolved);
    }

    private function getValidMediaUrl(?Media $media): ?string
    {
        if (! $media) {
            return null;
        }

        return $media->getUrl();
    }
}
