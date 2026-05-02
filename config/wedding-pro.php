<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Core URL
    |--------------------------------------------------------------------------
    | URL server AI (Python Flask/FastAPI) untuk fitur CBIR dan Chatbot.
    | Contoh lokal: http://127.0.0.1:5000
    | Contoh produksi: https://ai.domain-anda.com
    */
    'ai_core_url' => env('AI_CORE_URL', 'http://127.0.0.1:5000'),

    /*
    |--------------------------------------------------------------------------
    | CBIR API URL
    |--------------------------------------------------------------------------
    | URL endpoint khusus untuk Content-Based Image Retrieval.
    | Biasanya sama dengan AI Core URL.
    */
    'cbir_api_url' => env('CBIR_API_URL', 'http://127.0.0.1:5000'),

    /*
    |--------------------------------------------------------------------------
    | AI Core Timeout
    |--------------------------------------------------------------------------
    | Batas waktu (detik) untuk request ke server AI.
    */
    'ai_core_timeout' => env('AI_CORE_TIMEOUT', 15),

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    | Daftar bahasa yang didukung oleh aplikasi.
    | Format: 'kode_locale' => 'Nama Bahasa'
    |
    | Digunakan oleh:
    | - SetLocale middleware (deteksi bahasa browser)
    | - AutoTranslationService (skip label bahasa dari terjemahan)
    | - LanguageController (validasi locale saat switch)
    */
    'locales' => [
        'id' => 'Indonesian',
        'en' => 'English',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    | Bahasa default jika tidak ada preferensi dari user atau browser.
    */
    'default_locale' => env('APP_LOCALE', 'id'),

    /*
    |--------------------------------------------------------------------------
    | NativePHP Mobile Settings
    |--------------------------------------------------------------------------
    | Konfigurasi untuk integrasi NativePHP Mobile (Android/iOS).
    */
    'native' => [
        'host_ip'       => env('NATIVE_HOST_IP', '10.0.2.2'),
        'server_port'   => env('NATIVE_SERVER_PORT', 8000),
        'db_proxy_secret' => env('NATIVE_DB_PROXY_SECRET', 'nativephp-db-proxy-secret-2024'),
    ],

];
