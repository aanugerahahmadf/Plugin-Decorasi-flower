<?php

namespace Aanugerah\WeddingPro;


use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Native\Mobile\Network;
use Native\Mobile\Providers\DeviceServiceProvider;
use Native\Mobile\Providers\NetworkServiceProvider;
use Native\Mobile\Providers\SystemServiceProvider;
use Native\Mobile\System;
use SRWieZ\NativePHP\Mobile\Screen\ScreenServiceProvider;

class NativeServiceProvider extends ServiceProvider
{
    public static $result = null;
    public static $ip = null;

    // ═══════════════════════════════════════════════════════════════════════
    // ENVIRONMENT DETECTION HELPER
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Returns true ONLY when running inside a real NativePHP mobile app
     * (Android or iOS), even without NATIVEPHP_RUNNING being set.
     *
     * Detection priority:
     *  1. NATIVEPHP_RUNNING constant (set by NativePHP bootstrapper)
     *  2. NATIVEPHP_RUNNING env var (fallback)
     *  3. No REMOTE_ADDR + non-Windows OS (CLI / embedded PHP server on device)
     */
    public static function isNativeMobile(): bool
    {
        if (self::$result !== null) {
            return self::$result;
        }

        // 1. Explicit NativePHP constant (most reliable — set by NativePHP bootstrapper)
        if (defined('NATIVEPHP_RUNNING') && constant('NATIVEPHP_RUNNING')) {
            return self::$result = true;
        }

        // 2. Explicit env flag
        if (env('NATIVEPHP_RUNNING') || env('IS_NATIVE_MOBILE')) {
            return self::$result = true;
        }

        // 3. NativePHP sets database.default = 'nativephp' (SQLite) on device
        //    Check this BEFORE any DB operations to avoid circular dependency
        $dbDefault = env('DB_CONNECTION') ?: \Illuminate\Support\Facades\Config::get('database.default', 'sqlite');
        if ($dbDefault === 'nativephp' || $dbDefault === 'sqlite') {
            // Only treat as mobile if also running on Linux/Darwin (device OS)
            if (PHP_OS_FAMILY === 'Linux' || PHP_OS_FAMILY === 'Darwin') {
                return self::$result = true;
            }
        }

        // 4. Android WebView User-Agent detection
        //    NativePHP Android embeds Chromium WebView which sends "wv)" in UA
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (! empty($userAgent)) {
            if (preg_match('/Android.*wv\)/i', $userAgent)) {
                return self::$result = true;
            }
            // iOS WKWebView
            if (preg_match('/iPhone|iPad.*Mobile.*Safari/i', $userAgent) && ! str_contains($userAgent, 'CriOS') && ! str_contains($userAgent, 'FxiOS')) {
                return self::$result = true;
            }
        }

        // 5. Heuristic: non-Windows OS with no HTTP client (embedded PHP CLI server on device)
        $isCI = env('GITHUB_ACTIONS') || \Illuminate\Support\Facades\App::runningUnitTests();
        $isCloud = env('LARAVEL_CLOUD') || env('DOCKER_ENV') || env('APP_ENV') === 'production';

        if (PHP_OS_FAMILY !== 'Windows' && ! isset($_SERVER['REMOTE_ADDR']) && ! $isCloud && ! $isCI) {
            return self::$result = true;
        }

        return self::$result = false;
    }

    /**
     * Returns the correct "localhost" equivalent for the current environment:
     */
    public static function mobileHostIp(): string
    {
        if (self::$ip !== null) {
            return self::$ip;
        }

        // Allow explicit override via environment variable
        if ($override = env('NATIVE_HOST_IP')) {
            return self::$ip = $override;
        }

        // Android emulator special loopback
        if (PHP_OS_FAMILY === 'Linux') {
            return self::$ip = '10.0.2.2';
        }

        // iOS simulator / macOS host
        if (PHP_OS_FAMILY === 'Darwin') {
            return self::$ip = '127.0.0.1';
        }

        return self::$ip = '127.0.0.1';
    }

