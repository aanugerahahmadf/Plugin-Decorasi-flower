<?php

use Aanugerah\WeddingPro\NativeServiceProvider;

beforeEach(function () {
    // Reset static cache sebelum setiap test
    $reflection = new ReflectionClass(NativeServiceProvider::class);

    $result = $reflection->getProperty('result');
    $result->setAccessible(true);
    $result->setValue(null, null);

    $ip = $reflection->getProperty('ip');
    $ip->setAccessible(true);
    $ip->setValue(null, null);
});

// ── isNativeMobile() ──────────────────────────────────────────────────────

it('returns false when running in unit tests (GITHUB_ACTIONS not set)', function () {
    // Unit test environment: REMOTE_ADDR tidak ada tapi GITHUB_ACTIONS atau runningUnitTests() true
    // NativeServiceProvider heuristic #5 skip jika runningUnitTests()
    expect(NativeServiceProvider::isNativeMobile())->toBeFalse();
});

it('returns true when NATIVEPHP_RUNNING constant is defined', function () {
    if (! defined('NATIVEPHP_RUNNING')) {
        define('NATIVEPHP_RUNNING', true);
    }

    $reflection = new ReflectionClass(NativeServiceProvider::class);
    $result = $reflection->getProperty('result');
    $result->setAccessible(true);
    $result->setValue(null, null); // reset cache

    expect(NativeServiceProvider::isNativeMobile())->toBeTrue();
})->skip(defined('NATIVEPHP_RUNNING') ? false : true, 'NATIVEPHP_RUNNING constant not defined');

// ── mobileHostIp() ────────────────────────────────────────────────────────

it('returns 127.0.0.1 on Windows', function () {
    if (PHP_OS_FAMILY !== 'Windows') {
        $this->markTestSkipped('Only runs on Windows');
    }

    expect(NativeServiceProvider::mobileHostIp())->toBe('127.0.0.1');
});

it('returns 10.0.2.2 on Linux', function () {
    if (PHP_OS_FAMILY !== 'Linux') {
        $this->markTestSkipped('Only runs on Linux');
    }

    expect(NativeServiceProvider::mobileHostIp())->toBe('10.0.2.2');
});

it('returns 127.0.0.1 on Darwin (macOS)', function () {
    if (PHP_OS_FAMILY !== 'Darwin') {
        $this->markTestSkipped('Only runs on macOS');
    }

    expect(NativeServiceProvider::mobileHostIp())->toBe('127.0.0.1');
});

it('respects NATIVE_HOST_IP env override', function () {
    $_ENV['NATIVE_HOST_IP'] = '192.168.1.100';
    putenv('NATIVE_HOST_IP=192.168.1.100');

    $reflection = new ReflectionClass(NativeServiceProvider::class);
    $ip = $reflection->getProperty('ip');
    $ip->setAccessible(true);
    $ip->setValue(null, null); // reset cache

    expect(NativeServiceProvider::mobileHostIp())->toBe('192.168.1.100');

    // Cleanup
    unset($_ENV['NATIVE_HOST_IP']);
    putenv('NATIVE_HOST_IP');
    $ip->setValue(null, null);
});

// ── normalizeUrl() ────────────────────────────────────────────────────────

it('returns url unchanged when not on mobile', function () {
    // isNativeMobile() returns false in test env
    $url = 'http://127.0.0.1:8000/storage/image.jpg';
    expect(NativeServiceProvider::normalizeUrl($url))->toBe($url);
});

it('normalizeUrl replaces localhost with host ip on mobile', function () {
    // Simulasi mobile dengan mock static
    $reflection = new ReflectionClass(NativeServiceProvider::class);
    $result = $reflection->getProperty('result');
    $result->setAccessible(true);
    $result->setValue(null, true); // paksa isNativeMobile = true

    $ipProp = $reflection->getProperty('ip');
    $ipProp->setAccessible(true);
    $ipProp->setValue(null, '10.0.2.2'); // paksa hostIp = 10.0.2.2

    $url = 'http://127.0.0.1:8000/storage/image.jpg';
    $normalized = NativeServiceProvider::normalizeUrl($url);

    expect($normalized)->toBe('http://10.0.2.2:8000/storage/image.jpg');

    // Cleanup
    $result->setValue(null, null);
    $ipProp->setValue(null, null);
});

it('normalizeUrl replaces https://localhost on mobile', function () {
    $reflection = new ReflectionClass(NativeServiceProvider::class);
    $result = $reflection->getProperty('result');
    $result->setAccessible(true);
    $result->setValue(null, true);

    $ipProp = $reflection->getProperty('ip');
    $ipProp->setAccessible(true);
    $ipProp->setValue(null, '10.0.2.2');

    $url = 'https://localhost/api/packages';
    $normalized = NativeServiceProvider::normalizeUrl($url);

    expect($normalized)->toBe('https://10.0.2.2/api/packages');

    // Cleanup
    $result->setValue(null, null);
    $ipProp->setValue(null, null);
});

it('normalizeUrl does not modify external urls on mobile', function () {
    $reflection = new ReflectionClass(NativeServiceProvider::class);
    $result = $reflection->getProperty('result');
    $result->setAccessible(true);
    $result->setValue(null, true);

    $ipProp = $reflection->getProperty('ip');
    $ipProp->setAccessible(true);
    $ipProp->setValue(null, '10.0.2.2');

    $url = 'https://cdn.example.com/image.jpg';
    expect(NativeServiceProvider::normalizeUrl($url))->toBe($url);

    // Cleanup
    $result->setValue(null, null);
    $ipProp->setValue(null, null);
});
