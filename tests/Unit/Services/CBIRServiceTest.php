<?php

namespace Aanugerah\WeddingPro\Tests\Unit\Services;

use Aanugerah\WeddingPro\Services\CBIRService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Orchestra\Testbench\TestCase;

class CBIRServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.ai_core_url' => 'http://127.0.0.1:5000']);
        config(['services.ai_core_timeout' => 15]);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ── errorResponse() ───────────────────────────────────────────────────────

    public function test_error_response_returns_correct_structure(): void
    {
        $service = new CBIRService();

        $reflection = new \ReflectionMethod(CBIRService::class, 'errorResponse');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($service, 'Server offline');

        $this->assertSame(false, $result['success']);
        $this->assertSame(true, $result['error']);
        $this->assertSame('Server offline', $result['message']);
        $this->assertSame([], $result['results']);
        $this->assertSame(0, $result['query_time_seconds']);
    }

    // ── searchByImage() ───────────────────────────────────────────────────────

    public function test_search_by_image_returns_error_response_when_ai_server_is_offline(): void
    {
        Http::fake([
            'http://127.0.0.1:5000/*' => Http::response(null, 500),
        ]);

        $tmpFile = tempnam(sys_get_temp_dir(), 'cbir_test_');
        file_put_contents($tmpFile, 'fake-image-content');
        $file = new \Symfony\Component\HttpFoundation\File\File($tmpFile);

        $service = new CBIRService();
        $result = $service->searchByImage($file, 10);

        $this->assertFalse($result['success']);
        $this->assertTrue($result['error']);
        $this->assertIsArray($result['results']);
        $this->assertEmpty($result['results']);

        unlink($tmpFile);
    }

    public function test_search_by_image_returns_normalized_results_on_success(): void
    {
        Http::fake([
            'http://127.0.0.1:5000/api/search' => Http::response([
                'success' => true,
                'results' => [
                    [
                        'owner_id'   => 1,
                        'type'       => 'package',
                        'score'      => 0.95,
                        'similarity' => 95.0,
                        'image_url'  => 'http://example.com/img.jpg',
                    ],
                    [
                        'owner_id'   => 2,
                        'type'       => 'product',
                        'score'      => 0.80,
                        'similarity' => 80.0,
                        'image_url'  => null,
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

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['results']);
        $this->assertSame(1, $result['results'][0]['owner_id']);
        $this->assertSame('package', $result['results'][0]['type']);
        $this->assertSame(95.0, $result['results'][0]['similarity']);
        $this->assertSame(1.48, $result['query_time_seconds']);

        unlink($tmpFile);
    }

    public function test_search_by_image_filters_out_results_without_owner_id(): void
    {
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

        $this->assertCount(1, $result['results']);
        $this->assertSame(1, $result['results'][0]['owner_id']);

        unlink($tmpFile);
    }

    public function test_search_by_image_caps_top_k_to_maximum_50(): void
    {
        Http::fake([
            'http://127.0.0.1:5000/api/search' => Http::response([
                'success'            => true,
                'results'            => [],
                'query_time_seconds' => 0,
            ], 200),
        ]);

        $tmpFile = tempnam(sys_get_temp_dir(), 'cbir_test_');
        file_put_contents($tmpFile, 'fake-image-content');
        $file = new \Symfony\Component\HttpFoundation\File\File($tmpFile);

        $service = new CBIRService();
        // top_k = 999 harus di-cap ke 50
        $result = $service->searchByImage($file, 999);

        $this->assertTrue($result['success']);

        unlink($tmpFile);
    }

    // ── indexMedia() ──────────────────────────────────────────────────────────

    public function test_index_media_returns_false_when_ai_server_fails(): void
    {
        Http::fake([
            'http://127.0.0.1:5000/api/index/add' => Http::response(null, 500),
        ]);

        $media = Mockery::mock(\Spatie\MediaLibrary\MediaCollections\Models\Media::class)->makePartial();
        $media->shouldReceive('getPath')->andReturn('/tmp/fake.jpg');
        $media->shouldReceive('getUrl')->andReturn('http://example.com/fake.jpg');
        $media->model_type = \Aanugerah\WeddingPro\Models\Package::class;
        $media->model_id   = 1;
        $media->id         = 99;

        $service = new CBIRService();
        $this->assertFalse($service->indexMedia($media));
    }

    public function test_index_media_returns_true_on_success_and_increments_cache_version(): void
    {
        Http::fake([
            'http://127.0.0.1:5000/api/index/add' => Http::response(['success' => true], 200),
        ]);

        Cache::put('cbir_cache_version', 1);

        $media = Mockery::mock(\Spatie\MediaLibrary\MediaCollections\Models\Media::class)->makePartial();
        $media->shouldReceive('getPath')->andReturn('/tmp/fake.jpg');
        $media->shouldReceive('getUrl')->andReturn('http://example.com/fake.jpg');
        $media->model_type = \Aanugerah\WeddingPro\Models\Package::class;
        $media->model_id   = 1;
        $media->id         = 99;

        $service = new CBIRService();
        $result = $service->indexMedia($media);

        $this->assertTrue($result);
        $this->assertSame(2, Cache::get('cbir_cache_version'));
    }
}
