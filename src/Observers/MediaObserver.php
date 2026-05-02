<?php

namespace Aanugerah\WeddingPro\Observers;

use Aanugerah\WeddingPro\Services\CBIRService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaObserver
{
    protected $cbirService;

    public function __construct(CBIRService $cbirService)
    {
        $this->cbirService = $cbirService;
    }

    public function created(Media $media)
    {
        $targetCollections = ['gallery', 'product_image', 'package_image', 'category_image'];

        if (in_array($media->collection_name, $targetCollections)) {
            // Skip CBIR indexing di mobile — AI server tidak tersedia dari device
            if (\Aanugerah\WeddingPro\NativeServiceProvider::isNativeMobile()) {
                return;
            }

            try {
                $this->cbirService->indexMedia($media);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[MediaObserver] CBIR indexing failed: '.$e->getMessage());
            }
        }
    }

    public function deleted(Media $media)
    {
        // Skip di mobile
        if (\Aanugerah\WeddingPro\NativeServiceProvider::isNativeMobile()) {
            return;
        }

        try {
            $this->cbirService->removeFromIndex($media->id);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[MediaObserver] CBIR remove failed: '.$e->getMessage());
        }
    }
}