    /**
     * Normalize a URL so it works on the current platform.
     * On mobile, replaces 127.0.0.1/localhost with the correct host IP.
     * On web, returns the URL unchanged.
     */
    public static function normalizeUrl(string $url): string
    {
        if (! self::isNativeMobile()) {
            return $url;
        }

        $hostIp = self::mobileHostIp();

        return str_replace(
            ['http://127.0.0.1', 'http://localhost', 'https://127.0.0.1', 'https://localhost'],
            ["http://{$hostIp}", "http://{$hostIp}", "https://{$hostIp}", "https://{$hostIp}"],
            $url
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // REGISTER
    // ═══════════════════════════════════════════════════════════════════════

    public function register(): void
    {
        // Guard: skip all NativePHP-specific code on Docker/backend/Cloud envs
        if (env('DOCKER_ENV') || env('LARAVEL_CLOUD')) {
            return;
        }

        // Register native singletons only on mobile
        if (self::isNativeMobile()) {
            if (class_exists(Network::class)) {
                $this->app->singleton(Network::class, fn () => new Network);
            }
            if (class_exists(System::class)) {
                $this->app->singleton(System::class, fn () => new System);
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // BOOT
    // ═══════════════════════════════════════════════════════════════════════

    public function boot(): void
    {
        // Guard: skip on Docker / pure backend / Cloud
        if (env('DOCKER_ENV') || env('LARAVEL_CLOUD')) {
            return;
        }

        $isMobile = self::isNativeMobile();

        // ── 1. RESOLVE HOST IPs ───────────────────────────────────────────
        $hostIp = self::mobileHostIp();           // e.g. 10.0.2.2 (Android)
        $serverPort = env('NATIVE_SERVER_PORT', 8000);  // port Laragon/artisan serve

        $dbHost = env('DB_HOST', '127.0.0.1');
        $reverbHost = env('REVERB_HOST', 'localhost');
        $appUrl = env('APP_URL', 'http://127.0.0.1');
        $currentHost = parse_url($appUrl, PHP_URL_HOST) ?? '127.0.0.1'; // default agar tidak undefined

        // Dynamic Host Detection: If accessed via LAN IP, emulator IP, or ngrok
        if (! app()->runningInConsole() && isset($_SERVER['HTTP_HOST'])) {
            $currentHost = $_SERVER['HTTP_HOST'];

            // Handle X-Forwarded headers from ngrok/proxies
            $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
            $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'];

            // If the request isn't coming from standard localhost
            if (! in_array(parse_url('http://'.$host, PHP_URL_HOST), ['127.0.0.1', 'localhost'])) {
                $appUrl = "{$proto}://{$host}";
                $hostIp = parse_url($appUrl, PHP_URL_HOST);
                $currentHost = $host;
            }
        }

        if ($isMobile) {
            // Replace "localhost" / "127.0.0.1" with the correct host IP for the platform
            $replace = ['127.0.0.1', 'localhost'];

            if (in_array($dbHost, $replace)) {
                $dbHost = $hostIp;
            }
            if (in_array($reverbHost, $replace)) {
                $reverbHost = $hostIp;
            }

            // Rebuild host PC URL to proxy requests to (preserve port if set)
            $parsedUrl = parse_url($appUrl);
            $scheme = $parsedUrl['scheme'] ?? 'http';
            // Priority: APP_URL port > NATIVE_SERVER_PORT > 8000
            $port = $parsedUrl['port'] ?? $serverPort;

            // Only append port if not standard (80 for http, 443 for https)
            $portSuffix = ($port == 80 || $port == 443) ? '' : ":$port";
            $hostServerUrl = "{$scheme}://{$hostIp}{$portSuffix}";
        } else {
            $hostServerUrl = $appUrl;
        }

        // ── 3. APPLY RUNTIME CONFIG ───────────────────────────────────────
        $runtimeConfig = [
            'app.url' => $appUrl,
            'sanctum.stateful' => array_unique(array_merge(
                explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,127.0.0.1')),
                [$currentHost ?? '']
            )),

            // Session: use file driver on mobile to avoid proxy loop
            'session.driver' => $isMobile ? 'file' : env('SESSION_DRIVER', 'database'),

            // Database
            'database.connections.mysql.host' => $dbHost,
            'database.connections.mysql.port' => env('DB_PORT', '3306'),
            'database.connections.mysql.database' => env('DB_DATABASE', config('database.connections.mysql.database', 'Wedding_organizer')),
            'database.connections.mysql.username' => env('DB_USERNAME', 'root'),
            'database.connections.mysql.password' => env('DB_PASSWORD', ''),

            // Reverb / Broadcasting
            'reverb.apps.0.host' => $reverbHost,
            'broadcasting.connections.reverb.options.host' => $reverbHost,
            'broadcasting.connections.pusher.options.host' => $reverbHost,

            // AI / CBIR Service Synchronization
            // Optimization: Web/Native should use 127.0.0.1, Mobile Emulator should use $hostIp
            'wedding-pro.ai_core_url' => $isMobile ? str_replace(['127.0.0.1', 'localhost'], $hostIp, env('AI_CORE_URL', 'http://127.0.0.1:5000')) : env('AI_CORE_URL', 'http://127.0.0.1:5000'),
            'wedding-pro.cbir_api_url' => $isMobile ? str_replace(['127.0.0.1', 'localhost'], $hostIp, env('CBIR_API_URL', 'http://127.0.0.1:5000')) : env('CBIR_API_URL', 'http://127.0.0.1:5000'),
        ];

        $proxyUrl = "{$hostServerUrl}/api/db-proxy";

        if ($isMobile) {
            $runtimeConfig['database.default'] = 'mysql_proxy';
            $runtimeConfig['database.connections.mysql_proxy.proxy_url'] = $proxyUrl;
            $runtimeConfig['database.connections.mysql_proxy.proxy_secret'] = env('NATIVE_DB_PROXY_SECRET', 'nativephp-db-proxy-secret-2024');
            $runtimeConfig['database.connections.mysql_proxy.database'] = env('DB_DATABASE', config('database.connections.mysql.database', 'Wedding_organizer'));
        }

        config($runtimeConfig);

        if ($isMobile) {
            $dbConnection = config('database.default');
            error_log(sprintf(
                '[NativePHP] Environment: %s | OS: %s | Host IP: %s | DB via: %s | Proxy URL: %s',
                PHP_OS_FAMILY,
                PHP_OS,
                $hostIp,
                $dbConnection,
                $proxyUrl ?? 'N/A'
            ));
        }

        // ── 4. REFRESH DB CONNECTION ──────────────────────────────────────
        // Removed redundant purge/reconnect to prevent connection thrashing
        // Laravel handles connection lifecycle efficiently.

        // ── 5. ON-DEMAND INITIALIZATION (Mobile only) ────────────────────
        // Optimization: Use a flag file to persist 'initialized' state across requests.
        // PHP static variables do not persist across separate HTTP requests.
        $flagFile = storage_path('framework/mobile_init.flag');

        if ($isMobile && ! file_exists($flagFile) && ! app()->runningInConsole()) {
            try {
                // Double check DB status only if flag is missing
                $hasUsers = false;
                try {
                    $hasUsers = User::exists();
                } catch (\Throwable $e) {
                    $hasUsers = false;
                }

                if (! $hasUsers) {
                    error_log('[NativePHP] Database empty. Initializing...');
                    Artisan::call('migrate', ['--force' => true]);

                    // Jalankan semua seeder sama persis seperti DatabaseSeeder::run()
                    // Urutan penting: roles → admin → organizer → products → packages → banners → articles → terms
                    $seeders = [
                        'RolesAndPermissionsSeeder',
                        'SuperAdminSeeder',
                        'WeddingOrganizerSeeder',
                        'ProductSeeder',
                        'PackageSeeder',
                        'BannerSeeder',
                        'ArticleSeeder',
                        'TermsAndConditionsSeeder',
                    ];

                    foreach ($seeders as $seeder) {
                        try {
                            Artisan::call('db:seed', [
                                '--class' => "Database\\Seeders\\{$seeder}",
                                '--force' => true,
                            ]);
                            error_log("[NativePHP] Seeder done: {$seeder}");
                        } catch (\Throwable $e) {
                            error_log("[NativePHP] Seeder failed ({$seeder}): ".$e->getMessage());
                            // Lanjut ke seeder berikutnya meski ada yang gagal
                        }
                    }

                    error_log('[NativePHP] Initialization done.');
                }

                // Create flag to skip this check in future requests
                file_put_contents($flagFile, date('Y-m-d H:i:s'));

            } catch (\Throwable $e) {
                error_log('[NativePHP] Init failed: '.$e->getMessage());
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // NATIVEPHP PLUGINS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * The NativePHP plugins to enable.
     * Only plugins listed here will be compiled into your native builds.
     *
     * @return array<int, class-string<ServiceProvider>>
     */
    public function plugins(): array
    {
        return [
            ScreenServiceProvider::class,
            SystemServiceProvider::class,
            DeviceServiceProvider::class,
            NetworkServiceProvider::class,
        ];
    }
}
