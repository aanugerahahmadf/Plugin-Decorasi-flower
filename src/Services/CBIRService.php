<?php

namespace Aanugerah\WeddingPro\Services;

use Aanugerah\WeddingPro\Models\Package;
use Aanugerah\WeddingPro\Models\Product;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CBIRService
{
    protected string $baseUrl;

    protected int $timeoutSeconds;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.ai_core_url', 'http://127.0.0.1:5000'), '/');
        $this->timeoutSeconds = (int) config('services.ai_core_timeout', 15);
    }

    public function searchByImage($imageFile, $topK = 10): array
    {
        try {
            $fileHash = md5_file($imageFile->getRealPath());
            $safeTopK = max(1, min((int) $topK, 50));
            $cacheVersion = (int) Cache::get('cbir_cache_version', 1);
            $cacheKey = "cbir_search_v{$cacheVersion}_{$fileHash}_{$safeTopK}";

            Log::info("CBIR Search initiated for file hash: {$fileHash}");

            return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($imageFile, $safeTopK) {
                Log::info('CBIR Cache miss - calling AI Core API');
                /** @var Response $response */
                $response = Http::timeout($this->timeoutSeconds)
                    ->retry(2, 300, throw: false)
                    ->attach(
                        'file', // Flask app.py expects 'file'
                        file_get_contents($imageFile->getRealPath()),
                        method_exists($imageFile, 'getClientOriginalName') ? $imageFile->getClientOriginalName() : $imageFile->getFilename()
                    )->post("{$this->baseUrl}/api/search", [
                        'top_k' => $safeTopK,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (! is_array($data)) {
                        Log::warning('AI Core returned non-array JSON payload.');

                        return $this->errorResponse(__('Format respons AI tidak valid.'));
                    }

                    $normalizedResults = collect($data['results'] ?? [])
                        ->filter(fn ($row) => is_array($row) && isset($row['owner_id']))
                        ->map(function (array $row): array {
                            $score = (float) ($row['score'] ?? 0);
                            $similarity = (float) ($row['similarity'] ?? ($score * 100));

                            return [
                                'owner_id' => (int) $row['owner_id'],
                                'type' => (string) ($row['type'] ?? 'product'),
                                'score' => $score,
                                'similarity' => $similarity,
                                'image_url' => $row['image_url'] ?? null,
                            ];
                        })
                        ->values()
                        ->all();

                    Log::info('AI Core responded successfully with '.count($normalizedResults).' normalized results.');

                    return [
                        'success' => true,
                        'results' => $normalizedResults,
                        'query_time_seconds' => (float) ($data['query_time_seconds'] ?? 0),
                    ];
                }

                Log::error('AI Core search error: '.$response->body());

                return $this->errorResponse(__('Pencarian visual sedang gangguan. Coba lagi nanti.'));
            });
        } catch (\Exception $e) {
            Log::error('AI Core connection error: '.$e->getMessage());

            return $this->errorResponse(__('Layanan AI Scanner sedang offline. Silakan coba beberapa saat lagi.'));
        }
    }

    public function indexMedia($media): bool
    {
        try {
            $type = match ($media->model_type) {
                Package::class => 'package',
                Product::class => 'product',
                default => 'wo_gallery',
            };

            $response = Http::timeout($this->timeoutSeconds)
                ->retry(2, 300, throw: false)
                ->post("{$this->baseUrl}/api/index/add", [
                    'image_path' => $media->getPath(),
                    'metadata' => [
                        'id' => $media->id,
                        'type' => $type,
                        'owner_id' => $media->model_id,
                        'image_url' => $media->getUrl(),
                    ],
                ]);

            if ($response->successful()) {
                Cache::increment('cbir_cache_version');

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('AI Core indexing error: '.$e->getMessage());

            return false;
        }
    }

    public function removeFromIndex($mediaId): bool
    {
        try {
            $response = Http::timeout($this->timeoutSeconds)
                ->retry(2, 300, throw: false)
                ->post("{$this->baseUrl}/api/index/remove", [
                    'metadata_id' => $mediaId,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('AI Core de-indexing error: '.$e->getMessage());

            return false;
        }
    }

    protected function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'error' => true,
            'message' => $message,
            'results' => [],
            'query_time_seconds' => 0,
        ];
    }
}
