<?php

namespace Aanugerah\WeddingPro\Http\Controllers\Api;

use Aanugerah\WeddingPro\Http\Controllers\Controller;
use Aanugerah\WeddingPro\Models\WeddingOrganizer;
use Aanugerah\WeddingPro\Services\CBIRService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function byText(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1',
        ]);

        $query = $request->input('query');

        /** @var Collection $organizers */
        $organizers = WeddingOrganizer::where('id', 1) // Only search in the one brand
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('address', 'like', "%{$query}%");
            })
            ->get(['*']);

        $formattedResults = $organizers->map(function (WeddingOrganizer $wo) {
            return [
                'organizer' => $wo,
                'package' => $wo->packages()->first(['*']),
                'score' => 1.0,
                'similarity' => 100,
                'matched_image' => $wo->getFirstMediaUrl('gallery') ?: 'https://via.placeholder.com/800x400',
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $formattedResults,
        ]);
    }

    public function byImage(Request $request, CBIRService $cbirService)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // 10MB max
        ]);

        $apiResponse = $cbirService->searchByImage($request->file('image'));
        $results = $apiResponse['results'] ?? [];

        if (isset($apiResponse['error']) || ! ($apiResponse['success'] ?? false)) {
            return response()->json([
                'status' => 'error',
                'data' => [],
                'message' => $apiResponse['message'] ?? __('Rekomendasi gambar belum ditemukan.'),
            ]);
        }

        $formattedResults = collect($results)->map(function (mixed $result) {
            $wo = WeddingOrganizer::where('id', 1)->find($result['owner_id'], ['*']); // Filter and find by only allowed ID
            if (! $wo) {
                return null;
            }

            return [
                'organizer' => $wo,
                'package' => $wo->packages()->first(['*']),
                'score' => (float) ($result['score'] ?? 0),
                'similarity' => (float) ($result['similarity'] ?? 0),
                'matched_image' => $result['image_url'] ?? null,
            ];
        })->filter()->values();

        return response()->json([
            'status' => 'success',
            'data' => $formattedResults,
        ]);
    }
}
