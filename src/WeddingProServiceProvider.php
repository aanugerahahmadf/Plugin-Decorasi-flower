<?php

namespace Aanugerah\WeddingPro;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class WeddingProServiceProvider extends PackageServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->register(AutoTranslationServiceProvider::class);
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('wedding-pro')
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([
                '0001_01_01_000000_create_users_table',
                '2024_10_23_010712_create_user_languages_table',
                '2026_02_09_172001_create_categories_table',
                '2026_02_09_172002_create_wedding_organizers_table',
                '2026_02_09_172003_create_articles_table',
                '2026_02_09_172005_create_packages_table',
                '2026_04_18_000000_create_products_table',
                '2026_02_09_172006_create_orders_table',
                '2026_04_15_205631_create_transactions_table',
                '2026_02_09_172007_create_reviews_table',
                '2026_02_09_172008_create_banners_table',
                '2026_02_09_172010_create_vouchers_table',
                '2026_02_09_172010_create_wishlists_table',
                '2026_04_26_225228_create_carts_table',
                '2026_03_30_232913_create_histories_table',
                '2026_02_09_170825_create_fm_inboxes_table',
                '2026_02_09_170826_create_fm_messages_table',
                '2026_04_26_004259_add_meta_to_fm_messages_table',
                '2026_04_26_004311_add_meta_to_fm_inboxes_table',
            ]);
    }

    public function packageBooted(): void
    {
        Models\Order::observe(Observers\OrderObserver::class);
        Models\Transaction::observe(Observers\TransactionObserver::class);
        \Spatie\MediaLibrary\MediaCollections\Models\Media::observe(Observers\MediaObserver::class);

        // Register Assets
        \Filament\Support\Facades\FilamentAsset::register([
            \Filament\Support\Assets\Css::make('wedding-pro-mobile', __DIR__ . '/../resources/assets/css/mobile-cards.css'),
            \Filament\Support\Assets\Css::make('wedding-pro-upload', __DIR__ . '/../resources/assets/css/advanced-file-upload.css'),
            \Filament\Support\Assets\Js::make('wedding-pro-upload-js', __DIR__ . '/../resources/assets/js/advanced-file-upload.js'),
        ], 'aanugerah/wedding-pro');

        // Register Render Hooks (Snap Script for Midtrans)
        \Filament\Support\Facades\FilamentView::registerRenderHook(
            'panels::body.end',
            fn (): \Illuminate\Contracts\View\View => view('wedding-pro::filament.snap-script'),
        );

        // Register API Routes
        $this->loadRoutesFrom(__DIR__ . '/Http/Routes/api.php');
    }
}
