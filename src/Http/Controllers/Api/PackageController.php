<?php

namespace Aanugerah\WeddingPro\Http\Controllers\Api;

use Aanugerah\WeddingPro\Http\Controllers\Controller;
use Aanugerah\WeddingPro\Models\Package;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    /**
     * Get all packages with optional filtering and pagination
     */
    public function index(Request $request)
    {
        try {
            $query = Package::with(['weddingOrganizer', 'category', 'reviews']);

            // Apply filters
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->filled('theme')) {
                $query->where('theme', 'like', '%'.$request->theme.'%');
            }

            if ($request->filled('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->filled('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            if ($request->filled('organizer_id')) {
                $query->where('wedding_organizer_id', $request->organizer_id);
            }

            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm): void {
                    $q->where('name', 'like', '%'.$searchTerm.'%')
                        ->orWhere('description', 'like', '%'.$searchTerm.'%');
                });
            }

            // Apply sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            // Validate sort parameters to prevent injection
            $allowedSortFields = ['name', 'price', 'created_at', 'rating', 'discount_price'];
            if (! in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }

            $allowedDirections = ['asc', 'desc'];
            if (! in_array(strtolower($sortDirection), $allowedDirections)) {
                $sortDirection = 'desc';
            }

            $query->orderBy($sortBy, $sortDirection);

            // Paginate results
            $packages = $query->paginate($request->get('per_page', 10), ['*']);

            return response()->json([
                'status' => 'success',
                'data' => $packages->items(),
                'pagination' => [
                    'current_page' => $packages->currentPage(),
                    'last_page' => $packages->lastPage(),
                    'per_page' => $packages->perPage(),
                    'total' => $packages->total(),
                    'has_more_pages' => $packages->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengambil paket'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific package by ID
     */
    public function show($id)
    {
        try {
            $package = Package::with([
                'weddingOrganizer:id,name,address,rating,is_verified',
                'category:id,name,description',
                'reviews' => function ($query): void {
                    $query->with('user:id,full_name,avatar_url')->latest()->limit(5);
                },
                'media',
            ])->findOrFail($id, ['*']);

            return response()->json([
                'status' => 'success',
                'data' => $package,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Paket tidak ditemukan'),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengambil detail paket'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get featured packages
     */
    public function featured(Request $request)
    {
        try {
            $packages = Package::with(['weddingOrganizer', 'category', 'reviews'])
                ->where('is_featured', true)
                ->paginate($request->get('per_page', 10), ['*']);

            return response()->json([
                'status' => 'success',
                'data' => $packages->items(),
                'pagination' => [
                    'current_page' => $packages->currentPage(),
                    'last_page' => $packages->lastPage(),
                    'per_page' => $packages->perPage(),
                    'total' => $packages->total(),
                    'has_more_pages' => $packages->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengambil paket unggulan'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get packages on sale/discount
     */
    public function onSale(Request $request)
    {
        try {
            $packages = Package::with(['weddingOrganizer', 'category', 'reviews'])
                ->whereNotNull('discount_price')
                ->where('discount_price', '<', 'price')
                ->paginate($request->get('per_page', 10), ['*']);

            return response()->json([
                'status' => 'success',
                'data' => $packages->items(),
                'pagination' => [
                    'current_page' => $packages->currentPage(),
                    'last_page' => $packages->lastPage(),
                    'per_page' => $packages->perPage(),
                    'total' => $packages->total(),
                    'has_more_pages' => $packages->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => __('Gagal mengambil paket diskon'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
