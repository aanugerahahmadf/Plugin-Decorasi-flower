<?php

use Aanugerah\WeddingPro\Services\CBIRService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    config(['services.ai_core_url' => 'http://127.0.0.1:5000']);
    config(['services.ai_core_timeout' => 15]);
    Cache::flush();
});

// ── errorResponse() ───────────────────────────────────────────────────────

it('errorResponse returns correct structure', function () {
    $service = new CBIRService();

    $reflection = new ReflectionMethod(CBIRService::class, 'errorResponse');
    $reflection->setAccessible(true);

    $result = $reflection->invoke($service, 'Server offline');

    expect($result)->toMatchArray([
        'success' => false,
        'error' => true,
        'message' => 'Server offline',
        'results' => [],
        'query_time_seconds' => 0,
    ]);
});

// ── searchByImage() ───────────────────────────────────────────────────────

it('searchByImage returns error response when AI server is offline', function () {
    Http::fake([
        'http://127.0.0.1:5000/*' => Http::response(null, 500),
    ]);

    // Buat temp file untuk simulasi upload
    $tmpFile = tempnam(sys_get_temp_dir(), 'cbir_test_');
    file_put_contents($tmpFile, 'fake-image-content');

    $file = new \Symfony\Component\HttpFoundation\File\File($tmpFile);

    $service = new CBIRService();
    $result = $service->searchByImage($file, 10);

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBeTrue();
    expect($result['results'])->toBeArray()->toBeEmpty();

    unlink($tmpFile);
});

it('searchByImage returns normalized results on success', function () {
    Http::fake([
        'http://127.0.0.1:5000/api/search' => Http::response([
            'success' => true,
            'results' => [
                [
                    'owner_id' => 1,
                    'type' => 'package',
                    'score' => 0.95,
                    'similarity' => 95.0,
                    'image_url' => 'http://example.com/img.jpg',
                ],
                [
                    'owner_id' => 2,
                    'type' => 'product',
                    'score' => 0.80,
                    'similarity' => 80.0,
                    'image_url' => null,
                ],
            ],
            'query_time_seconds' => 1.48,
        ], 200),
    ]);

    $tmpFile = tempnam(sys_get_temp_dir(), 'cbir_test_');
    file_put_contents($tmpFile, 'fake-image-content');
    $file = new \Symfony\Component\HttpFoundation\File\File($tmpFile);

    $service = new CBIRService();
    $result = $service->searchByImage($file, 10);

    expect($result['success'])->toBeTrue();
    expect($result['results'])->toHaveCount(2);
    expect($result['results'][0]['owner_id'])->toBe(1);
    expect($result['results'][0]['type'])->toBe('package');
    expect($result['results'][0]['similarity'])->toBe(95.0);
    expect($result['query_time_seconds'])->toBe(1.48);

    unlink($tmpFile);
});

it('searchByImage filters out results without owner_id', function () {
    Http::fake([
        'http://127.0.0.1:5000/api/search' => Http::response([
            'success' => true,
            'results' => [
                ['owner_id' => 1, 'type' => 'package', 'score' => 0.9, 'similarity' => 90.0],
                ['type' => 'product', 'score' => 0.5], // no owner_id — harus difilter
            ],
            'query_time_seconds' => 0.5,
        ], 200),
    ]);

    $tmpFile = tempnam(sys_get_temp_dir(), 'cbir_test_');
    file_put_contents($tmpFile, 'fake-image-content');
    $file = new \Symfony\Component\HttpFoundation\File\File($tmpFile);

    $service = new CBIRService();
    $result = $service->searchByImage($file, 10);

    expect($result['results'])->toHaveCount(1);
    expect($result['results'][0]['owner_id'])->toBe(1);

    unlink($tmpFile);
});

it('searchByImage caps top_k to maximum 50', function () {
    Http::fake([
        'http://127.0.0.1:5000/api/search' => Http::response([
            'success' => true,
            'results' => [],
            'query_time_seconds' => 0,
        ], 200),
    ]);

    $tmpFile = tempnam(sys_get_temp_dir(), 'cbir_test_');
    file_put_contents($tmpFile, 'fake-image-content');
    $file = new \Symfony\Component\HttpFoundation\File\File($tmpFile);

    $service = new CBIRService();
    // top_k = 999 harus di-cap ke 50
    $result = $service->searchByImage($file, 999);

    expect($result['success'])->toBeTrue();

    unlink($tmpFile);
});

// ── indexMedia() ──────────────────────────────────────────────────────────

it('indexMedia returns false when AI server fails', function () {
    Http::fake([
        'http://127.0.0.1:5000/api/index/add' => Http::response(null, 500),
    ]);

    $media = Mockery::mock(\Spatie\MediaLibrary\MediaCollections\Models\Media::class);
    $media->shouldReceive('getPath')->andReturn('/tmp/fake.jpg');
    $media->shouldReceive('getUrl')->andReturn('http://example.com/fake.jpg');
    $media->model_type = \Aanugerah\WeddingPro\Models\Package::class;
    $media->model_id = 1;
    $media->id = 99;

    $service = new CBIRService();
    expect($service->indexMedia($media))->toBeFalse();
});

it('indexMedia returns true on success and increments cache version', function () {
    Http::fake([
        'http://127.0.0.1:5000/api/index/add' => Http::response(['success' => true], 200),
    ]);

    Cache::put('cbir_cache_version', 1);

    $media = Mockery::mock(\Spatie\MediaLibrary\MediaCollections\Models\Media::class);
    $media->shouldReceive('getPath')->andReturn('/tmp/fake.jpg');
    $media->shouldReceive('getUrl')->andReturn('http://example.com/fake.jpg');
    $media->model_type = \Aanugerah\WeddingPro\Models\Package::class;
    $media->model_id = 1;
    $media->id = 99;

    $service = new CBIRService();
    $result = $service->indexMedia($media);

    expect($result)->toBeTrue();
    expect(Cache::get('cbir_cache_version'))->toBe(2);
});
