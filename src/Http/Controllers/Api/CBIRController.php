<?php

namespace Aanugerah\WeddingPro\Http\Controllers\Api;

use Aanugerah\WeddingPro\Http\Controllers\Controller;
use Aanugerah\WeddingPro\Models\Package;
use Aanugerah\WeddingPro\Models\Product;
use Aanugerah\WeddingPro\Services\CBIRService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CBIRController extends Controller
{
    /**
     * Search for similar wedding packages using image
     *
     * @return JsonResponse
     */
    public function searchSimilar(Request $request, CBIRService $cbirService)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // Max 10MB
            'top_k' => 'nullable|integer|min:1|max:50',
        ]);

        $topK = $request->input('top_k', 20);
        $apiResponse = $cbirService->searchByImage($request->file('image'), $topK);

        if (isset($apiResponse['error']) || ! ($apiResponse['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $apiResponse['message'] ?? 'Error',
                'results' => [],
            ]);
        }

        $results = $apiResponse['results'] ?? [];

        // Group IDs by type
        $idsByType = collect($results)->groupBy('type');

        $packageIds = $idsByType->get('package', collect())->pluck('owner_id')->all();
        $itemIds = $idsByType->get('product', collect())->pluck('owner_id')->all();

        $packages = Package::with(['category', 'weddingOrganizer'])
            ->whereIn('id', $packageIds)
            ->get()
            ->keyBy('id');

        $products = Product::with(['category', 'weddingOrganizer'])
            ->whereIn('id', $itemIds)
            ->get()
            ->keyBy('id');

        $enrichedResults = collect($results)->map(function (array $res) use ($packages, $products): ?array {
            $type = $res['type'] ?? 'unknown';
            $id = (int) ($res['owner_id'] ?? 0);

            $model = ($type === 'package') ? $packages->get($id) : (($type === 'product') ? $products->get($id) : null);

            if (! $model) {
                return null;
            }

            return [
                'type' => $type,
                'similarity' => $res['similarity'] ?? 0,
                'score' => $res['score'] ?? 0,
                'data' => [
                    'id' => $model->id,
                    'name' => $model->name,
                    'slug' => $model->slug,
                    'description' => strip_tags($model->description),
                    'price' => $model->price,
                    'discount_price' => $model->discount_price ?? 0,
                    'image_url' => $model->image_url,
                    'category' => $model->category?->name,
                    'wedding_organizer' => [
                        'id' => $model->weddingOrganizer?->id,
                        'name' => $model->weddingOrganizer?->name,
                    ],
                ],
            ];
        })->filter()->values();

        // Store in session for Blade preview
        session([
            'cbir_mixed_results' => $enrichedResults->toArray(),
            'cbir_search_time' => $apiResponse['query_time_seconds'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'results' => $enrichedResults,
            'total_results' => $enrichedResults->count(),
            'query_time_seconds' => $apiResponse['query_time_seconds'] ?? 0,
        ]);
    }

    /**
     * Index an product image into CBIR database
     *
     * @return JsonResponse
     */
    public function indexItem(Request $request, CBIRService $cbirService)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::with('weddingOrganizer')->findOrFail($request->product_id);
        $media = $product->getFirstMedia('product_image');

        if (! $media) {
            return response()->json(['success' => false, 'message' => 'No image found for this product'], 400);
        }

        $success = $cbirService->indexMedia($media);

        return response()->json([
            'success' => $success,
            'message' => $success ? __('Product indexed successfully') : __('Failed to index product'),
            'data' => [
                'product_id' => $product->id,
                'wedding_organizer_id' => $product->wedding_organizer_id,
            ],
        ]);
    }

    public function buildIndex(CBIRService $cbirService)
    {
        $packages = Package::all();
        $products = Product::all();

        $pCount = 0;
        $iCount = 0;
        $errors = [];

        foreach ($packages as $package) {
            $media = $package->getFirstMedia('package_image');
            if ($media) {
                if ($cbirService->indexMedia($media)) {
                    $pCount++;
                } else {
                    $errors[] = "Failed to index package ID {$package->id}";
                }
            }
        }

        foreach ($products as $product) {
            $media = $product->getFirstMedia('product_image');
            if ($media) {
                if ($cbirService->indexMedia($media)) {
                    $iCount++;
                } else {
                    $errors[] = "Failed to index product ID {$product->id}";
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => __('CBIR Index built with :pCount packages and :iCount products', ['pCount' => $pCount, 'iCount' => $iCount]),
            'indexed_packages' => $pCount,
            'indexed_items' => $iCount,
            'total_products' => $packages->count() + $products->count(),
            'errors' => $errors,
        ]);
    }

    /**
     * Get CBIR index statistics
     *
     * @return JsonResponse
     */
    public function getStats(CBIRService $cbirService)
    {
        try {
            $baseUrl = config('wedding-pro.ai_core_url', 'http://127.0.0.1:5000');
            $response = Http::get("{$baseUrl}/status");

            if ($response->successful()) {
                $status = $response->json();

                return response()->json([
                    'success' => true,
                    'data' => [
                        'mode' => 'local',
                        'server_status' => 'online',
                        'indexed_products' => $status['total_products'] ?? 0,
                        'total_database_items' => Product::query()->count(),
                    ],
                ]);
            }
        } catch (\Exception $e) {
        }

        return response()->json([
            'success' => true,
            'data' => [
                'mode' => 'local',
                'server_status' => 'offline',
                'total_database_items' => Product::query()->count(),
            ],
        ]);
    }

    /**
     * Health check for CBIR service
     *
     * @return JsonResponse
     */
    public function healthCheck()
    {
        return response()->json([
            'success' => true,
            'message' => __('CBIR lokal aktif dan sehat'),
            'data' => [
                'mode' => 'local',
            ],
        ]);
    }
}
