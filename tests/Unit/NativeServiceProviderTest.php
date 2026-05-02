<?php

namespace Aanugerah\WeddingPro\Tests\Unit;

use Aanugerah\WeddingPro\NativeServiceProvider;
use Orchestra\Testbench\TestCase;
use ReflectionClass;

class NativeServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetStaticCache();
    }

    protected function tearDown(): void
    {
        $this->resetStaticCache();
        parent::tearDown();
    }

    private function resetStaticCache(): void
    {
        NativeServiceProvider::$result = null;
        NativeServiceProvider::$ip = null;
    }

    // ── isNativeMobile() ──────────────────────────────────────────────────

    public function test_returns_false_in_unit_test_environment(): void
    {
        // GITHUB_ACTIONS=true dan runningUnitTests() = true → heuristic #5 skip
        $this->assertFalse(NativeServiceProvider::isNativeMobile());
    }

    // ── mobileHostIp() ────────────────────────────────────────────────────

    public function test_returns_correct_ip_for_current_os(): void
    {
        $ip = NativeServiceProvider::mobileHostIp();

        $this->assertIsString($ip);
        $this->assertNotEmpty($ip);

        // Validasi format IP
        $this->assertMatchesRegularExpression(
            '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/',
            $ip
        );
    }

    public function test_respects_native_host_ip_env_override(): void
    {
        putenv('NATIVE_HOST_IP=192.168.1.200');
        $_ENV['NATIVE_HOST_IP'] = '192.168.1.200';

        $ref = new ReflectionClass(NativeServiceProvider::class);
        $ip = $ref->getProperty('ip');
        $ip->setAccessible(true);
        $ip->setValue(null, null);

        $result = NativeServiceProvider::mobileHostIp();
        $this->assertSame('192.168.1.200', $result);

        // Cleanup
        putenv('NATIVE_HOST_IP');
        unset($_ENV['NATIVE_HOST_IP']);
        $ip->setValue(null, null);
    }

    // ── normalizeUrl() ────────────────────────────────────────────────────

    public function test_normalize_url_returns_unchanged_when_not_mobile(): void
    {
        $url = 'http://127.0.0.1:8000/storage/image.jpg';
        $this->assertSame($url, NativeServiceProvider::normalizeUrl($url));
    }

    public function test_normalize_url_replaces_localhost_on_mobile(): void
    {
        $ref = new ReflectionClass(NativeServiceProvider::class);

        $resultProp = $ref->getProperty('result');
        $resultProp->setAccessible(true);
        $resultProp->setValue(null, true); // force mobile = true

        $ipProp = $ref->getProperty('ip');
        $ipProp->setAccessible(true);
        $ipProp->setValue(null, '10.0.2.2');

        $normalized = NativeServiceProvider::normalizeUrl('http://127.0.0.1:8000/storage/img.jpg');
        $this->assertSame('http://10.0.2.2:8000/storage/img.jpg', $normalized);

        // Cleanup
        $resultProp->setValue(null, null);
        $ipProp->setValue(null, null);
    }

    public function test_normalize_url_replaces_https_localhost_on_mobile(): void
    {
        $ref = new ReflectionClass(NativeServiceProvider::class);

        $resultProp = $ref->getProperty('result');
        $resultProp->setAccessible(true);
        $resultProp->setValue(null, true);

        $ipProp = $ref->getProperty('ip');
        $ipProp->setAccessible(true);
        $ipProp->setValue(null, '10.0.2.2');

        $normalized = NativeServiceProvider::normalizeUrl('https://localhost/api/packages');
        $this->assertSame('https://10.0.2.2/api/packages', $normalized);

        $resultProp->setValue(null, null);
        $ipProp->setValue(null, null);
    }

    public function test_normalize_url_does_not_modify_external_urls_on_mobile(): void
    {
        $ref = new ReflectionClass(NativeServiceProvider::class);

        $resultProp = $ref->getProperty('result');
        $resultProp->setAccessible(true);
        $resultProp->setValue(null, true);

        $ipProp = $ref->getProperty('ip');
        $ipProp->setAccessible(true);
        $ipProp->setValue(null, '10.0.2.2');

        $url = 'https://cdn.example.com/image.jpg';
        $this->assertSame($url, NativeServiceProvider::normalizeUrl($url));

        $resultProp->setValue(null, null);
        $ipProp->setValue(null, null);
    }
}
