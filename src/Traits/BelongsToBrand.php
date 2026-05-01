<?php

namespace Aanugerah\WeddingPro\Traits;

use Aanugerah\WeddingPro\Models\WeddingOrganizer;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToBrand
{
    /**
     * Cache brand ID per-request untuk menghindari query berulang.
     * Di mobile, setiap query = 1 HTTP request ke proxy — sangat mahal!
     */
    private static ?int $_cachedBrandId = null;

    private static function getCachedBrandId(): ?int
    {
        if (static::$_cachedBrandId !== null) {
            return static::$_cachedBrandId > 0 ? static::$_cachedBrandId : null;
        }

        try {
            static::$_cachedBrandId = WeddingOrganizer::getBrand()?->id ?? 0;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[BelongsToBrand] getBrand failed: '.$e->getMessage());
            static::$_cachedBrandId = 0;
        }

        return static::$_cachedBrandId > 0 ? static::$_cachedBrandId : null;
    }

    /**
     * Boot the trait to enforce single-brand logic.
     */
    public static function bootBelongsToBrand(): void
    {
        // Auto-assign brand ID saat create
        static::creating(function ($model) {
            if (empty($model->wedding_organizer_id)) {
                $brandId = static::getCachedBrandId();
                if ($brandId) {
                    $model->wedding_organizer_id = $brandId;
                }
            }
        });

        // Global scope — filter semua query ke brand utama
        static::addGlobalScope('brand', function (Builder $builder) {
            try {
                $brandId = static::getCachedBrandId();
                if ($brandId) {
                    $builder->where($builder->getModel()->getTable().'.wedding_organizer_id', $brandId);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[BelongsToBrand] Global scope failed: '.$e->getMessage());
            }
        });
    }

    /**
     * Get the wedding organizer associated with the model.
     */
    public function weddingOrganizer()
    {
        return $this->belongsTo(WeddingOrganizer::class, 'wedding_organizer_id');
    }
}
