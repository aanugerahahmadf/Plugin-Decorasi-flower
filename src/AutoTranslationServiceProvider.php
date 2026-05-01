<?php

namespace Aanugerah\WeddingPro;

use Aanugerah\WeddingPro\Services\AutoTranslationService;
use Aanugerah\WeddingPro\Translators\AutoTranslator;
use Illuminate\Support\ServiceProvider;

class AutoTranslationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Daftarkan AutoTranslationService sebagai singleton
        $this->app->singleton(AutoTranslationService::class);

        // Override translator Laravel dengan AutoTranslator kita
        $this->app->extend('translator', function ($original, $app) {
            $loader = $app['translation.loader'];
            $locale = $app['config']['app.locale'];

            $translator = new AutoTranslator($loader, $locale);
            $translator->setFallback($app['config']['app.fallback_locale']);
            $translator->setAutoTranslationService($app->make(AutoTranslationService::class));

            return $translator;
        });
    }

    public function boot(): void
    {
        //
    }
}
