<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Midtrans Merchant ID
    |--------------------------------------------------------------------------
    | Dapatkan dari: https://dashboard.midtrans.com/ → Settings → Access Keys
    */
    'merchant_id' => env('MIDTRANS_MERCHANT_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Midtrans Client Key
    |--------------------------------------------------------------------------
    | Digunakan di frontend untuk inisialisasi Snap.js
    */
    'client_key' => env('MIDTRANS_CLIENT_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Midtrans Server Key
    |--------------------------------------------------------------------------
    | Digunakan di backend untuk membuat transaksi dan verifikasi webhook.
    | JANGAN expose ke frontend.
    */
    'server_key' => env('MIDTRANS_SERVER_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Production Mode
    |--------------------------------------------------------------------------
    | false = Sandbox (testing), true = Production (live payment)
    */
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),

    /*
    |--------------------------------------------------------------------------
    | Sanitize Input
    |--------------------------------------------------------------------------
    | Aktifkan sanitasi input sebelum dikirim ke Midtrans.
    */
    'is_sanitized' => env('MIDTRANS_IS_SANITIZED', true),

    /*
    |--------------------------------------------------------------------------
    | 3D Secure
    |--------------------------------------------------------------------------
    | Aktifkan verifikasi 3DS untuk kartu kredit.
    */
    'is_3ds' => env('MIDTRANS_IS_3DS', true),

    /*
    |--------------------------------------------------------------------------
    | Snap-BI (Standard Nasional Open API) — Opsional
    |--------------------------------------------------------------------------
    | Konfigurasi untuk integrasi Snap-BI jika diperlukan.
    */
    'snap_bi' => [
        'client_id'     => env('MIDTRANS_SNAP_BI_CLIENT_ID', ''),
        'client_secret' => env('MIDTRANS_SNAP_BI_CLIENT_SECRET', ''),
        'partner_id'    => env('MIDTRANS_SNAP_BI_PARTNER_ID', ''),
        'channel_id'    => env('MIDTRANS_SNAP_BI_CHANNEL_ID', ''),
        'private_key'   => env('MIDTRANS_SNAP_BI_PRIVATE_KEY', ''),
        'public_key'    => env('MIDTRANS_SNAP_BI_PUBLIC_KEY', ''),
    ],

];
