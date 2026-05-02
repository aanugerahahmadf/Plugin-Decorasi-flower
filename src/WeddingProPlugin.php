<?php

namespace Aanugerah\WeddingPro;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Aanugerah\WeddingPro\Filament\Resources\WeddingOrganizerResource;
use Aanugerah\WeddingPro\Filament\Resources\OrderResource;
use Aanugerah\WeddingPro\Filament\Resources\CartResource;
use Aanugerah\WeddingPro\Filament\Resources\ProductResource;
use Aanugerah\WeddingPro\Filament\Resources\PackageResource;
use Aanugerah\WeddingPro\Filament\Resources\WishlistResource;
use Aanugerah\WeddingPro\Filament\Resources\ReviewResource;
use Aanugerah\WeddingPro\Filament\Resources\ArticleResource;
use Aanugerah\WeddingPro\Filament\Resources\HistoryResource;
use Aanugerah\WeddingPro\Filament\User\Pages\MessagesPage;

class WeddingProPlugin implements Plugin
{
    public function getId(): string
    {
        return 'wedding-pro';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->middleware([
                \Aanugerah\WeddingPro\Http\Middleware\MidtransCspMiddleware::class,
                \Aanugerah\WeddingPro\Http\Middleware\SetLocale::class,
            ])
            ->resources([
                WeddingOrganizerResource::class,
                OrderResource::class,
                CartResource::class,
                ProductResource::class,
                PackageResource::class,
                WishlistResource::class,
                ReviewResource::class,
                ArticleResource::class,
                HistoryResource::class,
            ])
            ->pages([
                MessagesPage::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        // Logika saat plugin dijalankan
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament()->getPlugin('wedding-pro');

        return $plugin;
    }
}
