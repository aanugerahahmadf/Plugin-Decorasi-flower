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
        'host_ip'         => env('NATIVE_HOST_IP', '10.0.2.2'),
        'server_port'     => env('NATIVE_SERVER_PORT', 8000),
        'db_proxy_secret' => env('NATIVE_DB_PROXY_SECRET', 'nativephp-db-proxy-secret-2024'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Messages / Chat Settings
    |--------------------------------------------------------------------------
    | Konfigurasi untuk fitur pesan dan AI Consultant Bot.
    */
    'messages' => [

        /*
         | Aktifkan/nonaktifkan AI Bot auto-reply.
         | Jika false, bot tidak akan membalas pesan apapun.
         */
        'bot_enabled' => env('WEDDING_PRO_BOT_ENABLED', true),

        /*
         | Delay (detik) sebelum bot mengirim balasan.
         | Memberikan kesan "sedang mengetik" yang natural.
         */
        'bot_reply_delay' => env('WEDDING_PRO_BOT_REPLY_DELAY', 5),

        /*
         | Nama role yang dianggap sebagai admin/bot sender.
         | Bot akan mengirim pesan menggunakan akun dengan role ini.
         */
        'admin_role' => env('WEDDING_PRO_ADMIN_ROLE', 'super_admin'),

        /*
         | Aktifkan upload attachment (gambar/file) di chat.
         */
        'attachments_enabled' => env('WEDDING_PRO_ATTACHMENTS_ENABLED', true),

        /*
         | Ukuran maksimal attachment dalam kilobytes (KB).
         | Default: 10240 KB = 10 MB
         */
        'max_attachment_size_kb' => env('WEDDING_PRO_MAX_ATTACHMENT_KB', 10240),

        /*
         | Tipe file yang diizinkan untuk di-upload di chat.
         */
        'allowed_attachment_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx'],

        /*
         | Interval polling pesan (detik) di panel Filament.
         | Set ke null untuk menonaktifkan polling (gunakan Reverb/WebSocket).
         | Dinonaktifkan otomatis saat berjalan di NativePHP Mobile.
         */
        'polling_interval' => env('WEDDING_PRO_CHAT_POLLING', '5s'),

        /*
         | Jumlah maksimal pesan yang dimuat per halaman.
         */
        'messages_per_page' => env('WEDDING_PRO_MESSAGES_PER_PAGE', 50),

        /*
         | Nama collection media Spatie untuk attachment pesan.
         */
        'media_collection' => 'filament-messages',

    ],

];
